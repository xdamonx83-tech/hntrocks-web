<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralSignup extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_link_id',
        'referrer_id',
        'referred_user_id',
        'referral_code',
        'registered_at',
        'profile_completed_at',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class, 'referral_link_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
