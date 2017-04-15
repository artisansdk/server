<?php

namespace ArtisanSDK\Server\Entities;

use ArtisanSDK\Server\Contracts\Topic;
use ArtisanSDK\Server\Traits\UUIDFilter;
use Illuminate\Support\Collection;

class Topics extends Collection
{
    use UUIDFilter;

    /**
     * Add a topic to the collection.
     *
     * @param ArtisanSDK\Server\Contracts\Topic $topic
     *
     * @return self
     */
    public function add(Topic $topic)
    {
        $this->put($topic->uuid(), $topic);

        return $this;
    }

    /**
     * Remove a topic from the collection.
     *
     * @param ArtisanSDK\Server\Contracts\Topic $topic
     *
     * @return self
     */
    public function remove(Topic $topic)
    {
        $this->forget($topic->uuid(), $topic);

        return $this;
    }
}
