<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ClientMessage;
use ArtisanSDK\Server\Entities\Message;
use ArtisanSDK\Server\Traits\ClientProtection;

class DismissNotifications extends Message implements ClientMessage
{
    use ClientProtection;

    /**
     * Save the message arguments for later when the message is handled.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        parent::__construct($arguments);
        $this->uuid = array_get($arguments, 'connection');
    }
}
