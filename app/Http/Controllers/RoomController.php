<?php

namespace App\Http\Controllers;

use App\Game\GuestIdentity;
use App\Game\RoomFullException;
use App\Game\RoomManager;
use App\Http\Requests\JoinRoomRequest;
use App\Http\Requests\StoreRoomRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
}
