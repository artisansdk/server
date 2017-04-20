<?php

namespace ArtisanSDK\Server\Contracts;

use ArtisanSDK\Server\Entities\Topics;
use Illuminate\Contracts\Support\Jsonable;

interface Message extends Jsonable
{
    /**
     * Get or set the message dispatcher.
     *
     * @param \ArtisanSDK\Server\Contracts\Manager $instance for the server
     *
     * @return \ArtisanSDK\Server\Contracts\Manager|self
     */
    public function dispatcher(Manager $instance = null);

    /**
     * Get or set the topics this message should be published to.
     *
     * @example topics() ==> \ArtisanSDK\Server\Entities\Topics
     *          topics($topics) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Topics $topics
     *
     * @return \ArtisanSDK\Server\Entities\Topics|self
     */
    public function topics(Topics $topics = null);
}
