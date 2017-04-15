<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ClientMessage;
use ArtisanSDK\Server\Contracts\Connection;
use ArtisanSDK\Server\Entities\Message;
use ArtisanSDK\Server\Traits\AdminProtection;

abstract class DisconnectType extends Message implements ClientMessage
{
    use AdminProtection;

    /**
     * Save the message arguments for later when the message is handled.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        parent::__construct($arguments);
        $this->type = Connection::ANONYMOUS;
    }
}
