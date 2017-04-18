<?php

namespace ArtisanSDK\Server\Commands;

use ArtisanSDK\Server\Entities\Command;
use ArtisanSDK\Server\Entities\Notification;
use ArtisanSDK\Server\Messages\UpdateNotifications;

class NotifyConnection extends Command
{
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

    /**
     * Run the command.
     *
     * @return mixed
     */
    public function run()
    {
        $everyone = $this->dispatcher()
            ->connections();

        $receiver = $everyone->uuid($this->receiver);

        $notification = new Notification($this->sender);
        $notifications = $receiver->notifications();
        $notifications->put($notification->sender(), $notification);

        return $this->dispatcher()
            ->send(new UpdateNotifications($notifications), $receiver);
    }
}
