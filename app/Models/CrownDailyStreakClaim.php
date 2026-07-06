<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrownDailyStreakClaim extends Model
{
    protected $fillable = [
        'user_id',
        'claim_date',
        'streak_day',
        'amount',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'streak_day' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CrownTransaction::class, 'transaction_id');
    }
}
