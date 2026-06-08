<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'abilities',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function createForUser(User $user, string $name = 'Android App', array $abilities = ['*'], ?int $expiresInDays = 90): array
    {
        $plainSecret = Str::random(64);

        $token = self::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plainSecret),
            'abilities' => $abilities,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ]);

        return [
            'access_token' => $token->id.'|'.$plainSecret,
            'token' => $token,
        ];
    }

    public static function findValidPlainToken(?string $plainToken): ?self
    {
        if (! $plainToken || ! str_contains($plainToken, '|')) {
            return null;
        }

        [$id, $secret] = explode('|', $plainToken, 2);

        if (! ctype_digit($id) || $secret === '') {
            return null;
        }

        $token = self::query()
            ->with('user')
            ->whereKey((int) $id)
            ->where('token_hash', hash('sha256', $secret))
            ->whereNull('revoked_at')
            ->first();

        if (! $token) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return null;
        }

        if (! $token->user || $token->user->status !== 'active') {
            return null;
        }

        return $token;
    }

    public function canUse(string $ability): bool
    {
        $abilities = $this->abilities ?: ['*'];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}
