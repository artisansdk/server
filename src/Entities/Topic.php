<?php

namespace ArtisanSDK\Server\Entities;

use ArtisanSDK\Server\Contracts\Connection as ConnectionInterface;
use ArtisanSDK\Server\Contracts\Topic as TopicInterface;
use ArtisanSDK\Server\Traits\FluentProperties;
use ArtisanSDK\Server\Traits\JsonHelpers;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

class Topic implements TopicInterface, Arrayable, Jsonable, JsonSerializable
{
    use FluentProperties, JsonHelpers;

    protected $name;
    protected $subscriptions;
    protected $uuid;

    /**
     * Instantiate the topic with the name.
     *
     * @param string $name    of topic
     * @param string $sponsor of topic
     */
    public function __construct($name, $sponsor)
    {
        $this->uuid(Uuid::uuid4()->toString());
        $this->name($name);
        $this->subscriptions(new Connections());
    }

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
    public function uuid($uuid = null)
    {
        return $this->property(__FUNCTION__, $uuid);
    }

    /**
     * Get or set the name of the topic.
     *
     * @example name() ==> string
     *          name($name) ==> self
     *
     * @param string $name
     *
     * @return string|self
     */
    public function name($name = null)
    {
        return $this->property(__FUNCTION__, $name);
    }

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
    public function subscriptions(Connections $connections = null)
    {
        return $this->property(__FUNCTION__, $connections);
    }

    /**
     * Add a connection to the topic's subscriptions.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to subscribe
     *
     * @return self
     */
    public function subscribe(ConnectionInterface $connection)
    {
        $this->subscriptions()->put($connection->uuid(), $connection);

        return $this;
    }

    /**
     * Remove a connection from the topic's subscriptions.
     *
     * @param \ArtisanSDK\Server\Contracts\Connection $connection to unsubscribe
     *
     * @return self
     */
    public function unsubscribe(ConnectionInterface $connection)
    {
        $this->subscriptions()->forget($connection->uuid());

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_filter([
            'name'          => $this->name(),
            'subscriptions' => $this->subscriptions()->toArray(),
            'uuid'          => $this->uuid(),
        ]);
    }
}
