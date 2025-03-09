<?php

namespace Squirtle\WebSocket\Events\Server;

use Illuminate\Foundation\Events\Dispatchable;

class ConnectionClosed
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $fd, public string $failure, public int $code)
    {
        //
    }
}
