<?php

namespace ArtisanSDK\Server\Entities;

use ArtisanSDK\Server\Contracts\Command as CommandInterface;
use Illuminate\Support\Collection;

class Commands extends Collection
{
    /**
     * Add a command to the collection.
     *
     * @param ArtisanSDK\Server\Contracts\Command $command
     *
     * @return self
     */
    public function add(CommandInterface $command)
    {
        $this->push($command);

        return $this;
    }

    /**
     * Remove a command from the collection.
     *
     * @param ArtisanSDK\Server\Contracts\Command $command
     *
     * @return self
     */
    public function remove(CommandInterface $command)
    {
        $index = array_search($command, $this->items, $strict = true);
        if ($index === false) {
            $this->offsetUnset($index);
        }

        return $this;
    }
}
