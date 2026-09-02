<?php

namespace App\Http\Controllers\Api\V1\Arcade;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ArcadeMatchResource;
use App\Models\Arcade\ArcadeMatch;
use App\Services\Arcade\ArcadeMatchService;
use App\Services\Arcade\ArcadeMoveService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ArcadeMatchController extends Controller
{
    public function __construct(private readonly ArcadeMatchService $matches, private readonly ArcadeMoveService $moves) {}
    public function index(Request $request) { return ArcadeMatchResource::collection(ArcadeMatch::query()->whereHas('players', fn ($q) => $q->where('user_id', $request->user()->id))->with(['game', 'players.user'])->latest()->paginate(30)); }
    public function show(Request $request, ArcadeMatch $match): ArcadeMatchResource { $this->authorizeParticipant($match, $request); return new ArcadeMatchResource($match); }
    public function ready(Request $request, ArcadeMatch $match): ArcadeMatchResource { return new ArcadeMatchResource($this->matches->ready($match, $request->user())); }
    public function move(Request $request, ArcadeMatch $match): ArcadeMatchResource
    {
        $data = $request->validate([
            'client_move_id' => ['required', 'string', 'max:100'],
            'column' => ['sometimes', 'integer'],
            'payload' => ['sometimes', 'array'],
        ]);

        $hasLegacyColumn = array_key_exists('column', $data);
        $hasGenericPayload = array_key_exists('payload', $data);
        if ($hasLegacyColumn === $hasGenericPayload) {
            throw ValidationException::withMessages(['move' => 'Provide either column or payload.']);
        }

        $payload = $hasGenericPayload ? $data['payload'] : ['column' => $data['column']];

        return new ArcadeMatchResource($this->moves->move($match, $request->user(), $data['client_move_id'], $payload));
    }
    private function authorizeParticipant(ArcadeMatch $match, Request $request): void { if (! $match->players()->where('user_id', $request->user()->id)->exists()) throw new AccessDeniedHttpException; }
}
