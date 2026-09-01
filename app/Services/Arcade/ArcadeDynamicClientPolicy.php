<?php

namespace App\Services\Arcade;

use Illuminate\Validation\ValidationException;

class ArcadeDynamicClientPolicy
{
    public function isTrustedHttpsUrl(?string $url): bool
    {
        if (! is_string($url) || trim($url) === '') return false;

        $parts = parse_url($url);
        if (! is_array($parts)) return false;
        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') return false;
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) return false;
        if (isset($parts['port']) && (int) $parts['port'] !== 443) return false;

        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        if ($host === '') return false;

        $trusted = array_map(
            static fn ($value): string => strtolower(rtrim(trim((string) $value), '.')),
            (array) config('arcade.trusted_web_hosts', []),
        );

        return in_array($host, $trusted, true);
    }

    public function assertTrustedHttpsUrl(?string $url, string $field): void
    {
        if (! $this->isTrustedHttpsUrl($url)) {
            throw ValidationException::withMessages([
                $field => 'The URL must use HTTPS and an HNT-controlled trusted Arcade host without credentials, a custom port, or a fragment.',
            ]);
        }
    }

    public function origin(string $url): string
    {
        $this->assertTrustedHttpsUrl($url, 'entrypoint_url');
        return 'https://'.strtolower(rtrim((string) parse_url($url, PHP_URL_HOST), '.'));
    }
}
