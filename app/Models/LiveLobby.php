<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LiveLobby extends Model
{
    use HasFactory;

    public const ACTIVE_STATUSES = ['open', 'full'];

    protected $fillable = [
        'public_id', 'creator_id', 'mode', 'slots_total', 'slots_filled', 'platform',
        'crossplay_pool', 'region', 'language', 'voice_required', 'playstyle', 'mood', 'note',
        'lobby_code', 'steam_id', 'psn_id', 'xbox_gamertag', 'discord_handle', 'status',
        'expires_at', 'full_at', 'closed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (LiveLobby $lobby): void {
            $lobby->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'voice_required' => 'boolean',
            'expires_at' => 'datetime',
            'full_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LiveLobbyMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->whereNull('left_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES)->where('expires_at', '>', now());
    }

    public function expireIfNeeded(): bool
    {
        if (in_array($this->status, self::ACTIVE_STATUSES, true) && $this->expires_at?->isPast()) {
            $this->forceFill(['status' => 'expired'])->save();
            return true;
        }

        return false;
    }

    public function acceptsPlatform(string $platform): bool
    {
        return $this->crossplay_pool === ($platform === 'pc' ? 'pc' : 'console');
    }
}
