<?php

namespace Squirtle\WebSocket\Events\Client;

use Illuminate\Foundation\Events\Dispatchable;

class ConnectionFailed
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $failure, public int $code)
    {
        //
    }
}
