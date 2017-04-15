<?php

namespace ArtisanSDK\Server\Contracts;

use Illuminate\Contracts\Support\Jsonable;

interface Message extends Jsonable
{
    /**
     * Get or set the message dispatcher.
     *
     * @param \ArtisanSDK\Server\Contracts\Manager $instance for the server
     *
     * @return \ArtisanSDK\Server\Contracts\Manager|self
     */
    public function dispatcher(Manager $instance = null);
}
