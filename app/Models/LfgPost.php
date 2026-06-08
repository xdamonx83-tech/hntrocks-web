<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LfgPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
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
        'cover_path',
    ];

    protected function casts(): array
    {
        return [
            'voice_required' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }


    public function coverUrl(): string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }

        return $this->user?->coverUrl() ?? asset('assets/vikinger/img/cover/01.jpg');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LfgApplication::class);
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

    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function canManage(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function canDelete(User $user): bool
    {
        return $this->isOwner($user) || $user->isAdmin();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->slots_filled < $this->slots_total;
    }

    public function slotsOpen(): int
    {
        return max(0, (int) $this->slots_total - (int) $this->slots_filled);
    }

    public function applicationFor(?User $user): ?LfgApplication
    {
        if (! $user) {
            return null;
        }

        return $this->applications->firstWhere('user_id', $user->id);
    }

    public function canApply(User $user): bool
    {
        return ! $this->isOwner($user)
            && $this->isOpen()
            && ! $this->applicationFor($user);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => __('ui.lfg_status_open'),
            'full' => __('ui.lfg_status_full'),
            'closed' => __('ui.lfg_status_closed'),
            'archived' => __('ui.lfg_status_archived'),
            default => __('ui.lfg_status_unknown'),
        };
    }

    public function visibilityLabel(): string
    {
        return match ($this->visibility) {
            'public' => __('ui.lfg_visibility_public'),
            'private' => __('ui.lfg_visibility_private'),
            default => __('ui.lfg_status_unknown'),
        };
    }

    public function voiceLabel(): string
    {
        return $this->voice_required ? __('ui.lfg_voice_wanted') : __('ui.lfg_voice_optional');
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
