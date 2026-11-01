<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
Route::post('/rooms/join', [RoomController::class, 'join'])->name('rooms.join');
Route::get('/rooms/{room:code}', [RoomController::class, 'show'])->name('rooms.show');
Route::post('/rooms/{room:code}/start', [RoomController::class, 'start'])->name('rooms.start');
Route::post('/rooms/{room:code}/guess', [RoomController::class, 'guess'])->name('rooms.guess');
Route::post('/rooms/{room:code}/rematch', [RoomController::class, 'rematch'])->name('rooms.rematch');
Route::post('/rooms/{room:code}/expire', [RoomController::class, 'expire'])->name('rooms.expire');
Route::post('/rooms/{room:code}/opponent-left', [RoomController::class, 'opponentLeft'])->name('rooms.opponent-left');
Route::post('/rooms/{room:code}/opponent-returned', [RoomController::class, 'opponentReturned'])->name('rooms.opponent-returned');
Route::post('/rooms/{room:code}/heartbeat', [RoomController::class, 'heartbeat'])
    ->middleware('throttle:30,1')
    ->name('rooms.heartbeat');
Route::post('/rooms/{room:code}/forfeit', [RoomController::class, 'forfeit'])->name('rooms.forfeit');
