<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use App\Models\HntMapCashSpotSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MapCashSpotSubmissionController extends Controller
{
    public function store(Request $request, HntMap $map): JsonResponse
    {
        $honeypot = $request->validate([
            'website' => ['nullable', 'string', 'max:120'],
        ]);

        if (trim((string) ($honeypot['website'] ?? '')) !== '') {
            return $this->pendingResponse();
        }

        abort_unless($map->is_active, 404);

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
                'image' => 'Die Datei ist kein gültiges JPG-, PNG- oder WebP-Bild.',
            ]);
        }

        $path = $image->storeAs(
            'maps/cash-spot-submissions',
            Str::uuid().'.'.$extensions[$mimeType],
            'local',
        );

        abort_unless(is_string($path) && $path !== '', 500, 'Der Upload konnte nicht gespeichert werden.');

        HntMapCashSpotSubmission::query()->create([
            'hnt_map_id' => $map->id,
            'user_id' => $request->user()?->id,
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

        return $this->pendingResponse();
    }

    private function pendingResponse(): JsonResponse
    {
        return response()->json(['ok' => true, 'status' => HntMapCashSpotSubmission::STATUS_PENDING], 201);
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
