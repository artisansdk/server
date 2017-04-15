<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ServerMessage;
use ArtisanSDK\Server\Entities\Message;
use ArtisanSDK\Server\Entities\Topics;

class UpdateSubscriptions extends Message implements ServerMessage
{
    /**
     * Send a list of all the subscriptions.
     *
     * @param \ArtisanSDK\Server\Entities\Topics $topics
     */
    public function __construct(Topics $topics)
    {
        $this->subscriptions = $topics->toArray();
    }
}
