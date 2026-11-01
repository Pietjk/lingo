<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->timestamp('round_ends_at')->nullable();
            $table->timestamp('opponent_left_at')->nullable();
        });

        Schema::table('room_players', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('solve_rank')->nullable();
            $table->unsignedSmallInteger('round_points')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['round_ends_at', 'opponent_left_at']);
        });

        Schema::table('room_players', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'solve_rank', 'round_points']);
        });
    }
};
