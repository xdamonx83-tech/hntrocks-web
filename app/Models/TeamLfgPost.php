<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamLfgPost extends Model
{
    use HasFactory;
    use HidesBlockedUsers;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'user_id',
        'type',
        'title',
        'body',
        'platform',
        'playstyle',
        'region',
        'language',
        'preferred_time',
        'experience_level',
        'voice_required',
        'slots_total',
        'slots_filled',
        'status',
        'visibility',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'voice_required' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TeamLfgApplication::class);
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function pendingApplications(): HasMany
    {
        return $this->applications()->where('status', 'pending');
    }

    public function acceptedApplications(): HasMany
    {
        return $this->applications()->where('status', 'accepted');
    }

    public function isTeamSeekingPlayers(): bool
    {
        return $this->type === 'team_seeks_players';
    }

    public function isPlayerSeekingTeam(): bool
    {
        return $this->type === 'player_seeks_team';
    }

    public function isOwner(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    public function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isOwner($user)) {
            return true;
        }

        if ($this->team) {
            return $this->team->canManage($user);
        }

        return false;
    }

    public function isOpen(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        if ($this->isTeamSeekingPlayers()) {
            return (int) $this->slots_filled < (int) $this->slots_total;
        }

        return true;
    }

    public function slotsOpen(): ?int
    {
        if (! $this->isTeamSeekingPlayers()) {
            return null;
        }

        return max(0, (int) $this->slots_total - (int) $this->slots_filled);
    }

    public function applicationFor(?User $user): ?TeamLfgApplication
    {
        if (! $user) {
            return null;
        }

        return $this->applications->firstWhere('user_id', $user->id);
    }

    public function hasApplicationFromTeam(int $teamId): bool
    {
        return $this->applications->contains(fn (TeamLfgApplication $application): bool => (int) $application->team_id === $teamId);
    }

    public function canApplyAsUser(User $user): bool
    {
        if (! $this->isTeamSeekingPlayers()) {
            return false;
        }

        if (! $this->isOpen() || $this->canManage($user)) {
            return false;
        }

        if ($this->applicationFor($user)) {
            return false;
        }

        if ($this->team && $this->team->isActiveMember($user)) {
            return false;
        }

        return true;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'player_seeks_team' => __('ui.team_lfg_type_player_seeks_team'),
            default => __('ui.team_lfg_type_team_seeks_players'),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => __('ui.team_lfg_status_open'),
            'filled' => __('ui.team_lfg_status_filled'),
            'closed' => __('ui.team_lfg_status_closed'),
            'archived' => __('ui.team_lfg_status_archived'),
            default => __('ui.team_lfg_status_unknown'),
        };
    }

    public function visibilityLabel(): string
    {
        return match ($this->visibility) {
            'private' => __('ui.team_lfg_visibility_private'),
            'public' => __('ui.team_lfg_visibility_public'),
            default => __('ui.team_lfg_visibility_unknown'),
        };
    }

    public function voiceLabel(): string
    {
        return $this->voice_required ? __('ui.team_lfg_voice_wanted') : __('ui.team_lfg_voice_optional');
    }

    public function displayTags(): array
    {
        return array_values(array_filter([
            $this->localizedOptionLabel('platform', $this->platform),
            $this->localizedOptionLabel('playstyle', $this->playstyle),
            $this->localizedOptionLabel('region', $this->region),
            $this->localizedOptionLabel('language', $this->language),
            $this->localizedOptionLabel('preferred_time', $this->preferred_time),
            $this->localizedOptionLabel('experience_level', $this->experience_level),
        ], static fn (?string $value): bool => filled($value)));
    }

    public function localizedOptionLabel(string $field, ?string $value): ?string
    {
        return self::localizedOptionLabelFor($field, $value);
    }

    public static function localizedOptionLabelFor(string $field, ?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $map = [
            'playstyle' => [
                'Entspannt' => 'ui.lfg_option_playstyle_relaxed',
                'Taktisch' => 'ui.lfg_option_playstyle_tactical',
                'Aggressiv' => 'ui.lfg_option_playstyle_aggressive',
                'Competitive' => 'ui.lfg_option_playstyle_competitive',
                'Einsteigerfreundlich' => 'ui.lfg_option_playstyle_beginner_friendly',
            ],
            'language' => [
                'Deutsch' => 'ui.lfg_option_language_german',
                'Englisch' => 'ui.lfg_option_language_english',
                'Deutsch / Englisch' => 'ui.lfg_option_language_german_english',
                'Mehrsprachig' => 'ui.lfg_option_language_multilingual',
            ],
            'preferred_time' => [
                'Morgens' => 'ui.lfg_option_time_morning',
                'Mittags' => 'ui.lfg_option_time_noon',
                'Abends' => 'ui.lfg_option_time_evening',
                'Nachts' => 'ui.lfg_option_time_night',
                'Wochenende' => 'ui.lfg_option_time_weekend',
                'Flexibel' => 'ui.lfg_option_time_flexible',
            ],
            'experience_level' => [
                'Einsteiger' => 'ui.lfg_option_experience_beginner',
                'Fortgeschritten' => 'ui.lfg_option_experience_advanced',
                'Erfahren' => 'ui.lfg_option_experience_experienced',
                'Competitive' => 'ui.lfg_option_playstyle_competitive',
                'Egal' => 'ui.lfg_option_experience_any',
            ],
        ];

        $key = $map[$field][$value] ?? null;

        return $key ? __($key) : $value;
    }
}
