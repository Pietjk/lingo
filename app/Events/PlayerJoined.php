<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class PlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public string $code, public string $nickname) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('room.'.$this->code);
    }

    /**
     * @return array{nickname: string}
     */
    public function broadcastWith(): array
    {
        return ['nickname' => $this->nickname];
    }
}
