<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ServerMessage;
use ArtisanSDK\Server\Entities\Connections;
use ArtisanSDK\Server\Entities\Message;

class UpdateConnections extends Message implements ServerMessage
{
    /**
     * Send a list of all the connections.
     *
     * @param \ArtisanSDK\Server\Entities\Connections $connections
     */
    public function __construct(Connections $connections)
    {
        $this->connections = $connections->toArray();
    }
}
