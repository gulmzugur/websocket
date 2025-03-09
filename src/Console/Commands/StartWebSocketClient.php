<?php

namespace Squirtle\WebSocket\Console\Commands;

use Squirtle\WebSocket\Factories\BrokerFactory;
use Squirtle\WebSocket\Factories\ClientFactory;
use Squirtle\WebSocket\Events\Client\MessageSent;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Exception;

class StartWebSocketClient extends Command
{
    /**
     * Command signature for calling the command via artisan
     *
     * @var string
     */
    protected $signature = 'websocket:client
        {--broker= : Specify the broker to use (e.g., redis, mqtt, etc.)}
        {--driver= : Define the client driver to be used}
        {--host= : Set the IP address for binding the client}
        {--port= : Set the port number for the client to listen on}
        {--debug : Enable debug mode to display additional log messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts the WebSocket client';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        // Retrieve the broker configuration from options or fallback to default config.
        $broker = BrokerFactory::create($this->option('broker') ?: config('websocket.broker.driver'));

        // Retrieve the client configuration from options or fallback to default config.
        $config = config('websocket.client');
        $config['driver'] = $this->option('driver') ?: config('websocket.client.driver');

        // Override client configuration if host or port options are provided.
        if ($host = $this->option('host')) {
            $config[$config['driver']]['host'] = $host;
        }
        if ($port = $this->option('port')) {
            $config[$config['driver']]['port'] = $port;
        }
        $client = ClientFactory::create($config, $broker);

        $this->info('WebSocket Client Starting...');

        // Register a callback for when the connection is successfully opened.
        $client->onOpen(function ($host, $port) {
            $this->comment("ws://{$host}:{$port} connected, listening (CTRL+C to stop)");
        });

        // Register a callback for when a message is received.
        $client->onMessage(function ($channel, $message, $opcode) {
            if ($this->option('debug')) {
                $this->info("Received message: {$message}");
            }
        });

        // Register a callback for when the connection is closed.
        $client->onClose(function ($failure, $code) {
            if ($this->option('debug')) {
                $this->warn('Connection closed! Failure: ' . $failure . ' | Code: ' . $code);
            }
        });

        // Register a callback for when an error occurs during connection.
        $client->onError(function ($failure, $code) {
            if ($this->option('debug')) {
                $this->error('Connection failed! Failure: ' . $failure . ' | Code: ' . $code);
            }
        });

        // Listen for outgoing message events and log the sent messages.
        Event::listen(MessageSent::class, function ($event) {
            if ($this->option('debug')) {
                $this->info("Sent message: {$event->message}");
            }
        });

        // Start the WebSocket client and handle potential exceptions.
        $client->start();
    }
}
