<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserLoadoutResource;
use App\Models\User;
use App\Models\UserLoadout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiUserLoadoutController extends Controller
{
    private const LIMIT = 3;

    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->loadouts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'section' => 'loadouts',
            'items' => UserLoadoutResource::collection($items),
            'meta' => [
                'total' => $items->count(),
                'limit' => self::LIMIT,
                'truncated' => false,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeArrays($request);
        $data = $this->validated($request);

        $loadout = DB::transaction(function () use ($request, $data): UserLoadout {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            if ($user->loadouts()->where('is_active', true)->count() >= self::LIMIT) {
                abort(422, 'You may have at most 3 active loadouts.');
            }

            return $user->loadouts()->create($data);
        });

        return response()->json(['data' => new UserLoadoutResource($loadout)], 201);
    }

    public function update(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ownedLoadout($request, $loadout);
        $this->normalizeArrays($request);
        $loadout->update($this->validated($request, true));

        return response()->json(['data' => new UserLoadoutResource($loadout->refresh())]);
    }

    public function destroy(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ownedLoadout($request, $loadout);
        $loadout->delete();

        return response()->json(['message' => 'Loadout deleted.']);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        $safeText = $this->safePlainTextRule();
        $required = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:80', $safeText],
            'primary_weapon' => ['sometimes', 'nullable', 'string', 'max:80', $safeText],
            'secondary_weapon' => ['sometimes', 'nullable', 'string', 'max:80', $safeText],
            'tools' => ['sometimes', 'nullable', 'array', 'max:4'],
            'tools.*' => ['string', 'max:80', $safeText],
            'consumables' => ['sometimes', 'nullable', 'array', 'max:4'],
            'consumables.*' => ['string', 'max:80', $safeText],
            'traits' => ['sometimes', 'nullable', 'array', 'max:6'],
            'traits.*' => ['string', 'max:80', $safeText],
            'note' => ['sometimes', 'nullable', 'string', 'max:500', $safeText],
            'visibility' => ['sometimes', Rule::in(['public', 'private'])],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    private function normalizeArrays(Request $request): void
    {
        $normalized = [];

        foreach (['tools', 'consumables', 'traits'] as $field) {
            if (! $request->exists($field) || ! is_array($request->input($field))) {
                continue;
            }

            $values = array_map(
                fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
                $request->input($field)
            );
            $values = array_filter($values, fn (mixed $value): bool => ! is_string($value) || $value !== '');
            $normalized[$field] = array_values(array_unique($values, SORT_REGULAR));
        }

        $request->merge($normalized);
    }

    private function safePlainTextRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value)) {
                return;
            }

            if (preg_match('~<[^>]*>|(?:https?://|www\.|://|discord\.gg|discord(?:app)?\.com/invite|steamcommunity\.com|invite\.gg)~i', $value)) {
                $fail('External links and HTML are not allowed.');
            }
        };
    }

    private function ownedLoadout(Request $request, UserLoadout $loadout): void
    {
        abort_unless((int) $loadout->user_id === (int) $request->user()->id, 404);
    }
}
