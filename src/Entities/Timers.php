<?php

namespace ArtisanSDK\Server\Entities;

use ArtisanSDK\Server\Contracts\ShouldAutoStart;
use ArtisanSDK\Server\Contracts\Timer as TimerInterface;
use Illuminate\Support\Collection;

class Timers extends Collection
{
    /**
     * Add a timer to the collection.
     *
     * @param ArtisanSDK\Server\Contracts\Timer $timer
     *
     * @return self
     */
    public function add(TimerInterface $timer)
    {
        $this->push($timer);

        if ($timer instanceof ShouldAutoStart) {
            $timer->start();
        }

        return $this;
    }

    /**
     * Remove a timer from the collection.
     *
     * @param ArtisanSDK\Server\Contracts\Timer $timer
     *
     * @return self
     */
    public function remove(TimerInterface $timer)
    {
        $timer->stop();

        $index = array_search($timer, $this->items, $strict = true);
        if ($index === false) {
            $this->offsetUnset($index);
        }

        return $this;
    }

    /**
     * Filter timers to those that are active.
     *
     * @return self
     */
    public function active()
    {
        return $this->where(function ($timer) {
            return $timer->started() && ! $timer->paused();
        });
    }

    /**
     * Filter timers to those that are inactive.
     *
     * @return self
     */
    public function inactive()
    {
        return $this->where(function ($timer) {
            return ! $timer->started() || $timer->paused();
        });
    }

    /**
     * Filter timers to those that are paused.
     *
     * @return self
     */
    public function paused()
    {
        return $this->where(function ($timer) {
            return $timer->paused();
        });
    }

    /**
     * Filter timers to those that are not paused.
     *
     * @return self
     */
    public function unpaused()
    {
        return $this->where(function ($timer) {
            return ! $timer->paused();
        });
    }
}
