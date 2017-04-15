<?php

namespace ArtisanSDK\Server\Timers;

use ArtisanSDK\Server\Commands\GetJob;
use ArtisanSDK\Server\Contracts\ShouldAutoStart;
use ArtisanSDK\Server\Entities\Timer;

class QueueWorker extends Timer implements ShouldAutoStart
{
    /**
     * Setup the timed command.
     */
    public function __construct()
    {
        $this->command(GetJob::class)
            ->interval(100);
    }
}
