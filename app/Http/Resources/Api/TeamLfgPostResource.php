<?php

namespace App\Http\Resources\Api;

use App\Models\Team;
use App\Models\TeamLfgApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamLfgPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'platform' => $this->platform,
            'playstyle' => $this->playstyle,
            'region' => $this->region,
            'language' => $this->language,
            'preferred_time' => $this->preferred_time,
            'experience_level' => $this->experience_level,
            'voice_required' => (bool) $this->voice_required,
            'slots_total' => (int) $this->slots_total,
            'slots_filled' => (int) $this->slots_filled,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'author' => new UserResource($this->whenLoaded('user')),
            'team' => new TeamResource($this->whenLoaded('team')),
            'viewer' => $this->viewerPayload($request),
            'pending_applications' => $this->pendingApplicationsPayload($request),
            'created_at' => $this->created_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
        ];
    }

    private function viewerPayload(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [
                'is_owner' => false,
                'can_apply' => false,
                'can_invite' => false,
                'application' => null,
            ];
        }

        $application = $this->viewerApplication($user->id);

        return [
            'is_owner' => $this->isOwner($user),
            'can_apply' => $this->isTeamSeekingPlayers() && $this->canApplyAsUser($user),
            'can_invite' => $this->canInviteFromManagedTeam($request),
            'application' => $application ? $this->applicationPayload($application, includeActor: false) : null,
        ];
    }

    private function pendingApplicationsPayload(Request $request): array
    {
        $user = $request->user();
        if (! $user || ! $this->relationLoaded('pendingApplications')) {
            return [];
        }

        $canManagePending = $this->isTeamSeekingPlayers()
            ? $this->canManage($user)
            : $this->isOwner($user);

        if (! $canManagePending) {
            return [];
        }

        return $this->pendingApplications
            ->map(fn (TeamLfgApplication $application): array => $this->applicationPayload($application, includeActor: true))
            ->values()
            ->all();
    }

    private function applicationPayload(TeamLfgApplication $application, bool $includeActor = true): array
    {
        return [
            'id' => $application->id,
            'team_id' => $application->team_id,
            'status' => $application->status,
            'message' => $application->message,
            'created_at' => $application->created_at?->toISOString(),
            'user' => $includeActor && $application->relationLoaded('user') ? new UserResource($application->user) : null,
            'team' => $includeActor && $application->relationLoaded('team') && $application->team ? new TeamResource($application->team) : null,
        ];
    }

    private function viewerApplication(int $userId)
    {
        if (! $this->relationLoaded('applications')) {
            return null;
        }

        return $this->applications->firstWhere('user_id', $userId)
            ?: $this->applications->firstWhere('status', 'pending');
    }

    private function canInviteFromManagedTeam(Request $request): bool
    {
        $user = $request->user();
        if (! $user || ! $this->isPlayerSeekingTeam() || ! $this->isOpen() || $this->isOwner($user)) {
            return false;
        }

        $teamIds = Team::query()
            ->whereHas('members', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->whereIn('role', ['owner', 'officer']);
            })
            ->whereDoesntHave('members', function ($query): void {
                $query->where('user_id', $this->user_id)
                    ->where('status', 'active');
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($teamIds === []) {
            return false;
        }

        return ! TeamLfgApplication::query()
            ->where('team_lfg_post_id', $this->id)
            ->whereIn('team_id', $teamIds)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();
    }
}
