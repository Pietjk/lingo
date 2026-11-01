<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->uuid('guest_id');
            $table->string('nickname', 20);
            $table->timestamps();

            $table->unique(['room_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_players');
    }
};
