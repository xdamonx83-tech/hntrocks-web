<?php

namespace App\Services\Hunt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SteamOpenIdVerifier
{
    private const ENDPOINT = 'https://steamcommunity.com/openid/login';
    private const NS = 'http://specs.openid.net/auth/2.0';

    public function authorizeUrl(string $state): string
    {
        $params = [
            'openid.ns' => self::NS,
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $this->returnTo($state),
            'openid.realm' => rtrim((string) config('app.url'), '/'),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return self::ENDPOINT.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function verify(Request $request, string $state): string
    {
        // Check local callback binding before contacting Steam.
        if ($request->query('openid_return_to') !== $this->returnTo($state)
            || $request->query('openid_ns') !== self::NS
            || $request->query('openid_op_endpoint') !== self::ENDPOINT
            || $request->query('openid_mode') !== 'id_res') {
            throw new RuntimeException('invalid_response');
        }

        // Steam validates only the fields listed in openid.signed. Refuse
        // any callback where security-critical identity/return fields were
        // not included in the signature that Steam validates.
        $signed = $request->query('openid_signed');
        $signature = $request->query('openid_sig');
        $nonce = $request->query('openid_response_nonce');
        if (! is_string($signed) || ! is_string($signature)
            || $signature === '' || ! is_string($nonce) || $nonce === '') {
            throw new RuntimeException('invalid_response');
        }
        $fields = array_map('trim', explode(',', $signed));
        foreach (['op_endpoint', 'claimed_id', 'identity', 'return_to', 'response_nonce'] as $field) {
            if (! in_array($field, $fields, true)) {
                throw new RuntimeException('invalid_response');
            }
        }

        $claimed = $request->query('openid_claimed_id');
        $identity = $request->query('openid_identity');
        if (! is_string($claimed) || $identity !== $claimed
            || ! preg_match('~^https?://steamcommunity\.com/openid/id/([0-9]{17})$~D', $claimed, $match)) {
            throw new RuntimeException('invalid_identity');
        }

        $params = [];
        foreach ($request->query() as $key => $value) {
            if (! str_starts_with((string) $key, 'openid_')) {
                continue;
            }
            // Never forward arrays or arbitrary query keys to the verifier.
            if (! is_string($value)) {
                throw new RuntimeException('invalid_response');
            }
            $params['openid.'.substr($key, 7)] = $value;
        }
        $params['openid.mode'] = 'check_authentication';

        $response = Http::asForm()
            ->timeout(12)
            ->post(self::ENDPOINT, $params);

        if (! $response->successful()
            || preg_match('/^is_valid:true\s*$/m', $response->body()) !== 1) {
            throw new RuntimeException('verification_failed');
        }

        return $match[1];
    }

    private function returnTo(string $state): string
    {
        return route('hunt.game.steam.callback', ['state' => $state]);
    }
}
