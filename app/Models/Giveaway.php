<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Giveaway extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'profile_completion_required',
        'base_entries',
        'profile_bonus_entries',
        'referral_bonus_entries',
        'max_referral_bonus_entries',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'rules' => 'array',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GiveawayEntry::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
