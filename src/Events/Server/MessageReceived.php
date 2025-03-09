<?php

namespace Squirtle\WebSocket\Events\Server;

use Illuminate\Foundation\Events\Dispatchable;

class MessageReceived
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $channel, public int $fd, public string $message)
    {
        //
    }
}
