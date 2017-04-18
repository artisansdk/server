<?php

namespace ArtisanSDK\Server\Contracts;

use ArtisanSDK\Server\Entities\Topics;
use Carbon\Carbon;
use Ratchet\ConnectionInterface;

interface Connection extends ConnectionInterface
{
    const ANONYMOUS = 'anonymous';
    const PLAYER    = 'player';
    const SPECTATOR = 'spectator';
    const WINNER    = 'winner';
    const LOSER     = 'loser';

    /**
     * Get or set the socket for the connection.
     *
     * @example socket() ==> \Ratchet\ConnectionInterface
     *          socket($interface) ==> self
     *
     * @param \Ratchet\ConnectionInterface $instance
     *
     * @return \Ratchet\ConnectionInterface|self
     */
    public function socket(ConnectionInterface $interface = null);

    /**
     * Get or set the timestamp when the connection was opened.
     *
     * @example timestamp() ==> \Carbon\Carbon
     *          timestamp($timestamp) ==> self
     *
     * @param \Carbon\Carbon $timestamp
     *
     * @return \Carbon\Carbon|self
     */
    public function timestamp(Carbon $timestamp = null);

    /**
     * Send data to the connection.
     *
     * @param string $data
     *
     * @return \ArtisanSDK\Server\Contracts\Connection
     */
    public function send($data);

    /**
     * Close the connection.
     */
    public function close();

    /**
     * Get or set the UUID of the connection.
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
     * Get or set the type of connection.
     *
     * @example type() ==> string
     *          type($type) ==> self
     *
     * @param string $type
     *
     * @return string|self
     */
    public function type($type = null);

    /**
     * Get or set the email registered for the connection.
     *
     * @example email() ==> string
     *          email($email) ==> self
     *
     * @param string $email
     *
     * @return string|self
     */
    public function email($email = null);

    /**
     * Get or set the name registered for the connection.
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
     * Get or set the client IP Address for the connection.
     *
     * @example ipAddress() ==> string
     *          ipAddress($name) ==> self
     *
     * @param string $ipAddress
     *
     * @return string|self
     */
    public function ipAddress($ipAddress = null);

    /**
     * Get or set that the connection is admin privileged.
     *
     * @example admin() ==> true
     *          admin(true) ==> self
     *
     * @param bool $privileged
     *
     * @return bool|self
     */
    public function admin($privileged = null);

    /**
     * Get or set the topics the connection subscribes to.
     *
     * @example subscriptions() ==> \ArtisanSDK\Server\Entities\Topics
     *          subscriptions($topics) ==> self
     *
     * @param \ArtisanSDK\Server\Entities\Topics $topics
     *
     * @return \ArtisanSDK\Server\Entities\Topics|self
     */
    public function subscriptions(Topics $topics = null);

    /**
     * Add a topic that the connection subscribes to.
     *
     * @param \ArtisanSDK\Server\Contracts\Topic $topic to subscribe to
     *
     * @return self
     */
    public function subscribe(Topic $topic);

    /**
     * Remove a topic that the connection is subscribed to.
     *
     * @param \ArtisanSDK\Server\Contracts\Topic $topic to unsubscribe from
     *
     * @return self
     */
    public function unsubscribe(Topic $topic);
}
