<?php

namespace Squirtle\WebSocket;

use Squirtle\WebSocket\Console\Commands\StartWebSocketClient;
use Squirtle\WebSocket\Console\Commands\StartWebSocketServer;
use Illuminate\Support\ServiceProvider;
use Exception;

class WebSocketServiceProvider extends ServiceProvider
{
    /**
     * @throws Exception
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/websocket.php', 'websocket');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/websocket.php' => config_path('websocket.php')], 'config');


        if ($this->app->runningInConsole()) {
            $this->commands([
                StartWebSocketClient::class,
                StartWebSocketServer::class,
            ]);
        }
    }
}


