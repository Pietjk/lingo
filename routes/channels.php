<?php

use App\Models\RoomPlayer;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('room.{code}', function ($user, string $code) {
    $player = RoomPlayer::query()
        ->where('guest_id', (string) $user->getAuthIdentifier())
        ->whereHas('room', fn ($query) => $query->where('code', $code))
        ->first();

    return $player === null ? false : ['id' => $player->id, 'name' => $player->nickname];
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
