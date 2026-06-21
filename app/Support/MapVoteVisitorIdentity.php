<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class MapVoteVisitorIdentity
{
    public const COOKIE_NAME = 'hnt_map_vote_visitor';

    /**
     * @return array{token: string, hash: string, is_new: bool}
     */
    public function resolve(Request $request): array
    {
        $token = $this->tokenFromRequest($request);
        $isNew = $token === null;

        if ($token === null) {
            $token = (string) Str::uuid();
        }

        return [
            'token' => $token,
            'hash' => $this->hash($token),
            'is_new' => $isNew,
        ];
    }

    public function hashFromRequest(Request $request): ?string
    {
        $token = $this->tokenFromRequest($request);

        return $token === null ? null : $this->hash($token);
    }

    public function requestValueHash(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $this->hash($value);
    }

    public function cookie(string $token, Request $request): SymfonyCookie
    {
        return Cookie::make(
            self::COOKIE_NAME,
            $token,
            60 * 24 * 365 * 2,
            null,
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        );
    }

    private function tokenFromRequest(Request $request): ?string
    {
        $token = trim((string) $request->cookie(self::COOKIE_NAME, ''));

        return Str::isUuid($token) ? $token : null;
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
