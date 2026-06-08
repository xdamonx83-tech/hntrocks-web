<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrownWallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'lifetime_earned',
        'lifetime_spent',
        'last_daily_login_reward_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'lifetime_earned' => 'integer',
            'lifetime_spent' => 'integer',
            'last_daily_login_reward_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CrownTransaction::class, 'wallet_id');
    }
}
