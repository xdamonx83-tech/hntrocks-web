<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserTwoFactorChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'purpose',
        'token_hash',
        'secret',
        'device_name',
        'ip_address',
        'user_agent',
        'expires_at',
        'used_at',
    ];

    protected $hidden = [
        'token_hash',
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function createForUser(User $user, string $purpose, ?string $deviceName = null, ?string $ipAddress = null, ?string $userAgent = null, ?string $secret = null, int $minutes = 10): array
    {
        $plainToken = Str::random(80);

        $challenge = self::query()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'token_hash' => hash('sha256', $plainToken),
            'secret' => $secret,
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? Str::limit($userAgent, 512, '') : null,
            'expires_at' => now()->addMinutes($minutes),
        ]);

        return [
            'plain_token' => $plainToken,
            'challenge' => $challenge,
        ];
    }

    public static function findValidPlainToken(?string $plainToken, ?string $purpose = null): ?self
    {
        $plainToken = trim((string) $plainToken);

        if ($plainToken === '') {
            return null;
        }

        return self::query()
            ->with('user.profile')
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->when($purpose, fn ($query) => $query->where('purpose', $purpose))
            ->first();
    }

    public function markUsed(): void
    {
        $this->forceFill(['used_at' => now()])->save();
    }
}
