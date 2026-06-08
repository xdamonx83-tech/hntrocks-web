<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPushDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'platform',
        'device_id',
        'device_name',
        'app_version',
        'locale',
        'timezone',
        'token',
        'token_hash',
        'last_seen_at',
        'disabled_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token',
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'disabled_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('disabled_at')->whereNull('revoked_at');
    }

    public function markRevoked(): void
    {
        $this->forceFill([
            'disabled_at' => now(),
            'revoked_at' => now(),
        ])->save();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
