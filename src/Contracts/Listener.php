<?php

namespace ArtisanSDK\Server\Contracts;

interface Listener
{
    /**
     * Make an instance of the listener.
     *
     * @return \ArtisanSDK\Server\Contracts\Listener
     */
    public static function make();

    /**
     * Initialize any registered message handlers upon construction.
     *
     * @return self
     */
    public function boot();

    /**
     * Get or set the command dispatcher.
     *
     * @param \ArtisanSDK\Server\Contracts\Manager $instance for the server
     *
     * @return \ArtisanSDK\Server\Contracts\Manager|self
     */
    public function dispatcher(Manager $instance = null);

    /**
     * Pass the message to the command handlers that are listening
     * for the message to be received.
     *
     * @param \ArtisanSDK\Serer\Contracts\Message $message
     *
     * @return self
     */
    public function handle(Message $message);

    /**
     * Get the commands registered for this listener.
     *
     * Optional argument filters the collection of commands to those
     * registered for that message.
     *
     * @param \ArtisanSDK\Server\Contracts\Message|string|null $message
     *
     * @return \Illuminate\Support\Collection
     */
    public function commands($message = null);

    /**
     * Get the messages registered for this listener.
     *
     * Optional argument filters the collection of messages to those
     * registered for that command.
     *
     * @param \ArtisanSDK\Server\Contracts\Command|string|null $command
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages($command = null);

    /**
     * Register a command to handle the message.
     *
     * @param \ArtisanSDK\Server\Contracts\Message|string $message to listen for
     * @param \ArtisanSDK\Server\Contracts\Command|string $command to invoke for message
     *
     * @return self
     */
    public function register($message, $command);

    /**
     * Unregister a message handler entirely or for a single command.
     *
     * @param \ArtisanSDK\Server\Contracts\Message|string $message that is being listened to
     * @param \ArtisanSDK\Server\Contracts\Command|string $command to unregister
     *
     * @return self
     */
    public function unregister($message, $command = null);
}
