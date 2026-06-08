<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, strlen(self::BASE32_ALPHABET) - 1)];
        }

        return $secret;
    }

    public function keyUri(User $user, string $secret): string
    {
        $issuer = 'HNT.rocks';
        $label = $issuer . ':' . ($user->email ?: $user->username ?: ('user-' . $user->id));

        return 'otpauth://totp/' . rawurlencode($label)
            . '?secret=' . rawurlencode($this->normalizeSecret($secret))
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    public function verifyCode(?string $secret, string $code, int $window = 1): bool
    {
        $secret = $this->normalizeSecret((string) $secret);
        $code = preg_replace('/\D+/', '', $code) ?: '';

        if ($secret === '' || strlen($code) !== 6) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $expected = $this->totp($secret, $timeSlice + $i);

            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(5) . '-' . Str::random(5));
        }

        return $codes;
    }

    public function recoveryHashes(array $codes): array
    {
        return array_map(fn (string $code): string => Hash::make($this->normalizeRecoveryCode($code)), $codes);
    }

    public function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        $normalized = $this->normalizeRecoveryCode($code);

        if ($normalized === '') {
            return false;
        }

        $hashes = is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : [];

        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && Hash::check($normalized, $hash)) {
                unset($hashes[$index]);

                $user->forceFill([
                    'two_factor_recovery_codes' => array_values($hashes),
                ])->save();

                return true;
            }
        }

        return false;
    }

    public function recoveryCodeCount(User $user): int
    {
        return count(is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : []);
    }

    private function totp(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $binaryTime = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $binaryTime, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $secret = $this->normalizeSecret($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        foreach (str_split($secret) as $char) {
            $value = strpos(self::BASE32_ALPHABET, $char);

            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }

    private function normalizeSecret(string $secret): string
    {
        return strtoupper(preg_replace('/[^A-Z2-7]+/i', '', $secret) ?: '');
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $code) ?: '');
    }
}
