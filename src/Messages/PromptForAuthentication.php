<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ClientMessage;
use ArtisanSDK\Server\Contracts\ServerMessage;
use ArtisanSDK\Server\Entities\Message;

class PromptForAuthentication extends Message implements ServerMessage
{
    /**
     * Prompt for authentication before authorizing the previous client message.
     *
     * @param \ArtisanSDK\Server\Contracts\ClientMessage $message
     */
    public function __construct(ClientMessage $message)
    {
        $this->previous = $message->toArray();
        $this->message  = 'Authorization required.';
        $this->code     = 401;
    }
}
