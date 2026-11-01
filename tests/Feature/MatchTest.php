<?php

use App\Events\RoundFinished;
use App\Game\RoomManager;
use App\Game\RoomStatus;
use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function matchWithTwoPlayers(string $word = 'crane'): array
{
    $room = Room::factory()->create(['code' => 'ABCD']);
    $manager = app(RoomManager::class);
    $ann = $manager->join('ABCD', 'guest-1', 'Ann');
    $bob = $manager->join('ABCD', 'guest-2', 'Bob');
    $manager->startRound($room, $word);

    return [$room->refresh(), $manager, $ann, $bob];
}

function scoreOf(Room $room, RoomPlayer $player): int
{
    return $room->refresh()->scores[$player->id] ?? 0;
}

it('awards 10 points, 2 per remaining row and 5 for solving first', function () {
    [$room, $manager, $ann] = matchWithTwoPlayers();

    $manager->guess($room, $ann, 'slate');
    $manager->guess($room, $ann, 'crane');

    expect(scoreOf($room, $ann))->toBe(10 + 3 * 2 + 5);
});

it('gives the second solver no first bonus and keeps the round open until both are done', function () {
    Event::fake([RoundFinished::class]);
    [$room, $manager, $ann, $bob] = matchWithTwoPlayers();

    $manager->guess($room, $ann, 'crane');
    expect($room->refresh()->status)->toBe(RoomStatus::Playing);
    Event::assertNotDispatched(RoundFinished::class);

    $manager->guess($room, $bob, 'slate');
    $manager->guess($room, $bob, 'trace');
    $manager->guess($room, $bob, 'crane');

    expect(scoreOf($room, $ann))->toBe(10 + 4 * 2 + 5)
        ->and(scoreOf($room, $bob))->toBe(10 + 2 * 2)
        ->and($room->refresh()->status)->toBe(RoomStatus::Finished);

    Event::assertDispatched(RoundFinished::class, fn (RoundFinished $event) => $event->broadcastWith()['winner'] === 'Ann'
        && $event->broadcastWith()['scores'] === ['Ann' => 23, 'Bob' => 14]);
});

it('finishes the round without points when both run out of guesses', function () {
    [$room, $manager, $ann, $bob] = matchWithTwoPlayers();

    foreach (range(1, 5) as $ignored) {
        $manager->guess($room, $ann, 'slate');
        $manager->guess($room, $bob, 'slate');
    }

    expect($room->refresh()->status)->toBe(RoomStatus::Finished)
        ->and(scoreOf($room, $ann))->toBe(0)
        ->and(scoreOf($room, $bob))->toBe(0);
});

it('rejects guesses beyond the five rows', function () {
    [$room, $manager, $ann] = matchWithTwoPlayers();

    foreach (range(1, 5) as $ignored) {
        $manager->guess($room, $ann, 'slate');
    }

    expect(fn () => $manager->guess($room, $ann, 'slate'))->toThrow(DomainException::class);
});

it('ends the round when the five minute timer runs out', function () {
    [$room, $manager] = matchWithTwoPlayers();

    expect($manager->expireRound($room))->toBeFalse();

    $this->travel(301)->seconds();

    expect($manager->expireRound($room->refresh()))->toBeTrue()
        ->and($room->refresh()->status)->toBe(RoomStatus::Finished);
});

it('refuses guesses after the timer has expired', function () {
    $room = Room::factory()->create(['code' => 'ABCD']);
    $this->postJson(route('rooms.join'), ['code' => 'ABCD', 'nickname' => 'Ann'])->assertOk();
    app(RoomManager::class)->join('ABCD', 'other', 'Bob');
    $this->postJson(route('rooms.start', 'ABCD'))->assertOk();

    $this->travel(301)->seconds();

    $this->postJson(route('rooms.guess', 'ABCD'), ['guess' => 'water'])->assertUnprocessable();
    expect($room->refresh()->status)->toBe(RoomStatus::Finished);
});

it('ends the match after three rounds and declares the leader the winner', function () {
    Event::fake([RoundFinished::class]);
    [$room, $manager, $ann, $bob] = matchWithTwoPlayers();

    foreach ([1, 2, 3] as $round) {
        expect($room->refresh()->round)->toBe($round);
        $manager->guess($room, $ann, 'crane');
        $manager->guess($room->refresh(), $bob, 'slate');
        $manager->finishRound($room->refresh());

        if ($round < 3) {
            expect($room->refresh()->status)->toBe(RoomStatus::Finished);
            $manager->startRound($room, 'crane');
        }
    }

    expect($room->refresh()->status)->toBe(RoomStatus::MatchFinished);
    Event::assertDispatched(RoundFinished::class, fn (RoundFinished $event) => $event->broadcastWith()['matchOver'] === true
        && $event->broadcastWith()['matchWinner'] === 'Ann');
});

it('does not start a fourth round but allows a rematch', function () {
    [$room, , $ann] = matchWithTwoPlayers();
    $room->update(['round' => 3, 'status' => RoomStatus::MatchFinished, 'scores' => [$ann->id => 40]]);

    $this->withSession(['guest_id' => 'guest-1'])
        ->postJson(route('rooms.start', 'ABCD'))
        ->assertUnprocessable();

    $this->withSession(['guest_id' => 'guest-1'])
        ->postJson(route('rooms.rematch', 'ABCD'))
        ->assertOk()
        ->assertJson(['round' => 1]);

    expect($room->refresh()->scores)->toBe([])
        ->and($room->status)->toBe(RoomStatus::Playing);
});

it('only allows a rematch once the match is over', function () {
    matchWithTwoPlayers();

    $this->withSession(['guest_id' => 'guest-1'])
        ->postJson(route('rooms.rematch', 'ABCD'))
        ->assertUnprocessable();
});

it('lets the remaining player win by forfeit after 30 seconds', function () {
    Event::fake([RoundFinished::class]);
    [$room] = matchWithTwoPlayers();
    $asAnn = fn () => $this->withSession(['guest_id' => 'guest-1']);

    $asAnn()->postJson(route('rooms.opponent-left', 'ABCD'))
        ->assertOk()
        ->assertJson(['seconds' => 30]);

    $this->travel(29)->seconds();
    $asAnn()->postJson(route('rooms.forfeit', 'ABCD'))->assertUnprocessable();

    $this->travel(2)->seconds();
    $asAnn()->postJson(route('rooms.forfeit', 'ABCD'))->assertOk();

    expect($room->refresh()->status)->toBe(RoomStatus::MatchFinished);
    Event::assertDispatched(RoundFinished::class, fn (RoundFinished $event) => $event->broadcastWith()['reason'] === 'forfeit'
        && $event->broadcastWith()['matchWinner'] === 'Ann'
        && $event->broadcastWith()['matchOver'] === true);
});

it('cancels the forfeit countdown when the opponent returns', function () {
    matchWithTwoPlayers();
    $asAnn = fn () => $this->withSession(['guest_id' => 'guest-1']);

    $asAnn()->postJson(route('rooms.opponent-left', 'ABCD'))->assertOk();
    $asAnn()->postJson(route('rooms.opponent-returned', 'ABCD'))->assertOk();

    $this->travel(60)->seconds();
    $asAnn()->postJson(route('rooms.forfeit', 'ABCD'))->assertUnprocessable();
});

it('shows scores and timer but never the word on the game page', function () {
    matchWithTwoPlayers('crane');

    $this->withSession(['guest_id' => 'guest-1'])
        ->get(route('rooms.show', 'ABCD'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game')
            ->where('totalRounds', 3)
            ->where('scores', ['Ann' => 0, 'Bob' => 0])
            ->where('secondsLeft', fn (int $seconds) => $seconds >= 298 && $seconds <= 300)
            ->where('matchOver', false)
            ->missing('word'))
        ->assertDontSee('crane');
});
