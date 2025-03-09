<?php

namespace Squirtle\WebSocket\Console\Commands;

use Squirtle\WebSocket\Events\Server\MessageSent;
use Squirtle\WebSocket\Factories\BrokerFactory;
use Squirtle\WebSocket\Factories\ServerFactory;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class StartWebSocketServer extends Command
{
    /**
     * Command signature for calling the command via artisan
     *
     * @var string
     */
    protected $signature = 'websocket:server
        {--broker= : Specify the broker to use (e.g., redis, mqtt, etc.)}
        {--driver= : Define the client driver to be used}
        {--host= : Set the IP address for binding the client}
        {--port= : Set the port number for the client to listen on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the WebSocket server';

    /**
     * Execute the command to start the WebSocket server.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        // Create broker instance using CLI option or default configuration.
        $broker = BrokerFactory::create(
            $this->option('broker') ?: config('websocket.broker.driver')
        );

        // Retrieve server configuration and set the driver.
        $config = config('websocket.server');
        $config['driver'] = $this->option('driver') ?: config('websocket.server.driver');

        // Override host and port if specified via CLI options.
        if ($host = $this->option('host')) {
            $config[$config['driver']]['host'] = $host;
        }
        if ($port = $this->option('port')) {
            $config[$config['driver']]['port'] = $port;
        }

        // Create server instance using the configuration and broker.
        $server = ServerFactory::create($config, $broker);

        $this->info('WebSocket Server Starting...');

        // Set callback for when the server starts.
        $server->onStart(function ($server) {
            $this->comment("WebSocket Server started at ws://{$server->host}:{$server->port} (CTRL+C to stop)");
        });

        // Set callback for new connections.
        $server->onOpen(function ($fd) {
            $this->info("Connection opened: $fd");
        });

        // Set callback for incoming messages.
        $server->onMessage(function ($fd, $message) use ($broker, $config) {
            $this->info("Received message from fd {$fd}: $message");
        });

        // Set callback for closed connections.
        $server->onClose(function ($fd) {
            $this->info("Connection closed: $fd");
        });

        // Set callback for server errors.
        $server->onError(function ($failure, $code) {
            $this->info("WebSocket server failed to start: {$failure} {$code}");
        });

        // Listen for MessageSent events.
        Event::listen(MessageSent::class, function ($event) use ($server) {
            $this->info('Sent message: ' . $event->fd . '->' . $event->message);
        });

        // Attempt to start the server.
        try {
            $server->run();
        } catch (Exception $e) {
            $this->error("WebSocket server failed to start: " . $e->getMessage());
        }
    }
}
