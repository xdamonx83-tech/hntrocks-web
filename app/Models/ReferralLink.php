<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'clicks_count',
        'signups_count',
        'completed_profiles_count',
        'last_clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_clicked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signups(): HasMany
    {
        return $this->hasMany(ReferralSignup::class);
    }

    public function url(): string
    {
        return route('referrals.accept', $this->code);
    }
}
