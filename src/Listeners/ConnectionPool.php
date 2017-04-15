<?php

namespace ArtisanSDK\Server\Listeners;

use ArtisanSDK\Server\Commands\CloseConnections;
use ArtisanSDK\Server\Entities\Listener as BaseListener;

class ConnectionPool extends BaseListener
{
    /**
     * Initialize any registered message handlers upon construction.
     *
     * @return self
     */
    public function boot()
    {
        $this->register(\ArtisanSDK\Server\Messages\DisconnectAll::class, CloseConnections::class);
        $this->register(\ArtisanSDK\Server\Messages\DisconnectPlayers::class, CloseConnections::class);
        $this->register(\ArtisanSDK\Server\Messages\DisconnectSpectators::class, CloseConnections::class);

        return $this;
    }
}
