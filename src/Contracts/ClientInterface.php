<?php

namespace Squirtle\WebSocket\Contracts;

interface ClientInterface
{
    /**
     * Initializes the client or underlying resources for operation.
     *
     * @return void
     */
    public function initialize(): void;

    /**
     * Starts the WebSocket client.
     *
     * Establishes and maintains a persistent WebSocket connection.
     * If the connection fails or closes, the implementation should
     * dispatch the appropriate events and potentially retry the
     * connection.
     *
     * @return void
     */
    public function start(): void;

    /**
     * Registers a callback to execute when a new connection opens.
     *
     * @param callable $callback A function invoked upon successful connection, typically receiving connection identifiers or resources.
     *
     * @return void
     */
    public function onOpen(callable $callback): void;

    /**
     * Registers a callback to be executed when a message is sent.
     *
     * @param callable $callback A function that accepts the current client connection as a parameter and handles sending messages.
     *
     * @return void
     */
    public function send(callable $callback): void;

    /**
     * Registers a callback to be executed when a message is received.
     *
     * @param callable $callback A function accepting two parameters: the received message and its opcode.
     *
     * @return void
     */
    public function onMessage(callable $callback): void;

    /**
     * Registers a callback to be executed when the connection is closed.
     *
     * @param callable $callback A function that handles cleanup or other tasks when the client disconnects.
     *
     * @return void
     */
    public function onClose(callable $callback): void;

    /**
     * Registers a callback to be executed when an error occurs.
     *
     * @param callable $callback A function that processes error details.
     *
     * @return void
     */
    public function onError(callable $callback): void;
}
