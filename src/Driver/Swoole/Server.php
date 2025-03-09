<?php

namespace Squirtle\WebSocket\Driver\Swoole;

use Squirtle\WebSocket\Contracts\ServerInterface;
use Squirtle\WebSocket\Brokers\Contracts\PubSubManager;

use Squirtle\WebSocket\Events\Server\ConnectionClosed;
use Squirtle\WebSocket\Events\Server\ConnectionFailed;
use Squirtle\WebSocket\Events\Server\ConnectionOpened;
use Squirtle\WebSocket\Events\Server\MessageReceived;
use Squirtle\WebSocket\Events\Server\MessageSent;

use Swoole\WebSocket\Server as WebSocketServer;

use Illuminate\Support\Facades\Event;
use Exception;
use Closure;

class Server implements ServerInterface
{
    /**
     * The Swoole WebSocket server instance.
     *
     * @var WebSocketServer|null
     */
    protected ?WebSocketServer $server = null;

    /**
     * A closure to be executed when the server starts.
     *
     * @var Closure|null
     */
    protected ?Closure $onStartCallback = null;

    /**
     * A closure to be executed when a new connection is established.
     *
     * @var Closure|null
     */
    protected ?Closure $onOpenCallback = null;

    /**
     * A closure to be executed when a message is received.
     *
     * @var Closure|null
     */
    protected ?Closure $onMessageCallback = null;

    /**
     * A closure to be executed when a connection is closed.
     *
     * @var Closure|null
     */
    protected ?Closure $onCloseCallback = null;

    /**
     * A closure to be executed when an error occurs.
     *
     * @var Closure|null
     */
    protected ?Closure $onErrorCallback = null;

    /**
     * Server constructor.
     *
     * Initializes a new instance of this class with the provided
     * configuration, PubSub manager, and channel definitions.
     *
     * @param array $config The configuration settings for this server, including host, port, SSL options, and more.
     * @param PubSubManager $broker The PubSub manager instance for handling messaging (publishing and subscribing).
     * @param array $channel An array of channel definitions, e.g. ['outgoing' => 'server:outgoing', 'incoming' => 'server:incoming'].
     */
    public function __construct(protected readonly array $config, protected PubSubManager $broker, protected readonly array $channel)
    {
        // Constructor does not start the server; it only initializes dependencies.
    }

    /**
     * Starts the WebSocket server.
     *
     * Initializes the Swoole WebSocket server with the host, port, and SSL
     * settings specified in the configuration array. It attaches event listeners
     * for start, open, message, and close. If an error occurs while starting
     * the server, a ConnectionFailed event is dispatched.
     *
     * @return void
     */
    public function run(): void
    {
        // Create a new Swoole WebSocket server using the given host, port, and SSL options.
        $this->server = new WebSocketServer(
            $this->config['host'],
            $this->config['port'],
            SWOOLE_PROCESS,
            SWOOLE_SOCK_TCP | (
            isset($this->config['ssl']['cert_file']) && $this->config['ssl']['cert_file']
                ? SWOOLE_SSL
                : 0
            )
        );

        // If SSL is enabled, configure the SSL certificate and private key.
        if (!empty($this->config['ssl']['cert_file'])) {
            $this->server->set([
                'ssl_cert_file' => $this->config['ssl']['cert_file'],
                'ssl_key_file' => $this->config['ssl']['key_file'],
            ]);
        }

        // Squirtlely any additional Swoole server options from the config.
        if (!empty($this->config['options'])) {
            $this->server->set($this->config['options']);
        }

        /**
         * The "start" event is triggered when the server successfully starts.
         * Here, we invoke the onStartCallback if it has been defined.
         */
        $this->server->on('start', function ($server) {
            if ($this->onStartCallback) {
                call_user_func($this->onStartCallback, $server);
            }
        });

        /**
         * The "open" event is triggered whenever a new WebSocket handshake
         * is completed and a new connection is established. We dispatch a
         * ConnectionOpened event for our application, and then call the
         * onOpenCallback if provided.
         */
        $this->server->on('open', function ($server, $request) {
            Event::dispatch(new ConnectionOpened($server, $request));
            if ($this->onOpenCallback) {
                call_user_func($this->onOpenCallback, $request->fd);
            }
        });

        /**
         * The "message" event is triggered whenever a connected client
         * sends a message. We dispatch a MessageReceived event for our
         * application, and then call the onMessageCallback if defined.
         */
        $this->server->on('message', function ($server, $frame) {
            Event::dispatch(new MessageReceived($this->channel['outgoing'], $frame->fd, $frame->data));
            if ($this->onMessageCallback) {
                call_user_func($this->onMessageCallback, $frame->fd, $frame->data);
            }
        });

        /**
         * The "close" event is triggered when a client connection is closed.
         * We dispatch a ConnectionClosed event for our application, and then
         * call the onCloseCallback if provided.
         */
        $this->server->on('close', function ($server, $fd) {
            Event::dispatch(new ConnectionClosed($fd, 'connection is closed', 0));
            if ($this->onCloseCallback) {
                call_user_func($this->onCloseCallback, $fd);
            }
        });

        // Start the Swoole WebSocket server. If an exception is thrown,
        // dispatch a ConnectionFailed event with error details.
        try {
            $this->server->start();
        } catch (Exception $e) {
            if ($this->onErrorCallback) {
                call_user_func($this->onErrorCallback, $e->getMessage(), $e->getCode());
            }
            Event::dispatch(new ConnectionFailed($e->getMessage(), $e->getCode()));
        }
    }

    /**
     * Sends a message to a specific connection.
     *
     * @param int $fd The file descriptor (FD) of the target connection.
     * @param mixed $message The message to be sent to the client.
     *
     * @return void
     */
    public function send(int $fd, mixed $message): void
    {
        // Check if the connection is still established before attempting to push the message.
        if ($this->server->isEstablished($fd)) {
            $this->server->push($fd, $message);
            Event::dispatch(new MessageSent('', $fd, $message));
        }
    }

    /**
     * Broadcasts a message to all connected clients.
     *
     * Iterates over all active connections and sends the given message
     * to each one if it is still established. Also dispatches a
     * MessageSent event for each individual push.
     *
     * @param mixed $message The message to broadcast.
     *
     * @return void
     */
    public function broadcast(mixed $message): void
    {
        foreach ($this->server->connections as $fd) {
            if ($this->server->isEstablished($fd)) {
                $this->server->push($fd, $message);
                Event::dispatch(new MessageSent('', $fd, $message));
            }
        }
    }

    /**
     * Registers a callback to execute when the server starts.
     *
     * @param callable $callback A function that receives the server instance and request object.
     *
     * @return void
     */
    public function onStart(callable $callback): void
    {
        $this->onStartCallback = $callback;
    }

    /**
     * Registers a callback to execute when a new connection opens.
     *
     * @param callable $callback A function that receives the connection's FD as a parameter.
     *
     * @return void
     */
    public function onOpen(callable $callback): void
    {
        $this->onOpenCallback = $callback;
    }

    /**
     * Registers a callback to execute when a message is received.
     *
     * @param callable $callback A function accepting message data and the connection's FD as parameters.
     *
     * @return void
     */
    public function onMessage(callable $callback): void
    {
        $this->onMessageCallback = $callback;
    }

    /**
     * Registers a callback to execute when a connection is closed.
     *
     * @param callable $callback A function that receives the closing connection's FD.
     *
     * @return void
     */
    public function onClose(callable $callback): void
    {
        $this->onCloseCallback = $callback;
    }

    /**
     * Registers a callback to execute when an error occurs.
     *
     * @param callable $callback A function that handles errors and exceptions.
     *
     * @return void
     */
    public function onError(callable $callback): void
    {
        $this->onErrorCallback = $callback;
    }
}
