<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class RoundFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<string, int>  $scores  Total score per nickname.
     * @param  array<string, int>  $points  Points earned this round per nickname.
     */
    public function __construct(
        public string $code,
        public string $word,
        public ?string $winner,
        public array $scores = [],
        public array $points = [],
        public bool $matchOver = false,
        public ?string $matchWinner = null,
        public string $reason = 'completed',
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('room.'.$this->code);
    }

    /**
     * @return array{word: string, winner: string|null, scores: array<string, int>, points: array<string, int>, matchOver: bool, matchWinner: string|null, reason: string}
     */
    public function broadcastWith(): array
    {
        return [
            'word' => $this->word,
            'winner' => $this->winner,
            'scores' => $this->scores,
            'points' => $this->points,
            'matchOver' => $this->matchOver,
            'matchWinner' => $this->matchWinner,
            'reason' => $this->reason,
        ];
    }
}
