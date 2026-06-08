<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MobileSocialLoginCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'code_hash',
        'device_name',
        'ip_address',
        'user_agent',
        'used_at',
        'expires_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function createForUser(User $user, string $provider, string $deviceName = 'Android App', ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $plainCode = Str::random(96);
        $ttlMinutes = max(1, (int) config('social.mobile.code_ttl_minutes', 5));

        $code = self::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'code_hash' => hash('sha256', $plainCode),
            'device_name' => Str::limit(trim($deviceName) !== '' ? trim($deviceName) : 'Android App', 80, ''),
            'ip_address' => $ipAddress,
            'user_agent' => Str::limit((string) $userAgent, 512, ''),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return [
            'plain_code' => $plainCode,
            'code' => $code,
            'ttl_seconds' => $ttlMinutes * 60,
        ];
    }
}
