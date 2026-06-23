<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LiveLobbyResource;
use App\Models\LiveLobby;
use App\Models\LiveLobbyMember;
use App\Models\User;
use App\Services\LiveLobbyNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiLiveLobbyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->expireDueLobbies();
        $filters = $request->validate([
            'platform' => ['nullable', Rule::in(['pc', 'playstation', 'xbox'])],
            'crossplay_pool' => ['nullable', Rule::in(['pc', 'console'])],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'mode' => ['nullable', Rule::in(['duo', 'trio'])],
        ]);

        $lobbies = LiveLobby::query()
            ->with($this->relations())
            ->active()
            ->when($filters['platform'] ?? null, fn ($query, $value) => $query->where('platform', $value))
            ->when($filters['crossplay_pool'] ?? null, fn ($query, $value) => $query->where('crossplay_pool', $value))
            ->when($filters['region'] ?? null, fn ($query, $value) => $query->where('region', $value))
            ->when($filters['language'] ?? null, fn ($query, $value) => $query->where('language', $value))
            ->when($filters['mode'] ?? null, fn ($query, $value) => $query->where('mode', $value))
            ->latest()
            ->paginate(20);

        return LiveLobbyResource::collection($lobbies);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        $this->expireDueLobbies();

        $lobbies = LiveLobby::query()
            ->with($this->relations())
            ->active()
            ->where(function (Builder $query) use ($request): void {
                $query->where('creator_id', $request->user()->id)
                    ->orWhereHas('activeMembers', fn ($members) => $members->where('user_id', $request->user()->id));
            })
            ->latest()
            ->paginate(20);

        return LiveLobbyResource::collection($lobbies);
    }

    public function store(Request $request, LiveLobbyNotificationService $notifications): JsonResponse
    {
        $validated = $this->validatedLobby($request);
        $user = $request->user();

        $lobby = DB::transaction(function () use ($validated, $user): LiveLobby {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($this->hasActiveMembership($user->id)) {
                abort(response()->json(['message' => $this->message('already_active')], 422));
            }

            $lobby = LiveLobby::query()->create([
                ...$validated,
                'creator_id' => $user->id,
                'slots_total' => $validated['mode'] === 'duo' ? 2 : 3,
                'slots_filled' => 1,
                'crossplay_pool' => $this->poolFor($validated['platform']),
                'voice_required' => $request->boolean('voice_required'),
                'status' => 'open',
                'expires_at' => now()->addMinutes(15),
            ]);

            $lobby->members()->create([
                'user_id' => $user->id,
                'role' => 'creator',
                'platform' => $validated['platform'],
                'platform_handle' => $validated['platform_handle'] ?? null,
                'joined_at' => now(),
            ]);

            return $lobby;
        });

        $lobby = $this->freshLobby($lobby);
        $notifications->announce($lobby);

        return response()->json([
            'message' => $this->message('created'),
            'data' => new LiveLobbyResource($lobby),
        ], 201);
    }

    public function show(Request $request, LiveLobby $lobby): JsonResponse
    {
        $lobby->expireIfNeeded();

        return response()->json(['data' => new LiveLobbyResource($this->freshLobby($lobby))]);
    }

    public function join(Request $request, LiveLobby $lobby, LiveLobbyNotificationService $notifications): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['pc', 'playstation', 'xbox'])],
            'platform_handle' => ['nullable', 'string', 'max:100', $this->noExternalLinksRule()],
        ]);
        $user = $request->user();

        $becameFull = DB::transaction(function () use ($lobby, $user, $validated): bool {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $locked = LiveLobby::query()->whereKey($lobby->id)->lockForUpdate()->firstOrFail();
            $locked->expireIfNeeded();

            if ($locked->status !== 'open') {
                abort(response()->json(['message' => $this->message('not_open')], 422));
            }
            if ($this->hasActiveMembership($user->id)) {
                abort(response()->json(['message' => $this->message('already_active')], 422));
            }
            if (! $locked->acceptsPlatform($validated['platform'])) {
                abort(response()->json(['message' => $this->message('incompatible')], 422));
            }

            $locked->members()->create([
                'user_id' => $user->id,
                'role' => 'member',
                'platform' => $validated['platform'],
                'platform_handle' => $validated['platform_handle'] ?? null,
                'joined_at' => now(),
            ]);

            $locked->slots_filled = $locked->activeMembers()->count();
            $becameFull = $locked->slots_filled >= $locked->slots_total;
            if ($becameFull) {
                $locked->status = 'full';
                $locked->full_at = now();
                $locked->expires_at = now()->addMinutes(5);
            }
            $locked->save();

            return $becameFull;
        });

        $lobby = $this->freshLobby($lobby);
        $notifications->joined($lobby, $user);
        if ($becameFull) {
            $notifications->full($lobby, $user);
        }

        return response()->json(['message' => $this->message('joined'), 'data' => new LiveLobbyResource($lobby)]);
    }

    public function leave(Request $request, LiveLobby $lobby): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($lobby, $user): void {
            $locked = LiveLobby::query()->whereKey($lobby->id)->lockForUpdate()->firstOrFail();
            $membership = $locked->activeMembers()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $membership) {
                abort(response()->json(['message' => $this->message('not_member')], 422));
            }

            if ((int) $locked->creator_id === (int) $user->id) {
                $locked->update(['status' => 'closed', 'closed_at' => now()]);
                $locked->activeMembers()->update(['left_at' => now()]);
                return;
            }

            $membership->update(['left_at' => now()]);
            $locked->slots_filled = $locked->activeMembers()->count();
            if ($locked->status === 'full' && $locked->expires_at->isFuture()) {
                $locked->status = 'open';
                $locked->full_at = null;
                $locked->expires_at = now()->addMinutes(15);
            }
            $locked->save();
        });

        return response()->json(['message' => $this->message('left'), 'data' => new LiveLobbyResource($this->freshLobby($lobby))]);
    }

    public function close(Request $request, LiveLobby $lobby): JsonResponse
    {
        if ((int) $lobby->creator_id !== (int) $request->user()->id) {
            return response()->json(['message' => $this->message('creator_only')], 403);
        }

        DB::transaction(function () use ($lobby): void {
            $lobby->update(['status' => 'closed', 'closed_at' => now()]);
            $lobby->activeMembers()->update(['left_at' => now()]);
        });

        return response()->json(['message' => $this->message('closed'), 'data' => new LiveLobbyResource($this->freshLobby($lobby))]);
    }

    private function validatedLobby(Request $request): array
    {
        $safeText = $this->noExternalLinksRule();

        return $request->validate([
            'mode' => ['required', Rule::in(['duo', 'trio'])],
            'platform' => ['required', Rule::in(['pc', 'playstation', 'xbox'])],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'voice_required' => ['nullable', 'boolean'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:500', $safeText],
            'lobby_code' => ['nullable', 'string', 'max:64', $safeText],
            'steam_id' => ['nullable', 'string', 'max:100', $safeText],
            'psn_id' => ['nullable', 'string', 'max:100', $safeText],
            'xbox_gamertag' => ['nullable', 'string', 'max:100', $safeText],
            'discord_handle' => ['nullable', 'string', 'max:100', $safeText],
            'platform_handle' => ['nullable', 'string', 'max:100', $safeText],
        ]);
    }

    private function noExternalLinksRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && preg_match('~(?:https?://|www\.|://|discord\.gg|discord(?:app)?\.com/invite|steamcommunity\.com|invite\.gg)~i', $value)) {
                $fail($this->message('links_blocked'));
            }
        };
    }

    private function hasActiveMembership(int $userId): bool
    {
        return LiveLobbyMember::query()
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->whereHas('lobby', fn ($query) => $query->active())
            ->exists();
    }

    private function expireDueLobbies(): void
    {
        LiveLobby::query()->whereIn('status', LiveLobby::ACTIVE_STATUSES)->where('expires_at', '<=', now())->update(['status' => 'expired']);
    }

    private function freshLobby(LiveLobby $lobby): LiveLobby
    {
        return LiveLobby::query()->with($this->relations())->findOrFail($lobby->id);
    }

    private function relations(): array
    {
        return ['creator.profile', 'activeMembers.user.profile'];
    }

    private function poolFor(string $platform): string
    {
        return $platform === 'pc' ? 'pc' : 'console';
    }

    private function message(string $key): string
    {
        $english = app()->getLocale() === 'en';
        $messages = [
            'created' => ['Ready Lobby erstellt.', 'Ready Lobby created.'],
            'joined' => ['Ready Lobby beigetreten.', 'Joined Ready Lobby.'],
            'left' => ['Ready Lobby verlassen.', 'Left Ready Lobby.'],
            'closed' => ['Ready Lobby geschlossen.', 'Ready Lobby closed.'],
            'already_active' => ['Du bist bereits in einer aktiven Ready Lobby.', 'You already have an active Ready Lobby membership.'],
            'not_open' => ['Diese Ready Lobby ist nicht offen.', 'This Ready Lobby is not open.'],
            'incompatible' => ['Deine Plattform ist mit dieser Ready Lobby nicht kompatibel.', 'Your platform is not compatible with this Ready Lobby.'],
            'not_member' => ['Du bist kein aktives Mitglied dieser Ready Lobby.', 'You are not an active member of this Ready Lobby.'],
            'creator_only' => ['Nur der Ersteller darf diese Ready Lobby schließen.', 'Only the creator may close this Ready Lobby.'],
            'links_blocked' => ['Externe Links und Einladungslinks sind nicht erlaubt.', 'External links and invite links are not allowed.'],
        ];

        return $messages[$key][$english ? 1 : 0];
    }
}
