<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrownTransaction extends Model
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'action',
        'amount',
        'balance_after',
        'source_type',
        'source_id',
        'description',
        'metadata',
        'collected_at',
        'claim_dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'metadata' => 'array',
            'collected_at' => 'datetime',
            'claim_dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CrownWallet::class, 'wallet_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
