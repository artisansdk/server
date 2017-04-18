<?php

namespace ArtisanSDK\Server\Messages;

use ArtisanSDK\Server\Contracts\ServerMessage;
use ArtisanSDK\Server\Entities\Message;
use Carbon\Carbon;

class CurrentUptime extends Message implements ServerMessage
{
    /**
     * Send the current uptime.
     *
     * @param \Carbon\Carbon $start time of server
     */
    public function __construct(Carbon $start)
    {
        $now           = Carbon::now();
        $this->elapsed = $now->diffInSeconds($start);
        $this->start   = $start->timestamp;
        $this->now     = $now->timestamp;
    }
}
