<?php

namespace App\Events;

use App\Game\LetterStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OpponentGuessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  list<LetterStatus>  $statuses  Colors only; the guessed letters are never included.
     */
    public function __construct(
        public string $code,
        public string $nickname,
        public array $statuses,
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('room.'.$this->code);
    }

    /**
     * @return array{nickname: string, statuses: list<string>}
     */
    public function broadcastWith(): array
    {
        return [
            'nickname' => $this->nickname,
            'statuses' => array_map(fn (LetterStatus $status): string => $status->value, $this->statuses),
        ];
    }
}
