<?php

namespace Squirtle\WebSocket\Factories;

use Squirtle\WebSocket\Brokers\Contracts\PubSubManager;
use Squirtle\WebSocket\Contracts\ServerInterface;
use Squirtle\WebSocket\Driver\Swoole\Server as SwooleServer;
use Exception;

class ServerFactory
{
    /**
     * Returns the ServerInterface implementation according to the server settings in Config and the broker object.
     *
     * @param array $config
     * @param PubSubManager $broker
     * @return ServerInterface
     * @throws Exception
     */
    public static function create(array $config, PubSubManager $broker): ServerInterface
    {
        $driver = strtolower($config['driver'] ?? 'swoole');

        return match ($driver) {
            'swoole' => new SwooleServer($config['swoole'], $broker, $config['channel']),
            default => throw new Exception("Unsupported WebSocket server driver: $driver"),
        };
    }
}
