<?php

namespace ArtisanSDK\Server\Timers;

use ArtisanSDK\Server\Contracts\ShouldAutoStart;
use ArtisanSDK\Server\Entities\Timer;

class DelayedCommand extends Timer implements ShouldAutoStart
{
}
