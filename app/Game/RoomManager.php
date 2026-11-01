<?php

namespace App\Game;

use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RoomManager
{
    public const MAX_PLAYERS = 2;

    public const CODE_LENGTH = 4;

    public function create(): Room
    {
        do {
            $code = $this->generateCode();

            try {
                return Room::create([
                    'code' => $code,
                    'status' => RoomStatus::Waiting,
                    'round' => 0,
                    'scores' => [],
                ]);
            } catch (UniqueConstraintViolationException) {
                // Code collision; try another one.
            }
        } while (true);
    }

    /**
     * @throws RoomFullException
     */
    public function join(string $code, string $guestId, string $nickname): RoomPlayer
    {
        return DB::transaction(function () use ($code, $guestId, $nickname): RoomPlayer {
            $room = Room::query()->where('code', $code)->lockForUpdate()->firstOrFail();

            $existing = $room->players()->where('guest_id', $guestId)->first();
            if ($existing !== null) {
                return $existing;
            }

            if ($room->players()->count() >= self::MAX_PLAYERS) {
                throw new RoomFullException('This room is full.');
            }

            return $room->players()->create([
                'guest_id' => $guestId,
                'nickname' => $nickname,
            ]);
        });
    }

    private function generateCode(): string
    {
        $letters = range('A', 'Z');
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $letters[random_int(0, 25)];
        }

        return $code;
    }
}
