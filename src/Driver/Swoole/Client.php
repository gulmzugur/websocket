<?php

namespace Squirtle\WebSocket\Driver\Swoole;

use Squirtle\WebSocket\Contracts\ClientInterface;
use Squirtle\WebSocket\Brokers\Contracts\PubSubManager;

use Squirtle\WebSocket\Events\Client\ConnectionOpened;
use Squirtle\WebSocket\Events\Client\ConnectionClosed;
use Squirtle\WebSocket\Events\Client\ConnectionFailed;
use Squirtle\WebSocket\Events\Client\MessageReceived;
use Squirtle\WebSocket\Events\Client\MessageSent;

use Swoole\Coroutine\Http\Client as WebSocketClient;
use Swoole\Coroutine;

use Illuminate\Support\Facades\Event;
use Exception;
use Closure;

class Client implements ClientInterface
{
    protected ?WebSocketClient $client = null;
    protected ?Closure $onOpenCallback = null;
    protected ?Closure $sendCallback = null;
    protected ?Closure $onMessageCallback = null;
    protected ?Closure $onCloseCallback = null;
    protected ?Closure $onErrorCallback = null;
    protected ?int $senderCid = null;
    protected ?int $receiverCid = null;

    /**
     * Constructs a new instance of the class with the specified configuration and PubSub broker.
     *
     * @param array $config The configuration settings for the instance.
     * @param PubSubManager $broker The PubSub manager instance for handling messaging.
     * @param array $channel An array containing channel names, e.g., ['outgoing' => 'client:outgoing', 'incoming' => 'client:incoming'].
     */
    public function __construct(protected readonly array $config, protected PubSubManager $broker, protected readonly array $channel)
    {
        $this->initialize();
    }

    /**
     * Initializes the object or system for operation.
     */
    public function initialize(): void
    {
        $this->client = new WebSocketClient(
            $this->config['host'],
            $this->config['port'],
            $this->config['ssl'] ?? false
        );

        if (!empty($this->config['options'])) {
            $this->client->set($this->config['options']);
        }
    }

    /**
     * Starts the WebSocket client.
     *
     * Establishes and maintains a persistent WebSocket connection.
     * If connection fails or closes, it dispatches the appropriate events and retries.
     */
    public function start(): void
    {
        while (true) {
            Coroutine\run(function () {

                // Attempt to upgrade the connection (perform the WebSocket handshake)
                if (!$this->client->upgrade($this->config['path'] ?? '/')) {
                    Event::dispatch(new ConnectionFailed($this->client->errMsg, $this->client->errCode));
                    if ($this->onErrorCallback) {
                        call_user_func($this->onErrorCallback, $this->client->errMsg, $this->client->errCode);
                    }
                    if (isset($this->senderCid)) {
                        Coroutine::cancel($this->senderCid);
                    }
                    if (isset($this->receiverCid)) {
                        Coroutine::cancel($this->receiverCid);
                    }
                    Coroutine::sleep(3);
                    return;
                }

                if ($this->client->connected) {
                    // Dispatch a ConnectionOpened event when the handshake succeeds
                    Event::dispatch(new ConnectionOpened());

                    // Calls the registered onOpen callback with the current client, if set.
                    if ($this->onOpenCallback) {
                        call_user_func($this->onOpenCallback, $this->config['host'], $this->config['port']);
                    }

                    // Calls the registered onSend callback with the current client, if set.
                    if ($this->sendCallback) {
                        call_user_func($this->sendCallback, $this->client);
                    }
                }

                // Create a coroutine to continuously listen for outgoing WebSocket messages
                $this->senderCid = Coroutine::create(function () {
                    try {
                        $broker = $this->broker->connection(true);
                        $broker->subscribe([$this->channel['outgoing']], function ($redis, $channel, $message) {
                            if ($this->client->connected) {
                                Event::dispatch(new MessageSent($channel, $message));
                                $this->client->push($message, WEBSOCKET_OPCODE_TEXT);
                            }
                        });
                    } catch (Exception $e) {
                        Event::dispatch(new ConnectionFailed($e->getMessage(), $e->getCode()));
                        return;
                    }
                });

                // Create a coroutine to continuously listen for incoming WebSocket frames
                $this->receiverCid = Coroutine::create(function () {
                    while (true) {
                        // Wait for a frame with a timeout of -1 seconds
                        $frame = $this->client->recv(-1);

                        // If the frame is false or null, assume the connection has been closed or an error occurred
                        if ($frame === false || $frame === null || $frame->opcode === WEBSOCKET_OPCODE_CLOSE) {
                            Event::dispatch(new ConnectionClosed($this->client->errMsg, $this->client->errCode));
                            if ($this->onCloseCallback) {
                                call_user_func($this->onCloseCallback, $this->client->errMsg, $this->client->errCode);
                            }
                            if (isset($this->senderCid)) {
                                Coroutine::cancel($this->senderCid);
                            }
                            if (isset($this->receiverCid)) {
                                Coroutine::cancel($this->receiverCid);
                            }
                            return;
                        }

                        // If a valid frame with data is received, process the message
                        if (isset($frame->data) && $frame->data !== '' && $frame->opcode == WEBSOCKET_OPCODE_TEXT) {
                            try {
                                Event::dispatch(new MessageReceived($this->channel['incoming'], $frame->data));
                                if ($this->onMessageCallback) {
                                    call_user_func($this->onMessageCallback, $this->channel['incoming'], $frame->data, $frame->opcode);
                                }
                                $this->broker->publish($this->channel['incoming'], $frame->data);
                            } catch (Exception $e) {
                                Event::dispatch(new ConnectionFailed($e->getMessage(), $e->getCode()));
                                return;
                            }
                        }
                    }
                });

                // Wait for a short period before attempting to reconnect
                Coroutine::sleep(3);
            });
        }
    }

    /**
     * Registers a callback to execute when a new connection opens.
     *
     * @param callable $callback Function accepting the connection host, port.
     */
    public function onOpen(callable $callback): void
    {
        $this->onOpenCallback = $callback;
    }

    /**
     * Registers a callback to be executed when a message is sent.
     *
     * @param callable $callback Function that accepts the current client connection.
     */
    public function send(callable $callback): void
    {
        $this->sendCallback = $callback;
    }

    /**
     * Registers a callback to be executed when a message is received.
     *
     * @param callable $callback Function accepting two parameters: message and opcode.
     */
    public function onMessage(callable $callback): void
    {
        $this->onMessageCallback = $callback;
    }

    /**
     * Registers a callback to be executed when the connection is closed.
     *
     * @param callable $callback
     */
    public function onClose(callable $callback): void
    {
        $this->onCloseCallback = $callback;
    }

    /**
     * Registers a callback to be executed when the connection is fail.
     *
     * @param callable $callback
     */
    public function onError(callable $callback): void
    {
        $this->onErrorCallback = $callback;
    }
}
