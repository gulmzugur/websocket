<?php

namespace Squirtle\WebSocket\Brokers;

use RedisException;
use Squirtle\WebSocket\Brokers\Contracts\PubSubManager;
use Redis as RedisClient;

class Redis implements PubSubManager
{
    /**
     * The Redis client used for publishing messages.
     *
     * @var RedisClient
     */
    protected RedisClient $publisher;
    
    /**
     * The Redis client used for subscribing to channels.
     *
     * @var RedisClient
     */
    protected RedisClient $subscriber;

    /**
     * Redis constructor.
     *
     * Initializes two Redis connections:
     * one for publishing and one for subscribing.
     *
     * @param array $config The configuration array for connecting to Redis.
     *
     * @throws RedisException If the connection or authentication fails.
     */
    public function __construct(protected readonly array $config)
    {
        $this->publisher = $this->connection();
        $this->subscriber = $this->connection(true);
    }

    /**
     * Creates and returns a Redis client connection.
     *
     * Depending on the $forSubscribe flag, the connection is
     * configured for either publishing or subscribing.
     *
     * @param bool $forSubscribe Whether the connection is intended for subscribing. If true, the read timeout is disabled.
     *
     * @return RedisClient The configured Redis client instance.
     *
     * @throws RedisException If connection or authentication fails.
     */
    public function connection(bool $forSubscribe = false): RedisClient
    {
        $redis = new RedisClient();
        $redis->connect($this->config['host'], $this->config['port']);

        if (!empty($this->config['options']['username']) && !empty($this->config['options']['password'])) {
            $redis->auth([$this->config['options']['username'], $this->config['options']['password']]);
        } elseif (!empty($this->config['options']['password'])) {
            $redis->auth($this->config['options']['password']);
        }

        if (!empty($this->config['options']['prefix'])) {
            $redis->setOption(RedisClient::OPT_PREFIX, $this->config['options']['prefix']);
        }

        if ($forSubscribe) {
            $redis->setOption(RedisClient::OPT_READ_TIMEOUT, -1);
        } else {
            if (!empty($this->config['options']['timeout'])) {
                $redis->setOption(RedisClient::OPT_READ_TIMEOUT, $this->config['options']['timeout']);
            }
        }

        return $redis;
    }

    /**
     * Publishes a message to a specified Redis channel.
     *
     * @param string $channel The name of the Redis channel.
     * @param string $message The message content to publish.
     *
     * @return void
     * @throws RedisException If publishing the message fails.
     */
    public function publish(string $channel, string $message): void
    {
        $this->publisher->publish($channel, $message);
    }

    /**
     * Subscribes to a given Redis channel and invokes the specified callback
     * whenever a message is published to that channel.
     *
     * @param string $channel The name of the channel to subscribe to.
     * @param callable $callback The function to call when a message is received, receiving parameters (Redis $redis, string $channel, string $message).
     *
     * @return void
     * @throws RedisException If subscribing fails.
     */
    public function subscribe(string $channel, callable $callback): void
    {
        $this->subscriber->subscribe([$channel], $callback);
    }
}
