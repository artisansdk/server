<?php

namespace ArtisanSDK\Server\Entities;

use ArtisanSDK\Server\Contracts\Manager;
use ArtisanSDK\Server\Contracts\Message as MessageInterface;
use ArtisanSDK\Server\Traits\FluentProperties;
use Illuminate\Support\Fluent;

abstract class Message extends Fluent implements MessageInterface
{
    use FluentProperties;

    protected $dispatcher;

    /**
     * Get or set the message dispatcher.
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
     * Handle the message.
     *
     * @return mixed
     */
    public function handle()
    {
    }
}
