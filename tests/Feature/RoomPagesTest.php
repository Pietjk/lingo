<?php

use App\Game\RoomManager;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function joinAs(object $test, string $code, string $nickname): void
{
    $test->postJson(route('rooms.join'), ['code' => $code, 'nickname' => $nickname])->assertOk();
}

it('shows the home page with the code prefilled to non-members', function () {
    Room::factory()->create(['code' => 'ABCD']);

    $this->get(route('rooms.show', 'ABCD'))
        ->assertInertia(fn (Assert $page) => $page->component('Home')->where('code', 'ABCD'));
});

it('shows the lobby to a joined player', function () {
    Room::factory()->create(['code' => 'ABCD']);
    joinAs($this, 'ABCD', 'Ann');

    $this->get(route('rooms.show', 'ABCD'))
        ->assertInertia(fn (Assert $page) => $page->component('Lobby')->where('players', ['Ann']));
});

it('refuses to start without two players', function () {
    Room::factory()->create(['code' => 'ABCD']);
    joinAs($this, 'ABCD', 'Ann');

    $this->postJson(route('rooms.start', 'ABCD'))->assertUnprocessable();
});

it('starts a round and accepts guesses', function () {
    $room = Room::factory()->create(['code' => 'ABCD']);
    joinAs($this, 'ABCD', 'Ann');
    app(RoomManager::class)->join('ABCD', 'other-guest', 'Bob');

    $this->postJson(route('rooms.start', 'ABCD'))->assertOk();
    $room->refresh()->update(['word' => 'water']);

    $this->get(route('rooms.show', 'ABCD'))
        ->assertInertia(fn (Assert $page) => $page->component('Game')->where('opponent', 'Bob'));

    $this->postJson(route('rooms.guess', 'ABCD'), ['guess' => 'water'])
        ->assertOk()
        ->assertJson(['statuses' => array_fill(0, 5, 'correct')]);

    $this->postJson(route('rooms.guess', 'ABCD'), ['guess' => 'water'])->assertUnprocessable();
});
