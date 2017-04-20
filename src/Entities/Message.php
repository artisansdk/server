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
     * Get or set the topics available for subscribing.
     *
     * @example topics() ==> \ArtisanSDK\Server\Entities\Topics
     *          topics($topics) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Topics $topics
     *
     * @return \ArtisanSDK\Server\Entities\Topics|self
     */
    public function topics(Topics $topics = null)
    {
        return $this->property(__FUNCTION__, $topics);
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
