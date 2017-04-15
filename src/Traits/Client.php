<?php

namespace ArtisanSDK\Server\Traits;

use ArtisanSDK\Server\Contracts\Connection;

trait Client
{
    protected $client;

    /**
     * Get or set the client connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection of the client
     *
     * @return \ArtisanSDK\Server\Contracts\Connection|self
     */
    public function client(Connection $connection = null)
    {
        return $this->property(__FUNCTION__, $connection);
    }
}
