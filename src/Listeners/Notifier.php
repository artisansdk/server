<?php

namespace ArtisanSDK\Server\Listeners;

use ArtisanSDK\Server\Entities\Listener as BaseListener;

class Notifier extends BaseListener
{
    /**
     * Initialize any registered message handlers upon construction.
     *
     * @return self
     */
    public function boot()
    {
        $this->register(\ArtisanSDK\Server\Messages\DismissNotifications::class, \ArtisanSDK\Server\Commands\DismissNotifications::class);
        $this->register(\ArtisanSDK\Server\Messages\NotifyConnection::class, \ArtisanSDK\Server\Commands\NotifyConnection::class);

        return $this;
    }
}
