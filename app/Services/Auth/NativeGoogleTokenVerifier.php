<?php

namespace App\Services\Auth;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NativeGoogleTokenVerifier
{
    public function verify(string $idToken): SocialIdentity
    {
        if (! (bool) config('social.enabled', true) || ! (bool) config('social.providers.google.native_enabled', false)) {
            throw new RuntimeException('Native Google Login ist aktuell nicht aktiviert.');
        }

        $idToken = trim($idToken);
        if ($idToken === '') {
            throw new RuntimeException('Google Login wurde ohne gültiges ID-Token gesendet.');
        }

        $response = Http::timeout($this->timeout())
            ->acceptJson()
            ->get((string) config('social.providers.google.tokeninfo_url', 'https://oauth2.googleapis.com/tokeninfo'), [
                'id_token' => $idToken,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google ID-Token konnte nicht bestätigt werden.');
        }

        $data = (array) $response->json();
        $this->assertTokenIsAcceptable($data);

        return new SocialIdentity(
            provider: 'google',
            providerUserId: (string) Arr::get($data, 'sub'),
            email: $this->normalizeEmail(Arr::get($data, 'email')),
            emailVerified: filter_var(Arr::get($data, 'email_verified'), FILTER_VALIDATE_BOOLEAN),
            name: $this->cleanString(Arr::get($data, 'name')),
            nickname: $this->cleanString(Arr::get($data, 'given_name')) ?: $this->emailNickname(Arr::get($data, 'email')),
            avatarUrl: $this->cleanString(Arr::get($data, 'picture')),
            raw: $data,
        );
    }

    private function assertTokenIsAcceptable(array $data): void
    {
        $subject = (string) Arr::get($data, 'sub', '');
        if ($subject === '') {
            throw new RuntimeException('Google ID-Token enthält keine Benutzer-ID.');
        }

        $audience = (string) Arr::get($data, 'aud', '');
        $allowedAudiences = $this->allowedAudiences();
        if ($audience === '' || $allowedAudiences === [] || ! in_array($audience, $allowedAudiences, true)) {
            throw new RuntimeException('Google ID-Token ist nicht für diese App ausgestellt.');
        }

        $issuer = (string) Arr::get($data, 'iss', '');
        if ($issuer !== '' && ! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            throw new RuntimeException('Google ID-Token hat einen ungültigen Aussteller.');
        }

        $expiresAt = (int) Arr::get($data, 'exp', 0);
        if ($expiresAt > 0 && $expiresAt < time()) {
            throw new RuntimeException('Google ID-Token ist abgelaufen.');
        }
    }

    private function allowedAudiences(): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) config('social.providers.google.native_allowed_client_ids', [])
        ))));
    }

    private function timeout(): int
    {
        return max(3, (int) config('social.timeout', 20));
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = strtolower(trim((string) $value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function cleanString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function emailNickname(mixed $value): ?string
    {
        $email = $this->normalizeEmail($value);
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        return trim(strstr($email, '@', true)) ?: null;
    }
}
