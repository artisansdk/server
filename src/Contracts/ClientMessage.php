<?php

namespace ArtisanSDK\Server\Contracts;

interface ClientMessage extends Message
{
    /**
     * Authorize the client connection.
     *
     * @return bool
     */
    public function authorize();

    /**
     * Get or set the client connection.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection of the client
     *
     * @return \ArtisanSDK\Server\Contracts\Connection|self
     */
    public function client(Connection $connection = null);
}
