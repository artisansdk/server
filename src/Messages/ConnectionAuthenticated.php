<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\Connection;
use ArtisanSDK\Server\Contracts\ServerMessage;
use ArtisanSDK\Server\Entities\Message;

class ConnectionAuthenticated extends Message implements ServerMessage
{
    /**
     * Send back connection details to the connection that was just established.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection->toArray();
    }
}
