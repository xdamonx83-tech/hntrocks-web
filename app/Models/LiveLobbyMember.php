<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveLobbyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_lobby_id', 'user_id', 'role', 'platform', 'platform_handle', 'joined_at', 'left_at',
    ];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'left_at' => 'datetime'];
    }

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(LiveLobby::class, 'live_lobby_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
