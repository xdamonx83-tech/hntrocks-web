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
        if ($marker->type !== 'cash' || $marker->status !== 'approved') {
            throw ValidationException::withMessages([
                'image' => 'Bilder können nur für freigegebene Cash-Marker eingereicht werden.',
            ]);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ]);

        $image = $validated['image'];
        $extension = strtolower($image->guessExtension() ?: $image->extension() ?: 'jpg');
        $path = $image->storeAs(
            'maps/cash-spot-submissions',
            Str::uuid().'.'.$extension,
            'local',
        );

        if (! is_string($path) || $path === '') {
            abort(500, 'Der Upload konnte nicht gespeichert werden.');
        }

        $upload = $marker->uploads()->create([
            'uploaded_by' => $request->user()->id,
            'disk' => 'local',
            'path' => $path,
            'public_path' => null,
            'original_name' => $image->getClientOriginalName(),
            'mime_type' => $image->getMimeType() ?: 'application/octet-stream',
            'size' => (int) $image->getSize(),
            'status' => HntMapMarkerUpload::STATUS_PENDING,
        ]);

        return response()->json([
            'ok' => true,
            'upload' => [
                'id' => $upload->id,
                'status' => $upload->status,
            ],
        ], 201);
    }
}
