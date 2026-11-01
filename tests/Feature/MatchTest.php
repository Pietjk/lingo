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

it('ends the round immediately when one player solves it and reveals the word', function () {
    Event::fake([RoundFinished::class]);
    [$room, $manager, $ann, $bob] = matchWithTwoPlayers();

    $manager->guess($room, $bob, 'slate');
    $manager->guess($room->refresh(), $ann, 'crane');

    expect($room->refresh()->status)->toBe(RoomStatus::Finished)
        ->and(scoreOf($room, $ann))->toBe(10 + 4 * 2 + 5)
        ->and(scoreOf($room, $bob))->toBe(0);

    Event::assertDispatched(RoundFinished::class, fn (RoundFinished $event) => $event->broadcastWith()['word'] === 'crane'
        && $event->broadcastWith()['winner'] === 'Ann'
        && $event->broadcastWith()['scores'] === ['Ann' => 23, 'Bob' => 0]);

    expect(fn () => $manager->guess($room->refresh(), $bob, 'crane'))->toThrow(DomainException::class);
});

it('does not offer a rematch after a forfeit', function () {
    [$room] = matchWithTwoPlayers();
    $asAnn = fn () => $this->withSession(['guest_id' => 'guest-1']);

    $asAnn()->postJson(route('rooms.opponent-left', 'ABCD'))->assertOk();
    $this->travel(31)->seconds();
    $asAnn()->postJson(route('rooms.forfeit', 'ABCD'))->assertOk();

    $asAnn()->get(route('rooms.show', 'ABCD'))
        ->assertInertia(fn (Assert $page) => $page->where('matchOver', true)->where('opponentForfeited', true));

    $asAnn()->postJson(route('rooms.rematch', 'ABCD'))->assertUnprocessable();
    $asAnn()->postJson(route('rooms.opponent-returned', 'ABCD'))->assertOk();
    $asAnn()->postJson(route('rooms.rematch', 'ABCD'))->assertUnprocessable();

    expect($room->refresh()->status)->toBe(RoomStatus::MatchFinished);
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

it('rejects heartbeats from non-members', function () {
    Room::factory()->create(['code' => 'ABCD']);

    $this->withSession(['guest_id' => 'outsider'])
        ->postJson(route('rooms.heartbeat', 'ABCD'))
        ->assertForbidden();
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

it('does not allow a player to forfeit while the opponent is active', function () {
    [$room] = matchWithTwoPlayers('water');
    $asAnn = fn () => $this->withSession(['guest_id' => 'guest-1']);
    $asBob = fn () => $this->withSession(['guest_id' => 'guest-2']);

    $asBob()->postJson(route('rooms.heartbeat', 'ABCD'))->assertOk();
    $asAnn()->postJson(route('rooms.opponent-left', 'ABCD'))->assertOk();

    $this->travel(31)->seconds();
    $asBob()->postJson(route('rooms.heartbeat', 'ABCD'))->assertOk();

    $asAnn()->postJson(route('rooms.forfeit', 'ABCD'))->assertUnprocessable();

    expect($room->refresh()->status)->toBe(RoomStatus::Playing);

    $asAnn()->postJson(route('rooms.guess', 'ABCD'), ['guess' => 'water'])->assertOk();

    expect($room->refresh()->status)->toBe(RoomStatus::Finished)
        ->and($room->opponent_left_at)->toBeNull();
});

it('cancels the forfeit countdown when the opponent returns', function () {
    matchWithTwoPlayers();
    $asAnn = fn () => $this->withSession(['guest_id' => 'guest-1']);
    $asBob = fn () => $this->withSession(['guest_id' => 'guest-2']);

    $asAnn()->postJson(route('rooms.opponent-left', 'ABCD'))->assertOk();
    $asAnn()->postJson(route('rooms.opponent-returned', 'ABCD'))->assertOk();
    $asBob()->postJson(route('rooms.heartbeat', 'ABCD'))->assertOk();

    $this->travel(60)->seconds();
    $asBob()->postJson(route('rooms.heartbeat', 'ABCD'))->assertOk();
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
