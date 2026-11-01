<?php

use App\Events\OpponentGuessed;
use App\Events\PlayerJoined;
use App\Events\RoundFinished;
use App\Events\RoundStarted;
use App\Game\RoomManager;
use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('broadcasts on the room presence channel', function () {
    $channel = (new RoundStarted('ABCD', 1))->broadcastOn();

    expect($channel)->toBeInstanceOf(PresenceChannel::class)
        ->and($channel->name)->toBe('presence-room.ABCD');
});

it('never exposes the word before the round ends', function () {
    Event::fake();

    $room = Room::factory()->create(['code' => 'ABCD']);
    $manager = app(RoomManager::class);

    $manager->join('ABCD', 'guest-1', 'Ann');
    $manager->join('ABCD', 'guest-2', 'Bob');
    $manager->startRound($room, 'crane');
    $room->refresh();

    $ann = RoomPlayer::query()->where('nickname', 'Ann')->firstOrFail();
    $manager->guess($room, $ann, 'slate');
    $manager->guess($room, $ann, 'trace');

    Event::assertDispatched(PlayerJoined::class, 2);
    Event::assertDispatched(RoundStarted::class);
    Event::assertDispatched(OpponentGuessed::class, 2);
    Event::assertNotDispatched(RoundFinished::class);

    $leaks = collect([PlayerJoined::class, RoundStarted::class, OpponentGuessed::class])
        ->flatMap(fn (string $event) => Event::dispatched($event))
        ->filter(function (array $dispatched) {
            $event = $dispatched[0];
            $payload = strtolower(json_encode([...get_object_vars($event), ...$event->broadcastWith()]));

            return str_contains($payload, 'crane');
        });

    expect($leaks)->toBeEmpty();
});

it('broadcasts only letter states for a guess', function () {
    Event::fake();

    $room = Room::factory()->create(['code' => 'ABCD', 'word' => 'crane']);
    $player = RoomPlayer::factory()->create(['room_id' => $room->id]);

    app(RoomManager::class)->guess($room, $player, 'slate');

    Event::assertDispatched(OpponentGuessed::class, function (OpponentGuessed $event) {
        $payload = $event->broadcastWith();

        return array_keys($payload) === ['nickname', 'statuses']
            && $payload['statuses'] === ['absent', 'absent', 'correct', 'absent', 'correct'];
    });
});

it('reveals the word only in RoundFinished', function () {
    Event::fake();

    $room = Room::factory()->create(['code' => 'ABCD']);
    $manager = app(RoomManager::class);
    $manager->join('ABCD', 'guest-1', 'Ann');
    $manager->startRound($room, 'crane');
    $room->refresh();
    $ann = RoomPlayer::query()->firstOrFail();

    $manager->guess($room, $ann, 'crane');

    Event::assertDispatched(RoundFinished::class, fn (RoundFinished $event) => $event->broadcastWith()['word'] === 'crane' && $event->broadcastWith()['winner'] === 'Ann');
});
