<?php

namespace App\Events;

use App\Game\RoundScorer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class RoundStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public string $code, public int $round, public int $secondsLeft = RoundScorer::ROUND_SECONDS) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('room.'.$this->code);
    }

    /**
     * @return array{round: int, secondsLeft: int}
     */
    public function broadcastWith(): array
    {
        return ['round' => $this->round, 'secondsLeft' => $this->secondsLeft];
    }
}
