<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SocialProviderService
{
    public const SUPPORTED_PROVIDERS = ['google', 'discord', 'twitch', 'steam', 'microsoft', 'facebook'];

    public function isSupported(string $provider): bool
    {
        return in_array($this->normalizeProvider($provider), self::SUPPORTED_PROVIDERS, true);
    }

    public function isEnabled(string $provider): bool
    {
        $provider = $this->normalizeProvider($provider);

        return (bool) config('social.enabled', true)
            && (bool) config("social.providers.{$provider}.enabled", false);
    }

    public function assertUsable(string $provider): string
    {
        $provider = $this->normalizeProvider($provider);

        if (! $this->isSupported($provider)) {
            throw new RuntimeException('Dieser Social-Login-Anbieter wird nicht unterstützt.');
        }

        if (! $this->isEnabled($provider)) {
            throw new RuntimeException('Dieser Social-Login-Anbieter ist noch nicht aktiviert.');
        }

        if ($provider !== 'steam' && (! $this->clientId($provider) || ! $this->clientSecret($provider))) {
            throw new RuntimeException('Dieser Social-Login-Anbieter ist noch nicht vollständig konfiguriert.');
        }

        return $provider;
    }

    public function redirectUrl(string $provider, string $state): string
    {
        $provider = $this->assertUsable($provider);

        if ($provider === 'steam') {
            return $this->steamRedirectUrl($state);
        }

        $query = [
            'client_id' => $this->clientId($provider),
            'redirect_uri' => $this->redirectUri($provider),
            'response_type' => 'code',
            'scope' => implode(' ', (array) config("social.providers.{$provider}.scopes", [])),
            'state' => $state,
        ];

        if ($provider === 'google') {
            $query['access_type'] = 'online';
            $query['prompt'] = 'select_account';
        }

        return (string) config("social.providers.{$provider}.authorize_url") . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function identity(string $provider, Request $request): SocialIdentity
    {
        $provider = $this->assertUsable($provider);

        return match ($provider) {
            'google' => $this->googleIdentity($request),
            'discord' => $this->discordIdentity($request),
            'twitch' => $this->twitchIdentity($request),
            'steam' => $this->steamIdentity($request),
            'microsoft' => $this->microsoftIdentity($request),
            'facebook' => $this->facebookIdentity($request),
        };
    }

    public function normalizeProvider(string $provider): string
    {
        return strtolower(trim($provider));
    }

    public function stateSessionKey(string $provider): string
    {
        return 'hunthub_social_state.' . $this->normalizeProvider($provider);
    }

    public function redirectUri(string $provider): string
    {
        $provider = $this->normalizeProvider($provider);

        return (string) config("social.providers.{$provider}.redirect_uri", route('social.callback', ['provider' => $provider]));
    }

    private function googleIdentity(Request $request): SocialIdentity
    {
        $token = $this->exchangeOAuthCode('google', $request);

        $profile = Http::timeout($this->timeout())
            ->acceptJson()
            ->withToken($token)
            ->get((string) config('social.providers.google.user_url'));

        if (! $profile->successful()) {
            throw new RuntimeException('Google-Profil konnte nicht geladen werden.');
        }

        $data = $profile->json();

        return new SocialIdentity(
            provider: 'google',
            providerUserId: (string) Arr::get($data, 'sub'),
            email: $this->normalizeEmail(Arr::get($data, 'email')),
            emailVerified: filter_var(Arr::get($data, 'email_verified'), FILTER_VALIDATE_BOOL),
            name: $this->cleanString(Arr::get($data, 'name')),
            nickname: $this->cleanString(Arr::get($data, 'given_name')),
            avatarUrl: $this->cleanString(Arr::get($data, 'picture')),
            raw: $data,
        );
    }

    private function discordIdentity(Request $request): SocialIdentity
    {
        $token = $this->exchangeOAuthCode('discord', $request);

        $profile = Http::timeout($this->timeout())
            ->acceptJson()
            ->withToken($token)
            ->get((string) config('social.providers.discord.user_url'));

        if (! $profile->successful()) {
            throw new RuntimeException('Discord-Profil konnte nicht geladen werden.');
        }

        $data = $profile->json();
        $id = (string) Arr::get($data, 'id');
        $avatar = Arr::get($data, 'avatar');
        $avatarUrl = $id && $avatar ? "https://cdn.discordapp.com/avatars/{$id}/{$avatar}.png?size=256" : null;

        return new SocialIdentity(
            provider: 'discord',
            providerUserId: $id,
            email: $this->normalizeEmail(Arr::get($data, 'email')),
            emailVerified: filter_var(Arr::get($data, 'verified'), FILTER_VALIDATE_BOOL),
            name: $this->cleanString(Arr::get($data, 'global_name')) ?: $this->cleanString(Arr::get($data, 'username')),
            nickname: $this->cleanString(Arr::get($data, 'username')),
            avatarUrl: $avatarUrl,
            raw: $data,
        );
    }

    private function twitchIdentity(Request $request): SocialIdentity
    {
        $token = $this->exchangeOAuthCode('twitch', $request);

        $profile = Http::timeout($this->timeout())
            ->acceptJson()
            ->withHeaders([
                'Client-ID' => $this->clientId('twitch'),
            ])
            ->withToken($token)
            ->get((string) config('social.providers.twitch.user_url'));

        if (! $profile->successful()) {
            throw new RuntimeException('Twitch-Profil konnte nicht geladen werden.');
        }

        $data = (array) Arr::first((array) Arr::get($profile->json(), 'data', []), default: []);

        return new SocialIdentity(
            provider: 'twitch',
            providerUserId: (string) Arr::get($data, 'id'),
            email: $this->normalizeEmail(Arr::get($data, 'email')),
            emailVerified: false,
            name: $this->cleanString(Arr::get($data, 'display_name')) ?: $this->cleanString(Arr::get($data, 'login')),
            nickname: $this->cleanString(Arr::get($data, 'login')),
            avatarUrl: $this->cleanString(Arr::get($data, 'profile_image_url')),
            raw: $data,
        );
    }

    private function microsoftIdentity(Request $request): SocialIdentity
    {
        $token = $this->exchangeOAuthCode('microsoft', $request);

        $profile = Http::timeout($this->timeout())
            ->acceptJson()
            ->withToken($token)
            ->get((string) config('social.providers.microsoft.user_url'));

        if (! $profile->successful()) {
            throw new RuntimeException('Microsoft-Profil konnte nicht geladen werden.');
        }

        $data = $profile->json();
        $email = $this->normalizeEmail(Arr::get($data, 'mail')) ?: $this->normalizeEmail(Arr::get($data, 'userPrincipalName'));

        return new SocialIdentity(
            provider: 'microsoft',
            providerUserId: (string) Arr::get($data, 'id'),
            email: $email,
            emailVerified: $email !== null,
            name: $this->cleanString(Arr::get($data, 'displayName')),
            nickname: $this->cleanString(Arr::get($data, 'givenName')),
            avatarUrl: null,
            raw: $data,
        );
    }

    private function facebookIdentity(Request $request): SocialIdentity
    {
        $token = $this->exchangeOAuthCode('facebook', $request);

        $profile = Http::timeout($this->timeout())
            ->acceptJson()
            ->withToken($token)
            ->get((string) config('social.providers.facebook.user_url'), [
                'fields' => 'id,name,email,picture.type(large)',
            ]);

        if (! $profile->successful()) {
            throw new RuntimeException('Facebook-Profil konnte nicht geladen werden.');
        }

        $data = $profile->json();
        $email = $this->normalizeEmail(Arr::get($data, 'email'));

        return new SocialIdentity(
            provider: 'facebook',
            providerUserId: (string) Arr::get($data, 'id'),
            email: $email,
            emailVerified: $email !== null,
            name: $this->cleanString(Arr::get($data, 'name')),
            nickname: null,
            avatarUrl: $this->cleanString(Arr::get($data, 'picture.data.url')),
            raw: $data,
        );
    }

    private function steamRedirectUrl(string $state): string
    {
        $returnTo = route('social.callback', ['provider' => 'steam', 'hh_state' => $state]);

        $query = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.realm' => rtrim((string) config('app.url'), '/'),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return 'https://steamcommunity.com/openid/login?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function steamIdentity(Request $request): SocialIdentity
    {
        $payload = [];

        foreach ($request->query() as $key => $value) {
            if ($key === 'hh_state') {
                continue;
            }

            $normalizedKey = str_starts_with((string) $key, 'openid_')
                ? 'openid.' . substr((string) $key, 7)
                : (string) $key;

            $payload[$normalizedKey] = $value;
        }

        $payload['openid.mode'] = 'check_authentication';

        $verification = Http::asForm()
            ->timeout($this->timeout())
            ->post('https://steamcommunity.com/openid/login', $payload);

        if (! $verification->successful() || ! Str::contains($verification->body(), 'is_valid:true')) {
            throw new RuntimeException('Steam-Login konnte nicht bestätigt werden.');
        }

        $identityUrl = $this->openidValue($request, 'openid.claimed_id', 'openid_claimed_id')
            ?: $this->openidValue($request, 'openid.identity', 'openid_identity');

        if (! $identityUrl || ! preg_match('~/(\d{15,25})$~', $identityUrl, $matches)) {
            throw new RuntimeException('Steam-ID konnte nicht gelesen werden.');
        }

        $steamId = $matches[1];
        $profile = $this->steamProfile($steamId);

        return new SocialIdentity(
            provider: 'steam',
            providerUserId: $steamId,
            email: null,
            emailVerified: false,
            name: $this->cleanString(Arr::get($profile, 'personaname')) ?: 'Steam Hunter',
            nickname: $this->cleanString(Arr::get($profile, 'personaname')),
            avatarUrl: $this->cleanString(Arr::get($profile, 'avatarfull')) ?: $this->cleanString(Arr::get($profile, 'avatarmedium')),
            raw: $profile ?: ['steamid' => $steamId],
        );
    }

    private function exchangeOAuthCode(string $provider, Request $request): string
    {
        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            throw new RuntimeException('Der Social-Login wurde ohne gültigen Code zurückgegeben.');
        }

        $response = Http::asForm()
            ->timeout($this->timeout())
            ->acceptJson()
            ->post((string) config("social.providers.{$provider}.token_url"), [
                'client_id' => $this->clientId($provider),
                'client_secret' => $this->clientSecret($provider),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri($provider),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Der Social-Login-Code konnte nicht eingelöst werden.');
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Der Social-Login hat keinen gültigen Zugriffstoken geliefert.');
        }

        return $token;
    }

    private function steamProfile(string $steamId): array
    {
        $apiKey = config('social.providers.steam.web_api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return [];
        }

        $response = Http::timeout($this->timeout())
            ->acceptJson()
            ->get('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/', [
                'key' => $apiKey,
                'steamids' => $steamId,
            ]);

        if (! $response->successful()) {
            return [];
        }

        return (array) Arr::first((array) Arr::get($response->json(), 'response.players', []), default: []);
    }

    private function openidValue(Request $request, string $dotKey, string $underscoreKey): ?string
    {
        $query = $request->query();
        $value = Arr::get($query, $dotKey) ?? Arr::get($query, $underscoreKey) ?? $request->query($dotKey) ?? $request->query($underscoreKey);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function clientId(string $provider): ?string
    {
        $value = config("social.providers.{$provider}.client_id");

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function clientSecret(string $provider): ?string
    {
        $value = config("social.providers.{$provider}.client_secret");

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function timeout(): int
    {
        return max(5, (int) config('social.timeout', 20));
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
