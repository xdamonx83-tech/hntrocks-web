<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CupTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $members = $this->relationLoaded('members')
            ? $this->members->where('status', 'active')->values()
            : collect();

        return [
            'id' => (int) $this->id,
            'name' => $this->displayName(),
            'raw_name' => (string) $this->name,
            'status' => (string) $this->status,
            'status_label' => $this->statusLabel(),
            'points_total' => (int) $this->points_total,
            'kills_total' => (int) $this->kills_total,
            'bounty_tokens_total' => (int) $this->bounty_tokens_total,
            'submissions_approved_count' => (int) $this->submissions_approved_count,
            'last_submission_at' => $this->last_submission_at?->toISOString(),
            'roster_locked' => $this->isRosterLocked(),
            'roster_locked_at' => $this->roster_locked_at?->toISOString(),
            'is_recruiting' => $this->isRecruiting(),
            'member_count' => $members->count(),
            'required_members_count' => $this->requiredMembersCount(),
            'slots_open' => $this->slotsOpen(),
            'complete' => $this->isComplete(),
            'can_change_roster' => $this->canChangeRoster(),
            'viewer_is_member' => $this->hasMember($request->user()),
            'viewer_is_captain' => $this->isCaptain($request->user()),
            'viewer_can_submit' => $this->canSubmitForCup($request->user()),
            'owner' => $this->relationLoaded('owner') && $this->owner
                ? new UserResource($this->owner)
                : null,
            'members' => CupTeamMemberResource::collection($members),
        ];
    }
}
