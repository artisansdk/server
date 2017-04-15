<?php

namespace ArtisanSDK\Server\Contracts;

use ArtisanSDK\Server\Entities\Connections;

interface Topic
{
    /**
     * Get or set the UUID of the topic.
     *
     * @example uuid() ==> string
     *          uuid($uuid) ==> self
     *
     * @param string $uuid
     *
     * @return string|self
     */
    public function uuid($uuid = null);

    /**
     * Get or set the name registered for the topic.
     *
     * @example name() ==> string
     *          name($name) ==> self
     *
     * @param string $name
     *
     * @return string|self
     */
    public function name($name = null);

    /**
     * Get or set the connections the topic has subscriptions for.
     *
     * @example connections() ==> \ArtisanSDK\Server\Entities\Connections
     *          connections($connections) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Connections $connections
     *
     * @return \ArtisanSDK\Server\Entities\Connections|self
     */
    public function subscriptions(Connections $connections = null);

    /**
     * Add a connection to the topic's subscriptions.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to subscribe.
     *
     * @return self
     */
    public function subscribe(Connection $connection);

    /**
     * Remove a connection from the topic's subscriptions.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to unsubscribe.
     *
     * @return self
     */
    public function unsubscribe(Connection $connection);
}
