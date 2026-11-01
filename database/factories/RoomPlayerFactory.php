<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomPlayer>
 */
class RoomPlayerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'guest_id' => fake()->uuid(),
            'nickname' => fake()->firstName(),
        ];
    }
}
