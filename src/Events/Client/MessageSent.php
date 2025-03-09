<?php

namespace Squirtle\WebSocket\Events\Client;

use Illuminate\Foundation\Events\Dispatchable;

class MessageSent
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $channel, public string $message)
    {
        //
    }
}
