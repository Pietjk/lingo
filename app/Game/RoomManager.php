<?php

namespace App\Game;

use App\Events\OpponentGuessed;
use App\Events\PlayerJoined;
use App\Events\RoundFinished;
use App\Events\RoundStarted;
use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RoomManager
{
    public const MAX_PLAYERS = 2;

    public const CODE_LENGTH = 4;

    public function __construct(
        private readonly WordList $words,
        private readonly GuessEvaluator $evaluator,
    ) {}

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

            $player = $room->players()->create([
                'guest_id' => $guestId,
                'nickname' => $nickname,
            ]);

            PlayerJoined::dispatch($room->code, $player->nickname);

            return $player;
        });
    }

    public function startRound(Room $room, ?string $word = null): Room
    {
        $room->update([
            'status' => RoomStatus::Playing,
            'word' => $word ?? $this->words->randomSolution(),
            'round' => $room->round + 1,
        ]);

        RoundStarted::dispatch($room->code, $room->round);

        return $room;
    }

    /**
     * Evaluates a guess and broadcasts only the letter states to the room.
     *
     * @return list<LetterStatus>
     */
    public function guess(Room $room, RoomPlayer $player, string $guess): array
    {
        $statuses = $this->evaluator->evaluate($guess, (string) $room->word);

        OpponentGuessed::dispatch($room->code, $player->nickname, $statuses);

        if (collect($statuses)->every(fn (LetterStatus $status) => $status === LetterStatus::Correct)) {
            $this->finishRound($room, $player);
        }

        return $statuses;
    }

    public function finishRound(Room $room, ?RoomPlayer $winner = null): Room
    {
        $scores = $room->scores;

        if ($winner !== null) {
            $scores[$winner->id] = ($scores[$winner->id] ?? 0) + 1;
        }

        $word = (string) $room->word;

        $room->update(['status' => RoomStatus::Finished, 'scores' => $scores]);

        RoundFinished::dispatch($room->code, $word, $winner?->nickname);

        return $room;
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
