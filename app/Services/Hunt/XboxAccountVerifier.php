<?php

namespace App\Services\Hunt;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class XboxAccountVerifier
{
    private const MSA = 'https://login.microsoftonline.com/consumers/oauth2/v2.0';
    private const USER_AUTH = 'https://user.auth.xboxlive.com/user/authenticate';
    private const XSTS = 'https://xsts.auth.xboxlive.com/xsts/authorize';

    public function isConfigured(): bool
    {
        // According to Microsoft GDK docs, persisting Xbox XUIDs requires
        // explicit written permission. Never enable by default.
        return config('hunt_platform.xbox.enabled') === true
            && config('hunt_platform.xbox.xuid_storage_authorized') === true
            && filled(config('hunt_platform.xbox.client_id'))
            && filled(config('hunt_platform.xbox.client_secret'));
    }

    public function authorizeUrl(string $state): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('not_configured');
        }

        return self::MSA.'/authorize?'.http_build_query([
            'client_id' => config('hunt_platform.xbox.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->callbackUrl(),
            'response_mode' => 'query',
            'scope' => 'xboxlive.signin',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function verifyAuthorizationCode(string $code): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('not_configured');
        }

        // This is the documented Microsoft-account -> Xbox User Token ->
        // Xbox XSTS delegated user authentication flow. No raw tokens stored.
        $token = Http::asForm()->acceptJson()->timeout(12)
            ->post(self::MSA.'/token', [
                'client_id' => config('hunt_platform.xbox.client_id'),
                'client_secret' => config('hunt_platform.xbox.client_secret'),
                'redirect_uri' => $this->callbackUrl(),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'scope' => 'xboxlive.signin',
            ]);

        $accessToken = $token->successful() ? $token->json('access_token') : null;
        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('microsoft_authorization_failed');
        }

        $user = Http::asJson()->acceptJson()->timeout(12)
            ->withHeaders(['x-xbl-contract-version' => '1'])
            ->post(self::USER_AUTH, [
                'Properties' => [
                    'AuthMethod' => 'RPS',
                    'SiteName' => 'user.auth.xboxlive.com',
                    'RpsTicket' => 'd='.$accessToken,
                ],
                'RelyingParty' => 'http://auth.xboxlive.com',
                'TokenType' => 'JWT',
            ]);

        $userToken = $user->successful() ? $user->json('Token') : null;
        if (! is_string($userToken) || $userToken === '') {
            throw new RuntimeException('xbox_authorization_failed');
        }

        $xsts = Http::asJson()->acceptJson()->timeout(12)
            ->withHeaders(['x-xbl-contract-version' => '1'])
            ->post(self::XSTS, [
                'Properties' => [
                    'SandboxId' => 'RETAIL',
                    'UserTokens' => [$userToken],
                    'OptionalDisplayClaims' => ['xid', 'gtg'],
                ],
                'RelyingParty' => 'http://xboxlive.com',
                'TokenType' => 'JWT',
            ]);

        $identity = $xsts->successful() ? $xsts->json('DisplayClaims.xui.0') : null;
        $xuid = is_array($identity) ? ($identity['xid'] ?? null) : null;
        if (! is_string($xuid) || preg_match('/^[0-9]{10,20}$/D', $xuid) !== 1) {
            throw new RuntimeException('xbox_identity_unavailable');
        }

        $gamertag = is_array($identity) && is_string($identity['gtg'] ?? null)
            ? trim($identity['gtg']) : null;

        return [
            'provider_user_id' => $xuid,
            'provider_name' => $gamertag !== '' ? mb_substr((string) $gamertag, 0, 120) : null,
        ];
    }

    private function callbackUrl(): string
    {
        return route('hunt.game.xbox.callback');
    }
}
