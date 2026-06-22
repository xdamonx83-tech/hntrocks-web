<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\HntMap;
use App\Models\HntMapCashSpotSubmission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MapCashSpotSubmissionApiController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
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
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'submitter_name' => ['nullable', 'string', 'max:80'],
            'submitter_email' => ['nullable', 'email', 'max:160'],
        ]);

        $image = $validated['image'];
        $realPath = $image->getRealPath();
        $imageInfo = is_string($realPath) ? @getimagesize($realPath) : false;
        $mimeType = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (! is_string($mimeType) || ! isset($extensions[$mimeType])) {
            throw ValidationException::withMessages([
                'image' => 'The file must be a valid JPG, PNG, or WebP image.',
            ]);
        }

        $path = $image->storeAs(
            'maps/cash-spot-submissions',
            Str::uuid().'.'.$extensions[$mimeType],
            'local',
        );

        abort_unless(is_string($path) && $path !== '', 500, 'The upload could not be stored.');

        $submission = HntMapCashSpotSubmission::query()->create([
            'hnt_map_id' => $map->id,
            'user_id' => $viewer?->id,
            'x' => (float) $validated['x'],
            'y' => (float) $validated['y'],
            'status' => HntMapCashSpotSubmission::STATUS_PENDING,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $image->getClientOriginalName(),
            'mime_type' => $mimeType,
            'size' => (int) $image->getSize(),
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
