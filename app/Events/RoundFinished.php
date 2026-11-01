<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class RoundFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $code,
        public string $word,
        public ?string $winner,
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('room.'.$this->code);
    }

    /**
     * @return array{word: string, winner: string|null}
     */
    public function broadcastWith(): array
    {
        return ['word' => $this->word, 'winner' => $this->winner];
    }
}
