<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    public const COVER_DISPLAY_AUTO = 'auto';

    public const COVER_DISPLAY_ALWAYS = 'always';

    public const COVER_DISPLAY_HIDDEN = 'hidden';

    public const COVER_DISPLAY_MODES = [
        self::COVER_DISPLAY_AUTO,
        self::COVER_DISPLAY_ALWAYS,
        self::COVER_DISPLAY_HIDDEN,
    ];

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
        'cover_display_mode',
        'hunter_dna',
        'hunter_dna_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_lfg_available' => 'boolean',
            'hunter_dna' => 'array',
            'hunter_dna_completed_at' => 'datetime',
        ];
    }

    public function coverDisplayMode(): string
    {
        $mode = (string) ($this->cover_display_mode ?: self::COVER_DISPLAY_AUTO);

        return in_array($mode, self::COVER_DISPLAY_MODES, true)
            ? $mode
            : self::COVER_DISPLAY_AUTO;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
