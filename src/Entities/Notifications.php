<?php

namespace ArtisanSDK\Server\Entities;

use ArtisanSDK\Server\Traits\UUIDFilter;
use Illuminate\Support\Collection;

class Notifications extends Collection
{
    use UUIDFilter;
}
