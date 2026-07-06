<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrownDailyStreak extends Model
{
    protected $fillable = [
        'user_id',
        'current_streak',
        'last_claimed_on',
        'last_claimed_at',
        'dismissed_on',
        'total_claims',
        'longest_streak',
    ];

    protected function casts(): array
    {
        return [
            'current_streak' => 'integer',
            'last_claimed_on' => 'date',
            'last_claimed_at' => 'datetime',
            'dismissed_on' => 'date',
            'total_claims' => 'integer',
            'longest_streak' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(CrownDailyStreakClaim::class, 'user_id', 'user_id');
    }
}
