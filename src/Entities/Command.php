<?php

namespace ArtisanSDK\Server\Entities;

use ArtisanSDK\Server\Contracts\Command as CommandInterface;
use ArtisanSDK\Server\Contracts\Manager;
use ArtisanSDK\Server\Traits\FluentProperties;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Fluent;

abstract class Command extends Fluent implements CommandInterface, ShouldQueue
{
    use FluentProperties, Queueable;

    protected $dispatcher;

    /**
     * Save the command arguments for later when the command is run.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->delay(0);
    }

    /**
     * Get or set the command dispatcher.
     *
     * @param \ArtisanSDK\Server\Contracts\Manager $instance for the server
     *
     * @return \ArtisanSDK\Server\Contracts\Manager|self
     */
    public function dispatcher(Manager $instance = null)
    {
        return $this->property(__FUNCTION__, $instance);
    }

    /**
     * Get or set the delay in milliseconds for the command to be executed.
     *
     * @param int $delay in milliseconds
     *
     * @return int|self
     */
    public function delay($delay = null)
    {
        return $this->property('delay', $delay);
    }
}
