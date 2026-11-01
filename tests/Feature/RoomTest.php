<?php

use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a room with a 4-letter code', function () {
    $response = $this->postJson(route('rooms.store'))->assertCreated();

    $code = $response->json('code');
    expect($code)->toMatch('/^[A-Z]{4}$/');
    $this->assertDatabaseHas('rooms', ['code' => $code, 'status' => 'waiting', 'round' => 0]);
});

it('joins a room by code with a nickname', function () {
    $room = Room::factory()->create(['code' => 'ABCD']);

    $this->postJson(route('rooms.join'), ['code' => 'abcd', 'nickname' => 'Ann'])
        ->assertOk()
        ->assertJson(['code' => 'ABCD', 'nickname' => 'Ann']);

    expect($room->players()->count())->toBe(1);
});

it('does not add the same guest twice', function () {
    $room = Room::factory()->create(['code' => 'ABCD']);

    $this->postJson(route('rooms.join'), ['code' => 'ABCD', 'nickname' => 'Ann'])->assertOk();
    $this->postJson(route('rooms.join'), ['code' => 'ABCD', 'nickname' => 'Ann'])->assertOk();

    expect($room->players()->count())->toBe(1);
});

it('rejects a third player when the room is full', function () {
    $room = Room::factory()->create(['code' => 'ABCD']);
    RoomPlayer::factory()->count(2)->create(['room_id' => $room->id]);

    $this->postJson(route('rooms.join'), ['code' => 'ABCD', 'nickname' => 'Cy'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    expect($room->players()->count())->toBe(2);
});

it('rejects an unknown room code', function () {
    $this->postJson(route('rooms.join'), ['code' => 'ZZZZ', 'nickname' => 'Ann'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('rejects malformed codes and invalid nicknames', function (array $payload, string $field) {
    $this->postJson(route('rooms.join'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'too short code' => [['code' => 'AB', 'nickname' => 'Ann'], 'code'],
    'digits in code' => [['code' => 'AB12', 'nickname' => 'Ann'], 'code'],
    'missing nickname' => [['code' => 'ABCD'], 'nickname'],
    'long nickname' => [['code' => 'ABCD', 'nickname' => str_repeat('a', 21)], 'nickname'],
]);
