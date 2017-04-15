<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\Connection;

class JoinAsPlayer extends JoinAsType
{
    /**
     * Save the message arguments for later when the message is handled.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        parent::__construct($arguments);
        $this->type = Connection::PLAYER;
    }
}
