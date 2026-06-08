<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'platform',
        'playstyle',
        'region',
        'language',
        'hunt_role',
        'discord_name',
        'steam_url',
        'twitch_url',
        'youtube_url',
        'is_lfg_available',
        'profile_visibility',
    ];

    protected function casts(): array
    {
        return [
            'is_lfg_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
