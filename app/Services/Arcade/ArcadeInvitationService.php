<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeGameStatus;
use App\Enums\Arcade\ArcadeInvitationStatus;
use App\Enums\Arcade\ArcadeMatchMode;
use App\Enums\Arcade\ArcadeMatchPlayerStatus;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeInvitation;
use App\Models\Arcade\ArcadeMatch;
use App\Models\Friendship;
use App\Models\User;
use App\Services\UserBlockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ArcadeInvitationService
{
    public function __construct(private readonly UserBlockService $blocks) {}

    public function create(User $inviter, User $invitee, ArcadeGame $game, ArcadeMatchMode $mode): ArcadeInvitation
    {
        if ($inviter->is($invitee)) throw ValidationException::withMessages(['invitee_id' => 'You cannot invite yourself.']);
        if ($invitee->status === 'suspended' || $invitee->suspended_at) throw ValidationException::withMessages(['invitee_id' => 'This user is unavailable.']);
        if (! Friendship::query()->between($inviter, $invitee)->where('status', Friendship::STATUS_ACCEPTED)->exists()) throw new AccessDeniedHttpException('Only accepted friends can be invited.');
        if ($this->blocks->areBlocked($inviter, $invitee)) throw new AccessDeniedHttpException('Invitations are unavailable between blocked users.');
        if (! in_array($game->status, [ArcadeGameStatus::Active, ArcadeGameStatus::Event], true)) throw ValidationException::withMessages(['game' => 'This game is unavailable.']);
        if (($mode === ArcadeMatchMode::Casual && ! $game->casual_enabled) || ($mode === ArcadeMatchMode::Ranked && ! $game->ranked_enabled)) throw ValidationException::withMessages(['mode' => 'This mode is unavailable.']);

        return DB::transaction(function () use ($inviter, $invitee, $game, $mode): ArcadeInvitation {
            $duplicate = ArcadeInvitation::query()->where('game_id', $game->id)->where('mode', $mode->value)
                ->where('status', ArcadeInvitationStatus::Pending->value)->where('expires_at', '>', now())
                ->where(fn ($q) => $q->where(fn ($p) => $p->where('inviter_id', $inviter->id)->where('invitee_id', $invitee->id))->orWhere(fn ($p) => $p->where('inviter_id', $invitee->id)->where('invitee_id', $inviter->id)))
                ->lockForUpdate()->first();
            if ($duplicate) throw new ConflictHttpException('A pending invitation already exists.');
            return ArcadeInvitation::create(['game_id' => $game->id, 'inviter_id' => $inviter->id, 'invitee_id' => $invitee->id, 'mode' => $mode, 'status' => ArcadeInvitationStatus::Pending, 'expires_at' => now()->addMinutes(max(1, (int) config('arcade.invitation_ttl_minutes', 30)))]);
        });
    }

    public function accept(ArcadeInvitation $invitation, User $actor): ArcadeInvitation
    {
        if ((int) $invitation->invitee_id !== (int) $actor->id) throw new AccessDeniedHttpException;
        $result = DB::transaction(function () use ($invitation): ArcadeInvitation {
            $invitation = ArcadeInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            if ($invitation->status === ArcadeInvitationStatus::Accepted && $invitation->match_id) return $invitation;
            if ($this->expireIfNecessary($invitation)) return $invitation;
            $this->requirePending($invitation);
            $match = ArcadeMatch::create(['game_id' => $invitation->game_id, 'mode' => $invitation->mode, 'status' => ArcadeMatchStatus::WaitingReady, 'version' => 0, 'created_by' => $invitation->inviter_id]);
            $match->players()->createMany([
                ['user_id' => $invitation->inviter_id, 'seat' => 1, 'status' => ArcadeMatchPlayerStatus::Joined, 'joined_at' => now()],
                ['user_id' => $invitation->invitee_id, 'seat' => 2, 'status' => ArcadeMatchPlayerStatus::Joined, 'joined_at' => now()],
            ]);
            $invitation->update(['status' => ArcadeInvitationStatus::Accepted, 'accepted_at' => now(), 'match_id' => $match->id]);
            return $invitation->fresh(['match']);
        });
        if ($result->status === ArcadeInvitationStatus::Expired) throw ValidationException::withMessages(['invitation' => 'This invitation has expired.']);
        return $result;
    }

    public function decline(ArcadeInvitation $invitation, User $actor): ArcadeInvitation { return $this->transition($invitation, $actor, false, ArcadeInvitationStatus::Declined, 'declined_at'); }
    public function cancel(ArcadeInvitation $invitation, User $actor): ArcadeInvitation { return $this->transition($invitation, $actor, true, ArcadeInvitationStatus::Cancelled, 'cancelled_at'); }

    private function transition(ArcadeInvitation $invitation, User $actor, bool $inviter, ArcadeInvitationStatus $status, string $timestamp): ArcadeInvitation
    {
        if ((int) ($inviter ? $invitation->inviter_id : $invitation->invitee_id) !== (int) $actor->id) throw new AccessDeniedHttpException;
        $result = DB::transaction(function () use ($invitation, $status, $timestamp): ArcadeInvitation { $locked = ArcadeInvitation::query()->lockForUpdate()->findOrFail($invitation->id); if ($this->expireIfNecessary($locked)) return $locked; $this->requirePending($locked); $locked->update(['status' => $status, $timestamp => now()]); return $locked; });
        if ($result->status === ArcadeInvitationStatus::Expired) throw ValidationException::withMessages(['invitation' => 'This invitation has expired.']);
        return $result;
    }

    private function requirePending(ArcadeInvitation $invitation): void
    {
        if ($invitation->status !== ArcadeInvitationStatus::Pending) throw new ConflictHttpException('This invitation is no longer pending.');
    }

    private function expireIfNecessary(ArcadeInvitation $invitation): bool
    {
        if ($invitation->status !== ArcadeInvitationStatus::Pending || ! $invitation->expires_at->isPast()) return false;
        $invitation->update(['status' => ArcadeInvitationStatus::Expired]);
        return true;
    }
}
