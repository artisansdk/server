<?php

namespace ArtisanSDK\Server\Traits;

trait ClientProtection
{
    use Client;

    /**
     * Authorize the client connection.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->client()->admin()
            || $this->client()->uuid() === $this->uuid;
    }
}
