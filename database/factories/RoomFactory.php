<?php

namespace Database\Factories;

use App\Game\RoomStatus;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'status' => RoomStatus::Waiting,
            'word' => null,
            'round' => 0,
            'scores' => [],
        ];
    }
}
