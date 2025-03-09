<?php

namespace Squirtle\WebSocket\Brokers\Contracts;

interface PubSubManager
{
    /**
     * Establishes and returns a connection resource or object.
     *
     * @param bool $forSubscribe Indicates whether the connection will be used for subscribing.
     *                           If true, the connection should be optimized or prepared for subscriptions.
     * @return mixed The connection resource or object specific to the implementation.
     */
    public function connection(bool $forSubscribe = false): mixed;

    /**
     * Publishes a message to a given channel.
     *
     * @param string $channel The channel to which the message will be published.
     * @param string $message The message to be published.
     * @return void
     */
    public function publish(string $channel, string $message): void;

    /**
     * Subscribes to a given channel and triggers a callback when messages are received.
     *
     * @param string $channel The channel to subscribe to.
     * @param callable $callback The function to be called when a new message is received.
     * @return void
     */
    public function subscribe(string $channel, callable $callback): void;
}
