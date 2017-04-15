<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ClientMessage;
use ArtisanSDK\Server\Entities\Message;
use ArtisanSDK\Server\Traits\NoProtection;

class NotifyConnection extends Message implements ClientMessage
{
    use NoProtection;

    /**
     * Save the command arguments for later when the command is run.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        parent::__construct($arguments);
        $this->receiver = array_get($arguments, 'receiver');
        $this->sender = array_get($arguments, 'sender');
    }
}
