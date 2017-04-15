<?php

namespace ArtisanSDK\Server\Contracts;

use ArtisanSDK\Server\Entities\Connections;
use Exception;

interface Broker
{
    /**
     * Called when a new connection is opened.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection being opened
     *
     * @return self
     */
    public function open(Connection $connection);

    /**
     * Send message to one connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Message    $message    to send
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to send to
     *
     * @return self
     */
    public function send(Message $message, Connection $connection);

    /**
     * Send message to one connection and then close the connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Message    $message    to send
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to send to
     *
     * @return self
     */
    public function end(Message $message, Connection $connection);

    /**
     * Broadcast message to multiple connections.
     *
     * @param \ArtisanSDK\Server\Contracts\Message    $message
     * @param \ArtisanSDK\Server\Entities\Connections $connections to send to
     * @param bool                                    $silent      output
     *
     * @return self
     */
    public function broadcast(Message $message, Connections $connections);

    /**
     * Called when a new message is received from an open connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection sending the message
     * @param string                                  $message    payload received
     *
     * @return self
     */
    public function message(Connection $connection, $message);

    /**
     * Called when an open connection is closed.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to be closed
     *
     * @return self
     */
    public function close(Connection $connection);

    /**
     * Called when an error occurs on the connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection that errored
     * @param \Exception                              $exception  caught
     *
     * @return self
     */
    public function error(Connection $connection, Exception $exception);
}
