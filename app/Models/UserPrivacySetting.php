<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPrivacySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_visibility',
        'allow_messages_from',
        'allow_team_invites',
        'allow_lfg_invites',
        'show_online_status',
        'show_activity_feed',
        'show_gamification',
        'data_usage_consent',
    ];

    protected function casts(): array
    {
        return [
            'allow_team_invites' => 'boolean',
            'allow_lfg_invites' => 'boolean',
            'show_online_status' => 'boolean',
            'show_activity_feed' => 'boolean',
            'show_gamification' => 'boolean',
            'data_usage_consent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
