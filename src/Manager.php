<?php

namespace ArtisanSDK\Server;

use ArtisanSDK\Server\Commands\RunQueuedCommands;
use ArtisanSDK\Server\Contracts\ClientMessage;
use ArtisanSDK\Server\Contracts\Command;
use ArtisanSDK\Server\Contracts\Connection;
use ArtisanSDK\Server\Contracts\Listener;
use ArtisanSDK\Server\Contracts\Manager as ManagerInterface;
use ArtisanSDK\Server\Contracts\Message;
use ArtisanSDK\Server\Contracts\Process;
use ArtisanSDK\Server\Contracts\Promise;
use ArtisanSDK\Server\Contracts\SelfHandling;
use ArtisanSDK\Server\Contracts\Timer;
use ArtisanSDK\Server\Contracts\Topic;
use ArtisanSDK\Server\Entities\Commands;
use ArtisanSDK\Server\Entities\Connections;
use ArtisanSDK\Server\Entities\Listeners;
use ArtisanSDK\Server\Entities\Processes;
use ArtisanSDK\Server\Entities\Timers;
use ArtisanSDK\Server\Entities\Topics;
use ArtisanSDK\Server\Listeners\ConnectionPool;
use ArtisanSDK\Server\Listeners\Notifier;
use ArtisanSDK\Server\Listeners\ServerAdmin;
use ArtisanSDK\Server\Messages\ConnectionEstablished;
use ArtisanSDK\Server\Messages\PromptForAuthentication;
use ArtisanSDK\Server\Messages\UpdateConnections;
use ArtisanSDK\Server\Messages\UpdateSubscriptions;
use ArtisanSDK\Server\Messages\UpdateTopics;
use ArtisanSDK\Server\Timers\DelayedCommand;
use ArtisanSDK\Server\Timers\QueueWorker;
use ArtisanSDK\Server\Traits\FluentProperties;
use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

class Manager implements ManagerInterface
{
    use FluentProperties;

    protected $commands;
    protected $connections;
    protected $connector;
    protected $listeners;
    protected $loop;
    protected $processes;
    protected $queue;
    protected $timers;
    protected $topics;

    /**
     * Setup the initial state of the manager when starting.
     *
     * @return self
     */
    public function boot()
    {
        // Initialize collections
        $this->commands(new Commands());
        $this->connections(new Connections());
        $this->listeners(new Listeners());
        $this->processes(new Processes());
        $this->timers(new Timers());
        $this->topics(new Topics());

        // Register all the timers
        $this->add(new QueueWorker());

        // Register all the listeners
        $this->listener(new ConnectionPool());
        $this->listener(new Notifier());
        $this->listener(new ServerAdmin());

        return $this;
    }

    /**
     * Called when the server is started.
     *
     * @return self
     */
    public function start()
    {
        // Log the start time
        $this->broker()->log('Server started at: '.time());

        // Initialize the application state
        $this->boot();

        // Start the actual loop: starts blocking
        $this->loop()->run();
        $this->broker()->log('Server stopped at: '.time());

        return $this;
    }

    /**
     * Called when the server is stopped.
     *
     * @return self
     */
    public function stop()
    {
        $this->loop()->stop();

        return $this;
    }

    /**
     * Called when a new connection is opened.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection being opened
     *
     * @return self
     */
    public function open(Connection $connection)
    {
        $this->connections()->add($connection);

        $this->send(new ConnectionEstablished($connection), $connection)
            ->broadcast(new UpdateConnections($this->connections()));

        return $this;
    }

    /**
     * Send message to one connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Message    $message    to send
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to send to
     *
     * @return self
     */
    public function send(Message $message, Connection $connection)
    {
        $message = $this->prepareMessage($message);

        $this->broker()->send($message, $connection);

        return $this;
    }

    /**
     * Send message to one connection and then close the connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Message    $message    to send
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to send to
     *
     * @return self
     */
    public function end(Message $message, Connection $connection)
    {
        $this->broker()->end($message, $connection);

        return $this;
    }

    /**
     * Broadcast message to multiple connections.
     *
     * @param \ArtisanSDK\Server\Contracts\Message    $message
     * @param \ArtisanSDK\Server\Entities\Connections $connections to send to (defaults to everyone)
     *
     * @return self
     */
    public function broadcast(Message $message, Connections $connections = null)
    {
        if (is_null($connections)) {
            $connections = $this->connections();
        }

        $message = $this->prepareMessage($message);

        if ($message->topics() && $message->topics()->count()) {
            $connections = $connections->topics($message->topics());
        }

        $this->broker()->broadcast($message, $connections);

        return $this;
    }

    /**
     * Called when a new message is received from an open connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Message    $message    payload received
     * @param \ArtisanSDK\Server\Contracts\Connection $connection sending the message
     *
     * @throws \InvalidArgumentException message is not a supported client message
     *
     * @return self
     */
    public function receive(Message $message, Connection $connection)
    {
        if ( ! $message instanceof ClientMessage) {
            throw new InvalidArgumentException(get_class($message).' is not a supported ClientMessage.');
        }

        $message->dispatcher($this);

        if ( ! $message->client($connection)->authorize()) {
            return $this->send(new PromptForAuthentication($message), $connection);
        }

        if ($message instanceof SelfHandling) {
            $message->handle();
        }

        $this->listeners->handle($message);

        return $this;
    }

    /**
     * Called when an open connection is closed.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to be closed
     *
     * @return self
     */
    public function close(Connection $connection)
    {
        $this->connections()->remove($connection);

        $this->broadcast(new UpdateConnections($this->connections()));

        return $this;
    }

    /**
     * Called when an error occurs on the connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection that errored
     * @param \Exception                              $exception  caught
     *
     * @return self
     */
    public function error(Connection $connection, Exception $exception)
    {
        $connection->close();

        $this->close($connection);

        return $this;
    }

    /**
     * Get or set the connections on the server.
     *
     * @example connections() ==> \ArtisanSDK\Server\Entities\Connections
     *          connections($connections) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Connections $connections
     *
     * @return \ArtisanSDK\Server\Entities\Connections|self
     */
    public function connections(Connections $connections = null)
    {
        return $this->property(__FUNCTION__, $connections);
    }

    /**
     * Get or set the topics available for subscribing.
     *
     * @example topics() ==> \ArtisanSDK\Server\Entities\Topics
     *          topics($topics) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Topics $topics
     *
     * @return \ArtisanSDK\Server\Entities\Topics|self
     */
    public function topics(Topics $topics = null)
    {
        return $this->property(__FUNCTION__, $topics);
    }

    /**
     * Register a new topic in the collection of topics.
     *
     * @param \ArtisanSDK\Server\Contracts\Topic $topic to register
     *
     * @return self
     */
    public function register(Topic $topic)
    {
        $this->topics()->add($topic);

        $this->broadcast(new UpdateTopics($this->topics()));

        return $this;
    }

    /**
     * Unregister an existing topic from the collection of topics.
     *
     * @param \ArtisanSDK\Server\Contracts\Topic $topic to unregister
     *
     * @return self
     */
    public function unregister(Topic $topic)
    {
        $this->topics()->remove($topic);
        $topic->subscriptions()->each(function ($connection) {
            $connection->unsubscribe();
        });
        $topic->subscriptions(new Connections());

        $this->broadcast(new UpdateTopics($this->topics()));

        return $this;
    }

    /**
     * Subscribe a connection to the topic.
     *
     * @param \ArtisanSDK\Server\Contracts\Topic      $topic      to subscribe to
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to subscribe to topic
     *
     * @return self
     */
    public function subscribe(Topic $topic, Connection $connection)
    {
        if ($this->topics()->uuid($topic)) {
            $connection->subscribe($topic);
            $topic->subscribe($connection);
            $this->send(new UpdateSubscriptions($connection->subscriptions()), $connection);
        }

        return $this;
    }

    /**
     * Unsubscribe a connection from the topic.
     *
     * @param \ArtisanSDK\Server\Contracts\Topic      $topic      to unsubscribe from
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to unsubscribe from topic
     *
     * @return self
     */
    public function unsubscribe(Topic $topic, Connection $connection)
    {
        if ($connection->subscriptions()->uuid($topic)) {
            $connection->unsubscribe($topic);
            $topic->unsubscribe($connection);
            $this->send(new UpdateSubscriptions($connection->subscriptions()), $connection);
        }

        return $this;
    }

    /**
     * Get or set the timers available for executing.
     *
     * @example timers() ==> \ArtisanSDK\Server\Entities\Timers
     *          timers($timers) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Timers $timers
     *
     * @return \ArtisanSDK\Server\Entities\Timers|self
     */
    public function timers(Timers $timers = null)
    {
        return $this->property(__FUNCTION__, $timers);
    }

    /**
     * Add a timer to the event loop.
     *
     * @param \ArtisanSDK\Server\Contracts\Timer $timer to add
     *
     * @return self
     */
    public function add(Timer $timer)
    {
        $timer->dispatcher($this);

        $this->timers()->add($timer);

        return $this;
    }

    /**
     * Pause a timer in the event loop so that it does not run until resumed.
     *
     * @param \ArtisanSDK\Server\Contracts\Timer $timer to pause
     *
     * @return self
     */
    public function pause(Timer $timer)
    {
        $timer->pause();

        return $this;
    }

    /**
     * Resume a timer in the event loop that was previously paused.
     *
     * @param \ArtisanSDK\Server\Contracts\Timer $timer to resume
     *
     * @return self
     */
    public function resume(Timer $timer)
    {
        $timer->resume();

        return $this;
    }

    /**
     * Add a timer that runs only once after the initial delay.
     *
     * @param \ArtisanSDK\Server\Contracts\Timer $timer to run once
     *
     * @return self
     */
    public function once(Timer $timer)
    {
        $timer->once();

        $this->add($timer);

        return $this;
    }

    /**
     * Cancel a timer in the event loop that is currently active.
     *
     * @param \ArtisanSDK\Server\Contracts\Timer $timer to cancel
     *
     * @return self
     */
    public function cancel(Timer $timer)
    {
        $this->timers()->remove($timer);

        return $this;
    }

    /**
     * Get the event loop the server runs on.
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function loop()
    {
        return Server::instance()->loop();
    }

    /**
     * Get the broker that communicates with the server.
     *
     * @return \ArtisanSDK\Server\Contracts\Broker
     */
    public function broker()
    {
        return Server::instance()->broker();
    }

    /**
     * Get or set the queue connector the server uses.
     *
     * @example connector() ==> \Illuminate\Contracts\Queue\Queue
     *          connector($instance) ==> self
     *
     * @param \Illuminate\Contracts\Queue\Queue $instance
     *
     * @return \Illuminate\Contracts\Queue\Queue|self
     */
    public function connector(Queue $instance = null)
    {
        return $this->property(__FUNCTION__, $instance);
    }

    /**
     * Get or set the queue the server processes.
     *
     * @example queue() ==> 'server'
     *          queue('server') ==> self
     *
     * @param string $name of queue
     *
     * @return string|self
     */
    public function queue($name = null)
    {
        return $this->property(__FUNCTION__, $name);
    }

    /**
     * Process a job that has been popped off the queue.
     *
     * @param \Illuminate\Contracts\Queue\Job $job to be processed
     *
     * @return self
     */
    public function work(Job $job)
    {
        $payload = $job->getRawBody();
        $message = json_decode($payload, true);
        $arguments = array_get($message, 'data', []);

        $command = array_get($message, 'job');
        if ( ! is_null($command)) {
            if ( ! class_exists($command)) {
                throw new Exception("Command $command not found.");
            }
            $this->run(new $command($arguments));
        }

        $job->delete();

        return $this;
    }

    /**
     * Get or set the commands available to be ran.
     *
     * @example commands() ==> \ArtisanSDK\Server\Entities\Commands
     *          commands($commands) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Commands $commands
     *
     * @return \ArtisanSDK\Server\Entities\Commands|self
     */
    public function commands(Commands $commands = null)
    {
        return $this->property(__FUNCTION__, $commands);
    }

    /**
     * Run a command immediately within this tick of the event loop.
     *
     * @param \ArtisanSDK\Server\Contracts\Command $command to run
     *
     * @return self
     */
    public function run(Command $command)
    {
        if ($command->delay() > 0) {
            $timer = new DelayedCommand();
            $timer->command($command)
                ->interval($command->delay());

            $this->once($timer);

            return $this;
        }

        $command->dispatcher($this)->run();

        return $this;
    }

    /**
     * Run a command in the next tick of the event loop.
     *
     * @param \ArtisanSDK\Server\Contracts\Command $command to run
     *
     * @return self
     */
    public function next(Command $command)
    {
        $this->commands()->add($command);

        $this->loop()->nextTick(function () {
            $this->run(new RunQueuedCommands());
        });

        return $this;
    }

    /**
     * Abort a command before it has a chance to run.
     *
     * @param \ArtisanSDK\Server\Contracts\Command $command to abort
     *
     * @return self
     */
    public function abort(Command $command)
    {
        $this->commands()->remove($command);

        return $this;
    }

    /**
     * Delay the execution of a command.
     *
     * @param \ArtisanSDK\Server\Contracts\Command $command to delay
     * @param int                                  $delay   in milliseconds
     *
     * @return self
     */
    public function delay(Command $command, $delay)
    {
        $command->delay($delay);

        $this->run($command);

        return $this;
    }

    /**
     * Get or set the listeners that are registered.
     *
     * @example listeners() ==> \ArtisanSDK\Server\Entities\Listeners
     *          listeners($listeners) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Listeners $listeners
     *
     * @return \ArtisanSDK\Server\Entities\Listeners|self
     */
    public function listeners(Listeners $listeners = null)
    {
        return $this->property(__FUNCTION__, $listeners);
    }

    /**
     * Bind a message to a command so that the command listens for
     * the message as an event and is ran when the event occurs.
     *
     * @param \ArtisanSDK\Server\Contracts\Message $message to listen for
     * @param \ArtisanSDK\Server\Contracts\Command $command to run
     *
     * @return self
     */
    public function listen(Message $message, Command $command)
    {
        $listener = Listener::make()
            ->dispatcher($this)
            ->register($message, $command);

        return $this->listener($listener);
    }

    /**
     * Add a listener to the collection of listeners.
     *
     * @param \ArtisanSDK\Server\Contracts\Listener $listener to add
     *
     * @return self
     */
    public function listener(Listener $listener)
    {
        $this->listeners()->add($listener->dispatcher($this));

        return $this;
    }

    /**
     * Remove a listener from the collection of listeners.
     *
     * @param \ArtisanSDK\Server\Contracts\Listener $listener to remove
     *
     * @return self
     */
    public function silence(Listener $listener)
    {
        $this->listeners()->remove($listener);

        return $this;
    }

    /**
     * Get or set the processes that are running.
     *
     * @example processes() ==> \ArtisanSDK\Server\Entities\Processes
     *          processes($processes) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Processes $processes
     *
     * @return \ArtisanSDK\Server\Entities\Processes|self
     */
    public function processes(Processes $processes = null)
    {
        return $this->property(__FUNCTION__, $processes);
    }

    /**
     * Add a process to the processes and begin running it.
     *
     * @param \ArtisanSDK\Server\Contracts\Process $process to add
     *
     * @return self
     */
    public function execute(Process $process)
    {
        $process->dispatcher($this);

        $this->processes()->add($process);

        $process->start($this->loop());

        return $this;
    }

    /**
     * Stop a process that is running and remove it from the processes.
     *
     * @param \ArtisanSDK\Server\Contracts\Process $process to terminate
     *
     * @return self
     */
    public function terminate(Process $process)
    {
        $process->stop();

        $this->processes()->remove($process);

        return $this;
    }

    /**
     * Pipe the output of one process to the input of another process.
     * Both processes will be added to the processes and started automatically.
     *
     * @param \ArtisanSDK\Server\Contracts\Process $input  to pipe to output
     * @param \ArtisanSDK\Server\Contracts\Process $output to receive from input pipe
     *
     * @return self
     */
    public function pipe(Process $input, Process $output)
    {
        // Start both processes
        $this->execute($input)
            ->execute($output);

        // Cascade the termination of one process to the other
        $input->on('exit', function ($exitCode, $termSignal) use ($output) {
            $this->terminate($output);
        });

        // Pipe process output of one process to process input of the other
        $input->output()->on('data', function ($chunk) use ($output) {
            $output->input()->write($chunk);
        });

        return $this;
    }

    /**
     * Run a deferred promise (for resolving asynchronous code).
     *
     * @param \ArtisanSDK\Server\Contracts\Promise $promise to defer
     * @param mixed                                $result  to resolve promise for
     *
     * @return self
     */
    public function promise(Promise $promise, $result = null)
    {
        $promise->dispatcher($this)->resolve($result);

        return $this;
    }

    /**
     * Prepare the message to be sent out over the broker.
     *
     * @param \ArtisanSDK\Server\Contracts\Message $message
     *
     * @return \ArtisanSDK\Server\Contracts\Message
     */
    protected function prepareMessage(Message $message)
    {
        return $message->id(Uuid::uuid4()->toString())
            ->name(class_basename($message))
            ->timestamp(microtime(true));
    }
}
