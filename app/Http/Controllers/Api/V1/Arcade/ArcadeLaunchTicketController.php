<?php

namespace App\Http\Controllers\Api\V1\Arcade;

use App\Http\Controllers\Controller;
use App\Services\Arcade\ArcadeGameCatalogService;
use App\Services\Arcade\ArcadeLaunchTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArcadeLaunchTicketController extends Controller
{
    public function __construct(
        private readonly ArcadeGameCatalogService $catalog,
        private readonly ArcadeLaunchTicketService $tickets,
    ) {}

    public function store(Request $request, string $game): JsonResponse
    {
        $data = $request->validate([
            'client' => ['required', Rule::in(['web', 'flutter'])],
            'match_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $arcadeGame = $this->catalog->visibleGame($game);
        [$playable, $reason] = $this->catalog->availability($arcadeGame);
        if (! $playable) {
            throw ValidationException::withMessages(['game' => 'The Arcade game is not launchable: '.($reason ?? 'unavailable').'.']);
        }

        return response()->json([
            'data' => $this->tickets->issue(
                $request->user(),
                $arcadeGame,
                (string) $data['client'],
                isset($data['match_id']) ? (int) $data['match_id'] : null,
            ),
        ], 201);
    }

    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate(['ticket' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => $this->tickets->exchange((string) $data['ticket'])]);
    }
}
