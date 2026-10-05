<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\HntMap;
use App\Models\HntMapCashSpotSubmission;
use App\Services\Maps\CashSpotImageOptimizer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapCashSpotSubmissionApiController extends Controller
{
    public function store(Request $request, string $slug, CashSpotImageOptimizer $optimizer): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $viewer = $this->optionalApiUser($request);
        $map = HntMap::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'x' => ['required', 'numeric', 'min:0', 'max:'.$map->width],
            'y' => ['required', 'numeric', 'min:0', 'max:'.$map->height],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:40960'],
            'description' => ['nullable', 'string', 'max:1000'],
            'submitter_name' => ['nullable', 'string', 'max:80'],
            'submitter_email' => ['nullable', 'email', 'max:160'],
        ]);

        $image = $validated['image'];
        $stored = $optimizer->store($image);

        $submission = HntMapCashSpotSubmission::query()->create([
            'hnt_map_id' => $map->id,
            'user_id' => $viewer?->id,
            'x' => (float) $validated['x'],
            'y' => (float) $validated['y'],
            'status' => HntMapCashSpotSubmission::STATUS_PENDING,
            'disk' => 'local',
            'path' => $stored['path'],
            'original_name' => $image->getClientOriginalName(),
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
            'description' => $this->nullableTrimmedString($validated['description'] ?? null),
            'submitter_name' => $this->nullableTrimmedString($validated['submitter_name'] ?? null),
            'submitter_email' => $this->nullableTrimmedString($validated['submitter_email'] ?? null),
            'ip_hash' => $this->requestValueHash($request->ip()),
            'user_agent_hash' => $this->requestValueHash($request->userAgent()),
        ]);

        return response()->json([
            'ok' => true,
            'status' => HntMapCashSpotSubmission::STATUS_PENDING,
            'submission' => [
                'id' => $submission->id,
                'map_slug' => $map->slug,
                'x' => $submission->x,
                'y' => $submission->y,
                'status' => $submission->status,
            ],
            'message' => 'Cash spot submitted for review.',
        ], 201);
    }

    private function optionalApiUser(Request $request): ?User
    {
        if (! $request->hasHeader('Authorization')) {
            return null;
        }

        $token = ApiAccessToken::findValidPlainToken($request->bearerToken());

        if (! $token || ! $token->user) {
            abort(401, 'Unauthenticated.');
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($token->user);
        $request->setUserResolver(fn (): User => $token->user);
        $request->attributes->set('api_access_token', $token);

        return $token->user;
    }

    private function requestValueHash(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
