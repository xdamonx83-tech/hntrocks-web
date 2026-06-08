<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserPushDevice;
use App\Services\Push\FcmPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiPushDeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()
            ->pushDevices()
            ->active()
            ->latest('last_seen_at')
            ->get()
            ->map(fn (UserPushDevice $device): array => $this->devicePayload($device))
            ->values();

        return response()->json([
            'message' => 'Push devices loaded.',
            'devices' => $devices,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'max:4096'],
            'provider' => ['nullable', 'string', 'in:fcm'],
            'platform' => ['nullable', 'string', 'in:android'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'device_name' => ['nullable', 'string', 'max:191'],
            'app_version' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:80'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $token = trim((string) $data['token']);
        $tokenHash = UserPushDevice::hashToken($token);

        $device = UserPushDevice::query()->updateOrCreate(
            ['token_hash' => $tokenHash],
            [
                'user_id' => $request->user()->id,
                'provider' => $data['provider'] ?? 'fcm',
                'platform' => $data['platform'] ?? 'android',
                'device_id' => $data['device_id'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'locale' => $data['locale'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'token' => $token,
                'last_seen_at' => now(),
                'disabled_at' => null,
                'revoked_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Push device registered.',
            'device' => $this->devicePayload($device),
            'counts' => [
                'push_devices' => $request->user()->pushDevices()->active()->count(),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['nullable', 'string', 'max:4096'],
            'device_id' => ['nullable', 'string', 'max:191'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $token = trim((string) ($data['token'] ?? ''));
        $deviceId = trim((string) ($data['device_id'] ?? ''));

        if ($token === '' && $deviceId === '') {
            return response()->json([
                'message' => 'Token or device_id is required.',
                'errors' => [
                    'token' => ['Token or device_id is required.'],
                ],
            ], 422);
        }

        $query = $request->user()->pushDevices()->active();

        if ($token !== '') {
            $query->where('token_hash', UserPushDevice::hashToken($token));
        } else {
            $query->where('device_id', $deviceId);
        }

        $revoked = 0;
        $query->get()->each(function (UserPushDevice $device) use (&$revoked): void {
            $device->markRevoked();
            $revoked++;
        });

        return response()->json([
            'message' => 'Push device removed.',
            'revoked' => $revoked,
            'counts' => [
                'push_devices' => $request->user()->pushDevices()->active()->count(),
            ],
        ]);
    }

    public function test(Request $request, FcmPushService $push): JsonResponse
    {
        if (! $push->isConfigured()) {
            return response()->json([
                'message' => 'Firebase Cloud Messaging ist serverseitig noch nicht konfiguriert.',
                'errors' => [
                    'fcm' => [
                        'Bitte Firebase Service Account JSON auf dem Server hinterlegen und HNT_PUSH_FCM_PROJECT_ID setzen.',
                    ],
                ],
            ], 422);
        }

        $activeDevices = $request->user()->pushDevices()->active()->count();

        if ($activeDevices < 1) {
            return response()->json([
                'message' => 'Für diesen Account ist noch kein aktives Push-Gerät registriert.',
                'errors' => [
                    'devices' => ['Bitte zuerst das Gerät in der App registrieren.'],
                ],
            ], 422);
        }

        $result = $push->sendToUser(
            $request->user(),
            'hnt.rocks Push-Test',
            'Wenn du diese Nachricht siehst, funktioniert der Push-Weg bis zu diesem Gerät.',
            'hntrocks://notifications',
            [
                'type' => 'push_test',
                'target' => 'notifications',
            ]
        );

        return response()->json([
            'message' => 'Test push sent.',
            'result' => $result,
            'counts' => [
                'push_devices' => $request->user()->pushDevices()->active()->count(),
            ],
        ]);
    }

    private function devicePayload(UserPushDevice $device): array
    {
        return [
            'id' => $device->id,
            'provider' => $device->provider,
            'platform' => $device->platform,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'app_version' => $device->app_version,
            'locale' => $device->locale,
            'timezone' => $device->timezone,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            'created_at' => $device->created_at?->toIso8601String(),
            'updated_at' => $device->updated_at?->toIso8601String(),
        ];
    }
}
