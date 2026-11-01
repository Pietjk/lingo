<?php

namespace App\Models;

use App\Game\RoomStatus;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['code', 'status', 'word', 'round', 'scores'];

    /** @var list<string> */
    protected $hidden = ['word'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RoomStatus::class,
            'round' => 'integer',
            'scores' => 'array',
        ];
    }

    /**
     * @return HasMany<RoomPlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(RoomPlayer::class);
    }
}
