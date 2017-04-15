<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ServerMessage;
use ArtisanSDK\Server\Entities\Message;
use ArtisanSDK\Server\Entities\Notifications;

class UpdateNotifications extends Message implements ServerMessage
{
    /**
     * Send a list of all the notifications.
     *
     * @param \ArtisanSDK\Server\Entities\Notifications $notifications
     */
    public function __construct(Notifications $notifications)
    {
        $this->notifications = $notifications->toArray();
    }
}
