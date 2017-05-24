<?php

namespace ArtisanSDK\Server;

use ArtisanSDK\Server\Contracts\Broker as BrokerInterface;
use ArtisanSDK\Server\Contracts\Manager as ManagerInterface;
use ArtisanSDK\Server\Contracts\Server as ServerInterface;
use ArtisanSDK\Server\Traits\FluentProperties;
use Illuminate\Contracts\Queue\Queue as QueueInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Queue as QueueManager;
use InvalidArgumentException;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

class Server implements ServerInterface
{
    use FluentProperties;

    protected $broker;
    protected $connector;
    protected $http;
    protected $manager;
    protected $socket;
    protected $websocket;
    protected static $instance;

    protected $config = [
        'address' => '0.0.0.0',
        'port'    => 8080,
    ];

    protected $serviceMap = [
        BrokerInterface::class  => 'broker',
        HttpServer::class       => 'http',
        IoServer::class         => 'socket',
        LoopInterface::class    => 'loop',
        ManagerInterface::class => 'manager',
        OutputInterface::class  => 'logger',
        QueueInterface::class   => 'resolveConnector',
        WsServer::class         => 'websocket',
    ];

    /**
     * Make a new instance of the server.
     *
     * @return self
     */
    public static function make()
    {
        $config = app('config');
        $server = app(static::class)
            ->uses((array) $config->get('server'))
            ->uses(app($config->get('server.manager', Manager::class)))
            ->uses(new Broker());

        self::$instance = $server
            ->uses(new WsServer($server->broker()))
            ->uses(new HttpServer($server->websocket()))
            ->uses(IoServer::factory($server->http(), $server->port(), $server->address()));

        return $server;
    }

    /**
     * Get a new or the existing instance of the server.
     *
     * @return self
     */
    public static function instance()
    {
        if ( ! self::$instance instanceof ServerInterface) {
            self::make();
        }

        return self::$instance;
    }

    /**
     * Set the bindings for the server.
     *
     * @example bind('0.0.0.0')
     *          bind('0.0.0.0', 8080)
     *          bind(['0.0.0.0', 8080])
     *
     * @param string|array $address to bind to
     * @param int          $port    to listen on
     *
     * @return self
     */
    public function bind($address, $port = null)
    {
        if (is_array($address)) {
            list($address, $port) = $address;
        }

        $this->address($address);
        $this->port($port);

        return $this;
    }

    /**
     * Start the server by running the event loop.
     */
    public function start()
    {
        $this->manager()->start();
    }

    /**
     * Stop the server by stopping the event loop.
     */
    public function stop()
    {
        $this->manager()->stop();
    }

    /**
     * Get or set the config settings.
     *
     * @example config() ==> array
     *          config(array $settings) ==> self
     *          config('key') ==> mixed
     *          config('key', $value) ==> self
     *
     * @param array|string $key   to set or get
     * @param mixed        $value to set under the key
     *
     * @return mixed|self
     */
    public function config($key = null, $value = null)
    {
        // Replace the entire config
        if (is_array($key)) {
            return $this->property(__FUNCTION__, $key);
        } elseif ($key instanceof Arrayable) {
            return $this->property(__FUNCTION__, $key->toArray());

        // Get the value for the key
        } elseif (is_string($key) && is_null($value)) {
            return array_get($this->property(__FUNCTION__), $key);

        // Set the value for the key
        } elseif (is_string($key) && ! is_null($value)) {
            $config = $this->config();
            array_set($config, $key, $value);
            $this->config($config);

            return $this;
        }

        // Get the entire config
        return $this->property(__FUNCTION__);
    }

    /**
     * Get or set the address the server binds to.
     *
     * @example address() ==> '0.0.0.0'
     *          address('0.0.0.0') ==> self
     *
     * @param string $ip4 address
     *
     * @return string|self
     */
    public function address($ip4 = null)
    {
        return $this->config(__FUNCTION__, $ip4);
    }

    /**
     * Get or set the port the server listens on.
     *
     * @example port() ==> 8080
     *          port(8080) ==> self
     *
     * @param int $number for port
     *
     * @return string|self
     */
    public function port($number = null)
    {
        return $this->config(__FUNCTION__, is_null($number) ? $number : (int) $number);
    }

    /**
     * Get or set the bindings for the server.
     *
     * @example bindings() ==> ['0.0.0.0', 8080]
     *          bindings('0.0.0.0') ==> self
     *          bindings('0.0.0.0', 8080) ==> self
     *          bindings(['0.0.0.0', 8080]) ==> self
     *
     * @param string|array $address to bind to
     * @param int          $port    to listen on
     *
     * @return array|self
     */
    public function bindings($address = null, $port = null)
    {
        if ( ! is_null($address)) {
            $this->bind($address, $port);
        }

        if (empty(func_get_args())) {
            return [$this->address(), $this->port()];
        }

        return $this;
    }

    /**
     * Set an instance of a service that should be used by the server.
     *
     * @example uses(\Illuminate\Contracts\Queue\Queue $service, 'default') to set a connector and queue
     *          uses(\Symfony\Component\Console\Output\OutputInterface $service) to set the output logging interface
     *          uses(\ArtisanSDK\Server\Contracts\Manager $manager) to set connection manager
     *          uses(\ArtisanSDK\Server\Contracts\Broker $broker) to set message broker
     *          uses(\Ratchet\WebSocket\WsServer $server) to set WebSocket server
     *          uses(\Ratchet\Http\HttpServer $server) to set HTTP server
     *          uses(\Ratchet\Server\IoServer $socket) to set I/O socket
     *          uses(\React\EventLoop\LoopInterface $loop) to set event loop
     *          uses(array|Arrayable $config) to set the configuration settings
     *          uses($key, $value) to set the configuration key-value pair
     *          uses($classname, $arg1, ... $argN) to resolve and then use the service
     *
     * @param mixed $service
     *
     * @throws \InvalidArgumentException if service is not supported
     *
     * @return self
     */
    public function uses($service)
    {
        $arguments = array_slice(func_get_args(), 1);

        // Resolve array like service as config
        if ($service instanceof Arrayable || is_array($service)) {
            return $this->config($service);

        // Use service as key and arguments as value in config
        } elseif (count($arguments) > 0 && is_string($service)) {
            return $this->config($service, head($arguments));

        // Resolve service class to supported service
        } elseif (is_object($service)) {
            return $this->resolveService($service, $arguments);

        // Resolve service name to supported service
        } elseif (is_string($service) && class_exists($service)) {
            return $this->resolveService(app($service, $arguments));
        }

        throw new InvalidArgumentException($service.' is not a resolvable class. The uses() method should be called with two arguments to use '.$service.' as a config key.');
    }

    /**
     * Resolve the service using a service map.
     *
     * @param object $service
     * @param array  $arguments
     *
     * @throws \InvalidArgumentException if service is not supported
     *
     * @return self
     */
    protected function resolveService($service, $arguments = [])
    {
        // Get the implementation instances of the service
        $reflection = new ReflectionClass($service);
        $instances = array_merge([$reflection->getName()], $reflection->getInterfaceNames());
        while ($instance = $reflection->getParentClass()) {
            $instances[] = $instance->getName();
            $reflection = $instance;
        }

        // Check if a service implementation is in the service map
        foreach ($instances as $instance) {
            $concrete = array_get($this->serviceMap, $instance);
            if ($concrete && $service instanceof $instance) {
                return call_user_func_array([$this, $concrete], array_merge([$service], $arguments));
            }
        }

        throw new InvalidArgumentException(get_class($service).' is not a supported service.');
    }

    /**
     * Set the queue the server processes.
     *
     * @example resolveConnector() is equivalent to resolveConnector('default', 'default')
     *          resolveConnector($connection) to inject an existing connector
     *          resolveConnector('beanstalkd') to use beanstalkd driver on default queue
     *          resolveConnector('beanstalkd', 'server') to use beanstalkd driver on server queue
     *
     * @param string|\Illuminate\Contracts\Queue\Queue $connection
     * @param string                                   $name
     *
     * @return self
     */
    protected function resolveConnector($connection = null, $name = null)
    {
        if ( ! $connection instanceof QueueInterface) {
            $connection = QueueManager::connection($connection);
        }

        $this->connector($connection);
        $this->queue($name);

        return $this;
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
    public function connector(QueueInterface $instance = null)
    {
        if ( ! is_null($instance)) {
            $this->manager()->connector($instance);

            return $this;
        }

        return $this->manager()->connector();
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
        if ( ! is_null($name)) {
            $this->manager()->queue($name);

            return $this;
        }

        return $this->manager()->queue();
    }

    /**
     * Get or set the logger interface the server pipes output to.
     *
     * @example logger() ==> \Symfony\Component\Console\Output\OutputInterface
     *          logger($interface) ==> self
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $interface
     *
     * @return \Symfony\Component\Console\Output\OutputInterface|self
     */
    public function logger(OutputInterface $interface = null)
    {
        if ( ! is_null($interface)) {
            $this->broker()->logger($interface);

            return $this;
        }

        return $this->broker()->logger();
    }

    /**
     * Get or set the connection manager the server uses.
     *
     * @example manager() ==> \ArtisanSDK\Server\Contracts\Manager
     *          manager($instance) ==> self
     *
     * @param \ArtisanSDK\Server\Contracts\Manager $instance
     *
     * @return \ArtisanSDK\Server\Contracts\Manager|self
     */
    public function manager(ManagerInterface $instance = null)
    {
        return $this->property(__FUNCTION__, $instance);
    }

    /**
     * Get or set the message broker the server uses.
     *
     * @example broker() ==> \ArtisanSDK\Server\Contracts\Broker
     *          broker($instance) ==> self
     *
     * @param \ArtisanSDK\Server\Contracts\Broker $instance
     *
     * @return \ArtisanSDK\Server\Contracts\Broker|self
     */
    public function broker(BrokerInterface $instance = null)
    {
        return $this->property(__FUNCTION__, $instance);
    }

    /**
     * Get or set the WebSocket instance the server uses.
     *
     * @example websocket() ==> \Ratchet\WebSocket\WsServer
     *          websocket($instance) ==> self
     *
     * @param \Ratchet\WebSocket\WsServer $instance
     *
     * @return \Ratchet\WebSocket\WsServer|self
     */
    public function websocket(WsServer $instance = null)
    {
        return $this->property(__FUNCTION__, $instance);
    }

    /**
     * Get or set the HTTP instance the server uses.
     *
     * @example http() ==> \Ratchet\Http\HttpServer
     *          http($instance) ==> self
     *
     * @param \Ratchet\Http\HttpServer $instance
     *
     * @return \Ratchet\Http\HttpServer|self
     */
    public function http(HttpServer $instance = null)
    {
        return $this->property(__FUNCTION__, $instance);
    }

    /**
     * Get or set the I/O instance the server uses.
     *
     * @example socket() ==> \Ratchet\Server\IoServer
     *          socket($instance) ==> self
     *
     * @param \Ratchet\Server\IoServer $instance
     *
     * @return \Ratchet\Server\IoServer|self
     */
    public function socket(IoServer $instance = null)
    {
        return $this->property(__FUNCTION__, $instance);
    }

    /**
     * Get or set the event loop the server uses.
     *
     * @example loop() ==> \React\EventLoop\LoopInterface
     *          loop($instance) ==> self
     *
     * @param \React\EventLoop\LoopInterface $instance
     *
     * @return \React\EventLoop\LoopInterface|self
     */
    public function loop(LoopInterface $instance = null)
    {
        if ( ! is_null($instance)) {
            $this->socket()->loop = $instance;

            return $this;
        }

        return $this->socket()->loop;
    }

    /**
     * Map undefined methods to config() method calls.
     *
     * @param  password() ==> config('password') ==> mixed
     *         password($value) ==> config('password', $value) ==> self
     * @param string $method which maps to config key
     * @param array  $args   which become the config value
     *
     * @return mixed|self
     */
    public function __call($method, $args = [])
    {
        array_unshift($args, snake_case($method));

        return call_user_func_array([$this, 'config'], $args);
    }

    /**
     * Mock the instance of the server.
     *
     * @param \ArtisanSDK\Server\Contracts\Server $instance
     */
    public static function mock($instance)
    {
        static::$instance = $instance;
    }
}
