<?php

namespace Squirtle\WebSocket\Events\Server;

use Illuminate\Foundation\Events\Dispatchable;

class ConnectionOpened
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public mixed $server, public mixed $request)
    {
        //
    }
}
