<?php
return [
    'client' => [
        'driver' => env('WS_CLIENT_DRIVER', 'swoole'),
        'swoole' => [
            'host' => env('WS_CLIENT_HOST', '0.0.0.0'),
            'port' => env('WS_CLIENT_PORT', 6001),
            'path' => env('WS_CLIENT_PATH', '/'),
            'ssl' => env('WS_CLIENT_SSL', false),
            'options' => [],
        ],
        'channel' => [
            'outgoing' => 'client:outgoing',
            'incoming' => 'client:incoming',
        ],
    ],
    'server' => [
        'driver' => env('WS_SERVER_DRIVER', 'swoole'),
        'swoole' => [
            'host' => env('WS_SERVER_HOST', '0.0.0.0'),
            'port' => env('WS_SERVER_PORT', 6001),
            'ssl' => [
                'cert_file' => env('WS_SERVER_SSL_CERT_FILE', ''),
                'key_file' => env('WS_SERVER_SSL_KEY_FILE', ''),
            ],
            'options' => [],
        ],
        'channel' => [
            'outgoing' => 'server:outgoing',
            'incoming' => 'server:incoming',
        ],
    ],
    'broker' => [
        'driver' => env('WS_BROKER_DRIVER', 'redis'),
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'options' => [
                'username' => env('REDIS_USERNAME', null),
                'password' => env('REDIS_PASSWORD', null),
                'prefix' => 'websocket:',
                'timeout' => 0,
            ]
        ],
    ],
];
