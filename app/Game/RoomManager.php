<?php

namespace App\Game;

use App\Events\OpponentGuessed;
use App\Events\PlayerJoined;
use App\Events\RoundFinished;
use App\Events\RoundStarted;
use App\Models\Room;
use App\Models\RoomPlayer;
use DomainException;
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
        $room->players()->update(['attempts' => 0, 'solve_rank' => null, 'round_points' => 0]);

        $room->update([
            'status' => RoomStatus::Playing,
            'word' => $word ?? $this->words->randomSolution(),
            'round' => $room->round + 1,
            'round_ends_at' => now()->addSeconds(RoundScorer::ROUND_SECONDS),
            'opponent_left_at' => null,
        ]);

        RoundStarted::dispatch($room->code, $room->round, RoundScorer::ROUND_SECONDS);

        return $room;
    }

    /**
     * @throws DomainException
     */
    public function rematch(Room $room): Room
    {
        if ($room->status !== RoomStatus::MatchFinished) {
            throw new DomainException('De wedstrijd is nog bezig.');
        }

        $room->update(['round' => 0, 'scores' => []]);

        return $this->startRound($room);
    }

    /**
     * @return array<string, int>
     */
    public function scoreboard(Room $room): array
    {
        return $room->players()->orderBy('id')->get()
            ->mapWithKeys(fn (RoomPlayer $player): array => [$player->nickname => $room->scores[$player->id] ?? 0])
            ->all();
    }

    public function secondsLeft(Room $room): int
    {
        if ($room->status !== RoomStatus::Playing || $room->round_ends_at === null) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInSeconds($room->round_ends_at, false)));
    }

    /**
     * Finishes the round when its timer has run out.
     */
    public function expireRound(Room $room): bool
    {
        return DB::transaction(function () use ($room): bool {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);

            if ($room->status !== RoomStatus::Playing || $room->round_ends_at === null || $room->round_ends_at->isFuture()) {
                return false;
            }

            $this->finishRound($room, 'timeout');

            return true;
        });
    }

    public function markOpponentLeft(Room $room): int
    {
        if ($room->opponent_left_at === null) {
            $room->update(['opponent_left_at' => now()]);
        }

        return RoundScorer::FORFEIT_SECONDS;
    }

    public function markOpponentReturned(Room $room): void
    {
        $room->update(['opponent_left_at' => null]);
    }

    /**
     * @throws DomainException
     */
    public function forfeit(Room $room, RoomPlayer $remaining): Room
    {
        $left = $room->opponent_left_at;

        if (! in_array($room->status, [RoomStatus::Playing, RoomStatus::Finished], true)
            || $left === null
            || $left->copy()->addSeconds(RoundScorer::FORFEIT_SECONDS)->isFuture()) {
            throw new DomainException('De tegenstander kan nog terugkomen.');
        }

        return $this->finishRound($room, 'forfeit', $remaining);
    }

    /**
     * Evaluates a guess and broadcasts only the letter states to the room.
     *
     * @return list<LetterStatus>
     *
     * @throws DomainException
     */
    public function guess(Room $room, RoomPlayer $player, string $guess): array
    {
        if ($this->expireRound($room)) {
            throw new DomainException('De tijd voor deze ronde is om.');
        }

        return DB::transaction(function () use ($room, $player, $guess): array {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            $player = $room->players()->findOrFail($player->id);

            if ($player->attempts >= RoundScorer::MAX_GUESSES || $player->solve_rank !== null) {
                throw new DomainException('Je kunt in deze ronde niet meer raden.');
            }

            $statuses = $this->evaluator->evaluate($guess, (string) $room->word);
            $player->attempts++;

            if (collect($statuses)->every(fn (LetterStatus $status) => $status === LetterStatus::Correct)) {
                $this->recordSolve($room, $player);
            }

            $player->save();

            OpponentGuessed::dispatch($room->code, $player->nickname, $statuses);

            if ($this->everyoneIsDone($room)) {
                $this->finishRound($room);
            }

            return $statuses;
        });
    }

    public function finishRound(Room $room, string $reason = 'completed', ?RoomPlayer $forfeitWinner = null): Room
    {
        $players = $room->players()->orderBy('id')->get();
        $scores = $this->scoreboard($room);
        $matchOver = $reason === 'forfeit' || $room->round >= RoundScorer::TOTAL_ROUNDS;

        $matchWinner = match (true) {
            $forfeitWinner !== null => $forfeitWinner->nickname,
            $matchOver => $this->leader($scores),
            default => null,
        };

        $room->update([
            'status' => $matchOver ? RoomStatus::MatchFinished : RoomStatus::Finished,
            'round_ends_at' => null,
        ]);

        RoundFinished::dispatch(
            $room->code,
            (string) $room->word,
            $players->firstWhere('solve_rank', 1)?->nickname,
            $scores,
            $players->mapWithKeys(fn (RoomPlayer $player): array => [$player->nickname => $player->round_points])->all(),
            $matchOver,
            $matchWinner,
            $reason,
        );

        return $room;
    }

    private function recordSolve(Room $room, RoomPlayer $player): void
    {
        $rank = $room->players()->whereNotNull('solve_rank')->count() + 1;
        $points = RoundScorer::points($player->attempts, $rank === 1);

        $player->solve_rank = $rank;
        $player->round_points = $points;

        $scores = $room->scores;
        $scores[$player->id] = ($scores[$player->id] ?? 0) + $points;
        $room->update(['scores' => $scores]);
    }

    private function everyoneIsDone(Room $room): bool
    {
        return $room->players()->get()->every(
            fn (RoomPlayer $player): bool => $player->solve_rank !== null || $player->attempts >= RoundScorer::MAX_GUESSES,
        );
    }

    /**
     * @param  array<string, int>  $scores
     */
    private function leader(array $scores): ?string
    {
        arsort($scores);
        $top = array_slice($scores, 0, 2, true);

        if (count($top) === 2 && array_values($top)[0] === array_values($top)[1]) {
            return null;
        }

        return array_key_first($top);
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
