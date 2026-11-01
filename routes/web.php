<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
Route::post('/rooms/join', [RoomController::class, 'join'])->name('rooms.join');
Route::get('/rooms/{room:code}', [RoomController::class, 'show'])->name('rooms.show');
Route::post('/rooms/{room:code}/start', [RoomController::class, 'start'])->name('rooms.start');
Route::post('/rooms/{room:code}/guess', [RoomController::class, 'guess'])->name('rooms.guess');
