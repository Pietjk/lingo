<?php

namespace App\Http\Controllers;

use App\Game\GuestIdentity;
use App\Game\LetterStatus;
use App\Game\RoomFullException;
use App\Game\RoomManager;
use App\Game\RoomStatus;
use App\Game\WordList;
use App\Http\Requests\GuessRequest;
use App\Http\Requests\JoinRoomRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function __construct(private readonly RoomManager $rooms) {}

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->rooms->create();

        return response()->json(['code' => $room->code], 201);
    }

    public function join(JoinRoomRequest $request): JsonResponse
    {
        try {
            $player = $this->rooms->join(
                $request->validated('code'),
                GuestIdentity::id($request->session()),
                $request->validated('nickname'),
            );
        } catch (RoomFullException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        return response()->json([
            'code' => $request->validated('code'),
            'nickname' => $player->nickname,
        ]);
    }

    public function show(Request $request, Room $room): Response
    {
        $player = $this->currentPlayer($request, $room);

        if ($player === null) {
            return Inertia::render('Home', ['code' => $room->code]);
        }

        $players = $room->players()->orderBy('id')->pluck('nickname')->all();
        $opponent = collect($players)->first(fn (string $name): bool => $name !== $player->nickname);

        if ($room->status === RoomStatus::Waiting) {
            return Inertia::render('Lobby', [
                'code' => $room->code,
                'me' => $player->nickname,
                'players' => $players,
                'shareUrl' => route('rooms.show', $room),
            ]);
        }

        return Inertia::render('Game', [
            'code' => $room->code,
            'me' => $player->nickname,
            'opponent' => $opponent,
            'round' => $room->round,
            'finished' => $room->status === RoomStatus::Finished,
        ]);
    }

    public function start(Request $request, Room $room): JsonResponse
    {
        abort_if($this->currentPlayer($request, $room) === null, 403);

        if ($room->status === RoomStatus::Playing || $room->players()->count() < RoomManager::MAX_PLAYERS) {
            throw ValidationException::withMessages(['room' => 'Er zijn twee spelers nodig om te starten.']);
        }

        $this->rooms->startRound($room);

        return response()->json(['round' => $room->round]);
    }

    public function guess(GuessRequest $request, Room $room, WordList $words): JsonResponse
    {
        $player = $this->currentPlayer($request, $room);
        abort_if($player === null, 403);

        if ($room->status !== RoomStatus::Playing) {
            throw ValidationException::withMessages(['guess' => 'Er is geen ronde bezig.']);
        }

        if (! $words->isValidGuess($request->validated('guess'))) {
            throw ValidationException::withMessages(['guess' => 'Dit woord staat niet in de woordenlijst.']);
        }

        $statuses = $this->rooms->guess($room, $player, $request->validated('guess'));

        return response()->json([
            'statuses' => array_map(fn (LetterStatus $status): string => $status->value, $statuses),
        ]);
    }

    private function currentPlayer(Request $request, Room $room): ?RoomPlayer
    {
        return $room->players()->where('guest_id', GuestIdentity::id($request->session()))->first();
    }
}
