<?php

namespace Squirtle\WebSocket\Factories;

use Squirtle\WebSocket\Brokers\Contracts\PubSubManager;
use Squirtle\WebSocket\Contracts\ClientInterface;
use Squirtle\WebSocket\Driver\Swoole\Client as SwooleClient;
use Exception;

class ClientFactory
{
    /**
     * Returns the ClientInterface implementation based on the client settings in Config and the broker object.
     *
     * @param array $config
     * @param PubSubManager $broker
     * @return ClientInterface
     * @throws Exception
     */
    public static function create(array $config, PubSubManager $broker): ClientInterface
    {
        $driver = strtolower($config['driver'] ?? 'swoole');

        return match ($driver) {
            'swoole' => new SwooleClient($config['swoole'], $broker, $config['channel']),
            default => throw new Exception("Unsupported WebSocket client driver: $driver"),
        };
    }
}
