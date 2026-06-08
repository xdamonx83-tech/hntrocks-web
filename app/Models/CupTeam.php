<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CupTeam extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'cup_id',
        'owner_id',
        'name',
        'join_token',
        'status',
        'points_total',
        'kills_total',
        'bounty_tokens_total',
        'submissions_approved_count',
        'last_submission_at',
        'roster_locked_at',
        'is_recruiting',
        'detected_gamertag',
        'detected_gamertag_normalized',
        'detected_gamertag_confidence',
        'detected_gamertag_locked_at',
        'disqualified_at',
        'disqualified_by',
        'disqualification_reason',
    ];

    protected function casts(): array
    {
        return [
            'points_total' => 'integer',
            'kills_total' => 'integer',
            'bounty_tokens_total' => 'integer',
            'submissions_approved_count' => 'integer',
            'last_submission_at' => 'datetime',
            'roster_locked_at' => 'datetime',
            'is_recruiting' => 'boolean',
            'detected_gamertag_confidence' => 'float',
            'detected_gamertag_locked_at' => 'datetime',
            'disqualified_at' => 'datetime',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (CupTeam $team): void {
            if (! $team->join_token) {
                $team->join_token = Str::random(32);
            }
        });
    }

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function disqualifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disqualified_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CupTeamMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CupSubmission::class)->latest();
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(CupTeamChatMessage::class)->latest();
    }

    public function hasMember(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('members')) {
            return $this->members->contains(fn (CupTeamMember $member): bool => (int) $member->user_id === (int) $user->id && $member->status === 'active');
        }

        return $this->members()->where('user_id', $user->id)->where('status', 'active')->exists();
    }

    public function isOwner(?User $user): bool
    {
        return $user !== null && (int) $this->owner_id === (int) $user->id;
    }

    public function isCaptain(?User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('members')) {
            return $this->members->contains(fn (CupTeamMember $member): bool => (int) $member->user_id === (int) $user->id && $member->status === 'active' && $member->role === 'captain');
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('role', 'captain')
            ->exists();
    }

    public function activeMembersCount(): int
    {
        if ($this->relationLoaded('members')) {
            return $this->members->where('status', 'active')->count();
        }

        return $this->activeMembers()->count();
    }

    public function requiredMembersCount(): int
    {
        if ($this->cup?->isSoloLeaderboard()) {
            return 1;
        }

        return max(1, (int) ($this->cup?->team_size ?: 1));
    }

    public function isComplete(): bool
    {
        return $this->activeMembersCount() >= $this->requiredMembersCount();
    }

    public function isRosterLocked(): bool
    {
        return $this->roster_locked_at !== null;
    }

    public function canChangeRoster(): bool
    {
        return ! $this->isDisqualified() && ! $this->isRosterLocked();
    }

    public function lockRoster(): void
    {
        if ($this->isRosterLocked() || $this->cup?->isSoloLeaderboard()) {
            return;
        }

        $this->forceFill(['roster_locked_at' => now()])->save();
    }


    public function isRecruiting(): bool
    {
        return (bool) $this->is_recruiting
            && $this->status === 'active'
            && $this->canChangeRoster()
            && $this->slotsOpen() > 0;
    }

    public function canSubmitForCup(?User $user): bool
    {
        if ($this->cup?->isSoloLeaderboard()) {
            return $this->hasMember($user);
        }

        return $this->isCaptain($user) && $this->isComplete();
    }

    public function canManage(?User $user): bool
    {
        return $this->isOwner($user) || $this->cup?->canManage($user) === true;
    }

    public function slotsOpen(): int
    {
        if ($this->cup?->isSoloLeaderboard() || $this->isRosterLocked()) {
            return 0;
        }

        $teamSize = max(1, (int) ($this->cup?->team_size ?: 1));

        return max(0, $teamSize - $this->activeMembers()->count());
    }

    public function displayName(): string
    {
        if ($this->cup?->isSoloLeaderboard()) {
            return $this->owner?->username ?: $this->owner?->name ?: $this->name;
        }

        return $this->name;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => __('ui.cup_team_status_active'),
            'locked' => __('ui.cup_team_status_locked'),
            'withdrawn' => __('ui.cup_team_status_withdrawn'),
            'disqualified' => __('ui.cup_team_status_disqualified'),
            default => __('ui.cup_status_unknown'),
        };
    }

    public function isDisqualified(): bool
    {
        return $this->status === 'disqualified';
    }
}

