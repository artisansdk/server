<?php

namespace ArtisanSDK\Server\Listeners;

use ArtisanSDK\Server\Entities\Listener as BaseListener;

class ServerAdmin extends BaseListener
{
    /**
     * Initialize any registered message handlers upon construction.
     *
     * @return self
     */
    public function boot()
    {
        $this->register(\ArtisanSDK\Server\Messages\StopServer::class, \ArtisanSDK\Server\Commands\StopServer::class);

        return $this;
    }
}
