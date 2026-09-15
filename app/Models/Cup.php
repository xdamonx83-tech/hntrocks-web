<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Cup extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'title',
        'slug',
        'summary',
        'rules',
        'platform',
        'region',
        'language',
        'team_size',
        'max_teams',
        'status',
        'visibility',
        'starts_at',
        'ends_at',
        'registration_opens_at',
        'registration_closes_at',
        'cover_path',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'settings' => 'array',
            'team_size' => 'integer',
            'max_teams' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(CupTeam::class)
            ->orderByDesc('points_total')
            ->orderByDesc('bounty_tokens_total')
            ->orderByDesc('kills_total');
    }

    public function activeTeams(): HasMany
    {
        return $this->teams()->where('status', 'active');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CupSubmission::class)->latest();
    }

    public function randomizerDraws(): HasMany
    {
        return $this->hasMany(CupRandomizerDraw::class)->latest();
    }

    public function randomizerEligibleTeams()
    {
        return CupTeam::query()
            ->where('cup_id', $this->id)
            ->where('status', 'active')
            ->whereHas('submissions', function ($query): void {
                $query->whereIn('status', CupSubmission::scoredStatuses());
            })
            ->with(['owner:id,name,username,avatar_path', 'members.user:id,name,username,avatar_path'])
            ->orderBy('name')
            ->get();
    }


    public function teamFinderPosts(): HasMany
    {
        return $this->hasMany(CupTeamFinderPost::class)->latest();
    }


    public function chatMessages(): HasMany
    {
        return $this->hasMany(CupChatMessage::class)->latest();
    }

    public function feedbackEntries(): HasMany
    {
        return $this->hasMany(CupFeedbackEntry::class)->latest('submitted_at');
    }

    public function pendingSubmissions(): HasMany
    {
        return $this->submissions()->whereIn('status', ['pending', 'review_required']);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    public function isOwner(?User $user): bool
    {
        return $user !== null && (int) $this->owner_id === (int) $user->id;
    }

    public function canManage(?User $user): bool
    {
        return $user?->isAdmin() === true;
    }

    public function isSoloLeaderboard(): bool
    {
        $settings = is_array($this->settings) ? $this->settings : [];

        if (($settings['mode'] ?? null) === 'solo_leaderboard') {
            return true;
        }

        if ((int) $this->team_size <= 1) {
            return true;
        }

        return $this->isBayouBloodCup();
    }

    public function isBayouBloodCup(): bool
    {
        $title = trim((string) $this->title);
        $slug = trim((string) $this->slug);

        return strcasecmp($title, 'Bayou Blood Cup') === 0 || strcasecmp($slug, 'bayou-blood-cup') === 0;
    }

    public function participantLimit(): ?int
    {
        return $this->max_teams ? (int) $this->max_teams : null;
    }

    public function modeLabel(): string
    {
        return $this->isSoloLeaderboard() ? __('ui.cup_mode_solo_leaderboard') : __('ui.cup_mode_team_leaderboard');
    }


    public function contentSetting(string $key, mixed $default = null): mixed
    {
        $settings = is_array($this->settings) ? $this->settings : [];

        return data_get($settings, 'content.'.$key, $default);
    }

    public function localizedContentSetting(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        $locale = $this->normalizeContentLocale($locale ?: app()->getLocale());
        $settings = is_array($this->settings) ? $this->settings : [];

        $value = data_get($settings, 'content.locales.'.$locale.'.'.$key);
        if ($this->filledContentValue($value)) {
            return $value;
        }

        if ($locale !== 'de') {
            $fallback = data_get($settings, 'content.locales.de.'.$key);
            if ($this->filledContentValue($fallback)) {
                return $fallback;
            }
        }

        $legacy = data_get($settings, 'content.'.$key);
        if ($this->filledContentValue($legacy)) {
            return $legacy;
        }

        return $default;
    }

    public function displaySummary(): string
    {
        $summary = trim((string) $this->localizedContentSetting('summary', $this->summary));

        if ($summary !== '') {
            return $summary;
        }

        return $this->isBayouBloodCup() ? __('ui.cup_bayou_summary') : __('ui.cup_no_summary');
    }

    public function displayDescription(): string
    {
        $description = trim((string) $this->localizedContentSetting('description', ''));

        if ($description !== '') {
            return $description;
        }

        return $this->isBayouBloodCup() ? __('ui.cup_bayou_intro_text') : '';
    }

    public function displayRules(): string
    {
        $rules = trim((string) $this->localizedContentSetting('rules', $this->rules));

        if ($rules !== '') {
            return $rules;
        }

        return $this->isBayouBloodCup() ? __('ui.cup_bayou_rules_text') : '';
    }

    public function displayScoringRules(): string
    {
        $scoringRules = trim((string) $this->localizedContentSetting('scoring_rules', ''));

        if ($scoringRules !== '') {
            return $scoringRules;
        }

        return __('ui.cup_info_scoring_text');
    }

    public function prizeRows(): array
    {
        $defaults = $this->isBayouBloodCup() ? [
            'first' => __('ui.cup_bayou_first_place_prize'),
            'second' => __('ui.cup_bayou_second_place_prize'),
            'third' => __('ui.cup_bayou_third_place_prize'),
        ] : [];

        $rows = [
            'first' => __('ui.cup_first_place'),
            'second' => __('ui.cup_second_place'),
            'third' => __('ui.cup_third_place'),
        ];

        return collect($rows)
            ->map(function (string $label, string $place) use ($defaults): array {
                return [
                    'place' => $place,
                    'label' => $label,
                    'text' => trim((string) $this->localizedContentSetting('prizes.'.$place, $defaults[$place] ?? '')),
                ];
            })
            ->filter(fn (array $row): bool => $row['text'] !== '')
            ->values()
            ->all();
    }

    public function prizeNotes(): array
    {
        $defaults = $this->isBayouBloodCup() ? [
            'prize_note' => __('ui.cup_bayou_prize_note'),
            'cashout_note' => __('ui.cup_default_cashout_note'),
            'hall_of_fame_note' => __('ui.cup_default_hall_of_fame_note'),
        ] : [];

        $notes = [
            'prize_note' => __('ui.cup_prize_note_title'),
            'cashout_note' => __('ui.cup_cashout_note_title'),
            'hall_of_fame_note' => __('ui.cup_hall_of_fame_note_title'),
        ];

        return collect($notes)
            ->map(function (string $label, string $key) use ($defaults): array {
                return [
                    'key' => $key,
                    'label' => $label,
                    'text' => trim((string) $this->localizedContentSetting($key, $defaults[$key] ?? '')),
                ];
            })
            ->filter(fn (array $note): bool => $note['text'] !== '')
            ->values()
            ->all();
    }

    private function normalizeContentLocale(string $locale): string
    {
        return in_array($locale, ['de', 'en'], true) ? $locale : 'de';
    }

    private function filledContentValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return trim((string) $value) !== '';
    }


    public static function rulesPresetOptions(): array
    {
        return [
            'classic_bounty' => __('ui.cup_rules_preset_classic_bounty'),
            'console_mini_fair' => __('ui.cup_rules_preset_console_mini_fair'),
            'summer_trio_first_trophy' => __('ui.cup_rules_preset_summer_trio_first_trophy'),
            'manual_bounty_banish' => __('ui.cup_rules_preset_manual_bounty_banish'),
        ];
    }

    public static function aiPromptPresetOptions(): array
    {
        return [
            'classic_summary' => __('ui.cup_ai_prompt_classic_summary'),
            'console_platform' => __('ui.cup_ai_prompt_console_platform'),
            'awards_first_trophy' => __('ui.cup_ai_prompt_awards_first_trophy'),
        ];
    }

    public function rulesPreset(): string
    {
        $preset = (string) data_get(is_array($this->settings) ? $this->settings : [], 'rules_preset', 'classic_bounty');

        return array_key_exists($preset, self::rulesPresetOptions()) ? $preset : 'classic_bounty';
    }

    public function rulesPresetLabel(): string
    {
        return self::rulesPresetOptions()[$this->rulesPreset()] ?? __('ui.cup_rules_preset_classic_bounty');
    }

    public function defaultAiPromptPreset(): string
    {
        return match ($this->rulesPreset()) {
            'console_mini_fair' => 'console_platform',
            'summer_trio_first_trophy' => 'awards_first_trophy',
            default => 'classic_summary',
        };
    }

    public function aiPromptPreset(): string
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        $preset = (string) data_get($settings, 'ai_prompt_preset', $this->defaultAiPromptPreset());

        return array_key_exists($preset, self::aiPromptPresetOptions()) ? $preset : $this->defaultAiPromptPreset();
    }

    public function usesSummerFirstTrophyScoring(): bool
    {
        return $this->rulesPreset() === 'summer_trio_first_trophy' || $this->aiPromptPreset() === 'awards_first_trophy';
    }

    public function usesManualReviewScoring(): bool
    {
        return $this->rulesPreset() === 'manual_bounty_banish';
    }

    public function maxSubmissionsPerParticipant(): ?int
    {
        $value = data_get(is_array($this->settings) ? $this->settings : [], 'submission_limit.max_uploads_per_participant');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function maxScoredSubmissionsPerParticipant(): ?int
    {
        $value = data_get(is_array($this->settings) ? $this->settings : [], 'submission_limit.max_scored_runs_per_participant');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function requiresCompleteProfile(): bool
    {
        return (bool) data_get(is_array($this->settings) ? $this->settings : [], 'participation_requirements.profile_complete', false);
    }

    public function requiredCommunityActions(): int
    {
        $value = data_get(is_array($this->settings) ? $this->settings : [], 'participation_requirements.min_community_actions', 0);

        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    public function allowedPlatforms(): array
    {
        $platforms = data_get(is_array($this->settings) ? $this->settings : [], 'platform_gate.allowed_platforms', []);

        if (! is_array($platforms) || $platforms === []) {
            $platform = trim((string) $this->platform);
            if (in_array($platform, ['PC', 'PlayStation', 'Xbox'], true)) {
                $platforms = [$platform];
            } elseif (strcasecmp($platform, 'Konsole') === 0 || strcasecmp($platform, 'Console') === 0) {
                $platforms = ['PlayStation', 'Xbox'];
            }
        }

        return collect($platforms)
            ->map(fn ($platform): string => $this->normalizePlatform((string) $platform) ?: '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isPlatformAllowedFor(?User $user): bool
    {
        $allowed = $this->allowedPlatforms();
        if ($allowed === []) {
            return true;
        }

        $platform = $this->normalizePlatform((string) $user?->profile?->platform);

        return $platform !== null && in_array($platform, $allowed, true);
    }

    public function participationEligibility(?User $user): array
    {
        if (! $user) {
            return ['eligible' => false, 'messages' => [__('ui.cup_requirement_login')]];
        }

        $user->loadMissing('profile');

        $messages = [];
        $allowedPlatforms = $this->allowedPlatforms();
        if ($allowedPlatforms !== [] && ! $this->isPlatformAllowedFor($user)) {
            $messages[] = __('ui.cup_requirement_platform', ['platforms' => implode(' / ', $allowedPlatforms)]);
        }

        if ($this->requiresCompleteProfile() && \App\Support\ProfileCompletion::score($user) < 100) {
            $messages[] = __('ui.cup_requirement_profile_complete');
        }

        $requiredCommunityActions = $this->requiredCommunityActions();
        if ($requiredCommunityActions > 0 && $this->communityActionsCount($user) < $requiredCommunityActions) {
            $messages[] = trans_choice('ui.cup_requirement_community_action', $requiredCommunityActions, ['count' => $requiredCommunityActions]);
        }

        return ['eligible' => $messages === [], 'messages' => $messages];
    }

    public function communityActionsCount(User $user): int
    {
        $feedPosts = FeedPost::query()
            ->where('user_id', $user->id)
            ->where(function ($query): void {
                $query->whereNull('status')->orWhereNotIn('status', ['deleted', 'hidden', 'rejected']);
            })
            ->count();

        $moments = Moment::query()
            ->where('user_id', $user->id)
            ->published()
            ->count();

        return (int) $feedPosts + (int) $moments;
    }

    public function normalizePlatform(string $platform): ?string
    {
        $platform = trim($platform);
        $normalized = strtolower(str_replace([' ', '-', '_'], '', $platform));

        return match ($normalized) {
            'pc', 'steam', 'windows' => 'PC',
            'ps', 'ps5', 'ps4', 'playstation', 'playstation5', 'sony' => 'PlayStation',
            'xbox', 'xboxseries', 'xboxseriesxs', 'xboxseriesx', 'xboxseriess', 'microsoft' => 'Xbox',
            default => null,
        };
    }

    public function rulesSummary(): array
    {
        $rows = [];
        $allowedPlatforms = $this->allowedPlatforms();
        if ($allowedPlatforms !== []) {
            $rows[] = __('ui.cup_rules_summary_platforms', ['platforms' => implode(' / ', $allowedPlatforms)]);
        }
        if ($this->maxSubmissionsPerParticipant()) {
            $rows[] = __('ui.cup_rules_summary_max_uploads', ['count' => $this->maxSubmissionsPerParticipant()]);
        }
        if ($this->maxScoredSubmissionsPerParticipant()) {
            $rows[] = __('ui.cup_rules_summary_best_runs', ['count' => $this->maxScoredSubmissionsPerParticipant()]);
        }
        if ($this->requiresCompleteProfile()) {
            $rows[] = __('ui.cup_rules_summary_profile_complete');
        }
        if ($this->requiredCommunityActions() > 0) {
            $rows[] = trans_choice('ui.cup_rules_summary_community_action', $this->requiredCommunityActions(), ['count' => $this->requiredCommunityActions()]);
        }

        return $rows;
    }

    public function isRegistrationOpen(): bool
    {
        if (! in_array($this->status, ['planned', 'active'], true)) {
            return false;
        }

        if ($this->registration_opens_at && $this->registration_opens_at->isFuture()) {
            return false;
        }

        if ($this->registration_closes_at && $this->registration_closes_at->isPast()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_teams && $this->activeTeams()->count() >= (int) $this->max_teams) {
            return false;
        }

        return true;
    }

    public function isSubmissionOpen(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function submissionClosedReason(): string
    {
        if ($this->status !== 'active') {
            return __('ui.cup_submit_not_open_status_text');
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return __('ui.cup_submit_not_open_before_start_text', [
                'date' => $this->starts_at->translatedFormat('d.m.Y · H:i'),
            ]);
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return __('ui.cup_submit_not_open_after_end_text');
        }

        return __('ui.cup_submit_not_open_text');
    }

    public function teamFor(?User $user): ?CupTeam
    {
        if (! $user) {
            return null;
        }

        return $this->teams
            ->first(fn (CupTeam $team): bool => $team->hasMember($user));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'planned' => __('ui.cup_status_planned'),
            'active' => __('ui.cup_status_active'),
            'finished' => __('ui.cup_status_finished'),
            'archived' => __('ui.cup_status_archived'),
            default => __('ui.cup_status_unknown'),
        };
    }

    public function visibilityLabel(): string
    {
        return match ($this->visibility) {
            'private' => __('ui.cup_visibility_private'),
            default => __('ui.cup_visibility_public'),
        };
    }

    public function coverUrl(): string
    {
        $coverPath = $this->normalizedPublicCoverPath($this->cover_path);

        if ($coverPath !== null && Storage::disk('public')->exists($coverPath)) {
            return Storage::disk('public')->url($coverPath);
        }

        $attachedCover = MediaAsset::query()
            ->where('attachable_type', $this->getMorphClass())
            ->where('attachable_id', $this->id)
            ->whereIn('context', ['cups/covers', 'cups'])
            ->where(function ($query): void {
                $query->where('type', 'image')
                    ->orWhere('mime_type', 'like', 'image/%');
            })
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->latest('id')
            ->first();

        if ($attachedCover) {
            return $attachedCover->url();
        }

        if (is_string($this->cover_path) && preg_match('#^https?://#i', trim($this->cover_path))) {
            return trim($this->cover_path);
        }

        return asset('assets/vikinger/img/default-cover.svg');
    }

    private function normalizedPublicCoverPath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '' || $path === '/' || $path === 'storage' || $path === 'storage/') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($urlPath) ? $urlPath : '';
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        $path = ltrim($path, '/');

        return $path !== '' ? $path : null;
    }
}
