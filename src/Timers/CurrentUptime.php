<?php

namespace ArtisanSDK\Server\Timers;

use ArtisanSDK\Server\Commands\BroadcastCurrentUptime;
use ArtisanSDK\Server\Contracts\ShouldAutoStart;
use ArtisanSDK\Server\Entities\Timer;
use Carbon\Carbon;

class CurrentUptime extends Timer implements ShouldAutoStart
{
    /**
     * Setup timer command.
     *
     * @param \Carbon\Carbon $start of server
     *
     * @return self
     */
    public function __construct(Carbon $start)
    {
        $this->command(new BroadcastCurrentUptime(compact('start')))
            ->interval(1000);
    }
}
