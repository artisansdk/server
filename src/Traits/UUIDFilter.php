<?php

namespace ArtisanSDK\Server\Traits;

trait UUIDFilter
{
    /**
     * Get the first entity that matches the UUID.
     *
     * @param string $uuid
     *
     * @return \ArtisanSDK\Server\Contracts\Notification|null
     */
    public function uuid($uuid)
    {
        return $this->first(function ($entity) use ($uuid) {
            return $entity->uuid() === $uuid;
        });
    }
}
