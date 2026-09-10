<?php

namespace App\Services\Push;

use App\Models\User;
use App\Models\UserPushDevice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FcmPushService
{
    public function isConfigured(): bool
    {
        $account = $this->serviceAccount();

        return $this->projectId() !== ''
            && isset($account['client_email'], $account['private_key'])
            && trim((string) $account['client_email']) !== ''
            && trim((string) $account['private_key']) !== '';
    }

    public function sendToUser(User $user, string $title, string $body, ?string $actionUrl = null, array $data = []): array
    {
        $devices = $user->pushDevices()->active()->get();

        $sent = 0;
        $failed = 0;
        $responses = [];

        foreach ($devices as $device) {
            try {
                $responses[] = $this->sendToDevice($device, $title, $body, $actionUrl, $data);
                $sent++;
            } catch (Throwable $error) {
                $failed++;
                $responses[] = [
                    'device_id' => $device->id,
                    'ok' => false,
                    'error' => $error->getMessage(),
                ];
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'devices' => $devices->count(),
            'responses' => $responses,
        ];
    }

    public function sendToAllActiveDevices(string $title, string $body, ?string $actionUrl = null, array $data = []): array
    {
        $sent = 0;
        $failed = 0;
        $devices = 0;
        $failureDetails = [];

        UserPushDevice::query()
            ->active()
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use ($title, $body, $actionUrl, $data, &$sent, &$failed, &$devices, &$failureDetails): void {
                foreach ($chunk as $device) {
                    $devices++;

                    try {
                        $this->sendToDevice($device, $title, $body, $actionUrl, $data);
                        $sent++;
                    } catch (Throwable $error) {
                        $failed++;

                        // Keep the audit payload bounded even for a large broadcast.
                        if (count($failureDetails) < 100) {
                            $failureDetails[] = [
                                'device_id' => $device->id,
                                'ok' => false,
                                'error' => $error->getMessage(),
                            ];
                        }
                    }
                }
            });

        return [
            'sent' => $sent,
            'failed' => $failed,
            'devices' => $devices,
            'failures' => $failureDetails,
            'failures_truncated' => $failed > count($failureDetails),
        ];
    }

    public function sendToDevice(UserPushDevice $device, string $title, string $body, ?string $actionUrl = null, array $data = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Firebase Cloud Messaging is not configured.');
        }

        if (trim((string) $device->token) === '') {
            throw new RuntimeException('Push device has no token.');
        }

        $payload = [
            'message' => [
                'token' => $device->token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->stringData(array_merge([
                    'title' => $title,
                    'body' => $body,
                    'action_url' => $actionUrl ?: '',
                ], $data)),
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'click_action' => 'OPEN_HNT_NOTIFICATION',
                    ],
                ],
            ],
        ];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->post($this->endpoint(), $payload);

        if ($response->failed()) {
            $body = (string) $response->body();

            if ($this->looksLikeInvalidToken($body, $response->status())) {
                $device->markRevoked();
            }

            Log::warning('FCM push failed.', [
                'device_id' => $device->id,
                'status' => $response->status(),
                'body' => $body,
            ]);

            throw new RuntimeException('FCM push failed with HTTP '.$response->status().'.');
        }

        return [
            'device_id' => $device->id,
            'ok' => true,
            'name' => $response->json('name'),
        ];
    }

    private function accessToken(): string
    {
        $account = $this->serviceAccount();
        $clientEmail = trim((string) ($account['client_email'] ?? ''));
        $cacheKey = 'hnt_push_fcm_access_token_'.sha1($this->projectId().'|'.$clientEmail);

        return Cache::remember($cacheKey, now()->addMinutes(50), fn (): string => $this->fetchAccessToken($account));
    }

    private function fetchAccessToken(array $account): string
    {
        $jwt = $this->signedJwt($account);

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($this->timeout())
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if ($response->failed()) {
            Log::warning('FCM access token request failed.', [
                'status' => $response->status(),
                'body' => (string) $response->body(),
            ]);

            throw new RuntimeException('Could not fetch Firebase access token.');
        }

        $token = trim((string) $response->json('access_token'));

        if ($token === '') {
            throw new RuntimeException('Firebase access token response was empty.');
        }

        return $token;
    }

    private function signedJwt(array $account): string
    {
        $clientEmail = trim((string) ($account['client_email'] ?? ''));
        $privateKey = $this->normalizePrivateKey((string) ($account['private_key'] ?? ''));

        if ($clientEmail === '' || $privateKey === '') {
            throw new RuntimeException('Firebase service account is incomplete.');
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $input = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            .'.'.$this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));

        $signature = '';
        $signed = openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $signed) {
            throw new RuntimeException('Could not sign Firebase JWT.');
        }

        return $input.'.'.$this->base64UrlEncode($signature);
    }

    private function serviceAccount(): array
    {
        $rawJson = trim((string) config('push.fcm.service_account_json'));

        if ($rawJson !== '') {
            return $this->decodeServiceAccountJson($rawJson);
        }

        $path = trim((string) config('push.fcm.service_account_path'));

        if ($path === '') {
            return [];
        }

        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        return $this->decodeServiceAccountJson((string) file_get_contents($path));
    }

    private function decodeServiceAccountJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function endpoint(): string
    {
        return 'https://fcm.googleapis.com/v1/projects/'.$this->projectId().'/messages:send';
    }

    private function projectId(): string
    {
        $configured = trim((string) config('push.fcm.project_id'));
        if ($configured !== '') {
            return $configured;
        }

        return trim((string) ($this->serviceAccount()['project_id'] ?? ''));
    }

    private function timeout(): int
    {
        return max(5, (int) config('push.fcm.timeout', 15));
    }

    private function normalizePrivateKey(string $key): string
    {
        return str_replace('\\n', "\n", trim($key));
    }

    private function stringData(array $data): array
    {
        $payload = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $payload[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
        }

        return $payload;
    }

    private function looksLikeInvalidToken(string $body, int $status): bool
    {
        if (! in_array($status, [400, 404], true)) {
            return false;
        }

        return str_contains($body, 'UNREGISTERED')
            || str_contains($body, 'INVALID_ARGUMENT')
            || str_contains($body, 'registration token is not a valid');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
