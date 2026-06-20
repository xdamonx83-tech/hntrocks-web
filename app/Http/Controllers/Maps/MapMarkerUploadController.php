<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MapMarkerUploadController extends Controller
{
    public function store(Request $request, HntMapMarker $marker): JsonResponse
    {
        $honeypot = $request->validate([
            'website' => ['nullable', 'string', 'max:120'],
        ]);

        if (trim((string) ($honeypot['website'] ?? '')) !== '') {
            return response()->json([
                'ok' => true,
                'status' => HntMapMarkerUpload::STATUS_PENDING,
                'upload' => ['status' => HntMapMarkerUpload::STATUS_PENDING],
            ], 201);
        }

        if ($marker->type !== 'cash' || $marker->status !== 'approved') {
            throw ValidationException::withMessages([
                'image' => 'Bilder können nur für freigegebene Cash-Marker eingereicht werden.',
            ]);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'submitter_name' => ['nullable', 'string', 'max:80'],
            'submitter_email' => ['nullable', 'email', 'max:160'],
        ]);

        $image = $validated['image'];
        $realPath = $image->getRealPath();
        $imageInfo = is_string($realPath) ? @getimagesize($realPath) : false;
        $mimeType = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! is_string($mimeType) || ! isset($extensions[$mimeType])) {
            throw ValidationException::withMessages([
                'image' => 'Die Datei ist kein gültiges JPG-, PNG- oder WebP-Bild.',
            ]);
        }

        $extension = $extensions[$mimeType];
        $path = $image->storeAs(
            'maps/cash-spot-submissions',
            Str::uuid().'.'.$extension,
            'local',
        );

        if (! is_string($path) || $path === '') {
            abort(500, 'Der Upload konnte nicht gespeichert werden.');
        }

        $upload = $marker->uploads()->create([
            'uploaded_by' => $request->user()?->id,
            'submitter_name' => $this->nullableTrimmedString($validated['submitter_name'] ?? null),
            'submitter_email' => $this->nullableTrimmedString($validated['submitter_email'] ?? null),
            'ip_hash' => $this->requestValueHash($request->ip()),
            'user_agent_hash' => $this->requestValueHash($request->userAgent()),
            'disk' => 'local',
            'path' => $path,
            'public_path' => null,
            'original_name' => $image->getClientOriginalName(),
            'mime_type' => $mimeType,
            'size' => (int) $image->getSize(),
            'status' => HntMapMarkerUpload::STATUS_PENDING,
        ]);

        return response()->json([
            'ok' => true,
            'status' => HntMapMarkerUpload::STATUS_PENDING,
            'upload' => [
                'id' => $upload->id,
                'status' => $upload->status,
            ],
        ], 201);
    }

    private function requestValueHash(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === ''
            ? null
            : hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
