<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrownRewardClaim extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'reward_date',
        'claims_count',
    ];

    protected function casts(): array
    {
        return [
            'reward_date' => 'date',
            'claims_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
