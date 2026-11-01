<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RoomPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $last_seen_at
 */
class RoomPlayer extends Model
{
    /** @use HasFactory<RoomPlayerFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['room_id', 'guest_id', 'nickname', 'attempts', 'solve_rank', 'round_points', 'last_seen_at'];

    /** @var list<string> */
    protected $hidden = ['guest_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'solve_rank' => 'integer',
            'round_points' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
