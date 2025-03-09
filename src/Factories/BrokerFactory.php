<?php

namespace Squirtle\WebSocket\Factories;

use Squirtle\WebSocket\Brokers\Contracts\PubSubManager;
use Squirtle\WebSocket\Brokers\Redis;
use Exception;

class BrokerFactory
{
    /**
     * Returns the Broker Interface implementation based on the broker settings in Config.
     *
     * @param string $driver
     * @return PubSubManager
     * @throws Exception
     */
    public static function create(string $driver): PubSubManager
    {
        $config = config('websocket.broker.' . $driver);

        return match ($driver) {
            'redis' => new Redis($config),
            default => throw new Exception("Unsupported broker driver: $driver"),
        };
    }
}
