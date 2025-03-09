<?php

namespace Squirtle\WebSocket\Contracts;

interface ServerInterface
{
    /**
     * Starts the WebSocket server.
     *
     * This method should initialize the underlying server with
     * the specified host, port, SSL options, and any additional
     * settings. It also sets up the event handlers to manage
     * the server lifecycle (onStart, onOpen, onMessage, onClose,
     * onError).
     *
     * Implementations of this method are responsible for handling
     * any errors that occur during server startup, such as
     * binding to an unavailable port.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Sends a message to a specific connection.
     *
     * @param int $fd The connection's file descriptor (unique identifier).
     * @param mixed $message The message to be sent to this specific client.
     *
     * @return void
     */
    public function send(int $fd, mixed $message): void;

    /**
     * Broadcasts a message to all connected clients.
     *
     * @param mixed $message The message to be sent to every active connection.
     *
     * @return void
     */
    public function broadcast(mixed $message): void;

    /**
     * Registers a callback to execute when the server starts.
     *
     * @param callable $callback A function that is invoked on server start. It typically receives no parameters, but implementations may vary.
     *
     * @return void
     */
    public function onStart(callable $callback): void;

    /**
     * Registers a callback to execute when a new connection is opened.
     *
     * @param callable $callback A function that is invoked whenever a new client successfully connects, receiving the connection's file descriptor as an argument.
     *
     * @return void
     */
    public function onOpen(callable $callback): void;

    /**
     * Registers a callback to execute when a message is received from a client.
     *
     * @param callable $callback A function that is invoked with the message data and the connection's file descriptor as arguments.
     *
     * @return void
     */
    public function onMessage(callable $callback): void;

    /**
     * Registers a callback to execute when a connection is closed.
     *
     * @param callable $callback A function that is invoked with the connection's file descriptor when the client disconnects.
     *
     * @return void
     */
    public function onClose(callable $callback): void;

    /**
     * Registers a callback to execute when an error or exception occurs.
     *
     * @param callable $callback A function that is invoked on errors typically receiving error details as arguments.
     *
     * @return void
     */
    public function onError(callable $callback): void;
}
