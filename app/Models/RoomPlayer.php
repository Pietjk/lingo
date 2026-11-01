<?php

namespace App\Models;

use Database\Factories\RoomPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPlayer extends Model
{
    /** @use HasFactory<RoomPlayerFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['room_id', 'guest_id', 'nickname'];

    /** @var list<string> */
    protected $hidden = ['guest_id'];

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
