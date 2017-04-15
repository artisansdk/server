<?php

namespace ArtisanSDK\Server\Timers;

use ArtisanSDK\Server\Commands\StopServer;
use ArtisanSDK\Server\Contracts\ShouldAutoStart;
use ArtisanSDK\Server\Entities\Timer;

class AutoRestartServer extends Timer implements ShouldAutoStart
{
    /**
     * Setup the timed command.
     */
    public function __construct()
    {
        $this->command(StopServer::class)
            ->interval(60 * 60 * 1000);
    }
}
