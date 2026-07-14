<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Support\CupOrganizerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityCupStoreController extends Controller
{
    public function __invoke(
        Request $request,
        MediaService $mediaService,
        GamificationService $gamification
    ): RedirectResponse {
        abort_unless($request->user(), 401);

        $validated = $this->validatedCupData($request);
        $contentSettings = $this->extractCupContentSettings($validated);
        $rulesSettings = $this->extractCupRulesSettings($request, $validated);

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $request->user(), 'cups/covers');
        }

        unset($validated['cover']);
        $validated['owner_id'] = $request->user()->id;
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['settings'] = array_merge($rulesSettings, [
            'scoring' => 'bounty_first_extract_required',
            'submission_cooldown_minutes' => (int) config('hunthub.cups.submission_cooldown_minutes', 30),
            'mode' => (int) ($validated['team_size'] ?? 1) <= 1 ? 'solo_leaderboard' : 'team_leaderboard',
            'event_key' => null,
            'content' => $contentSettings,
        ]);

        $cup = Cup::create($validated);

        if ($request->hasFile('cover')) {
            $asset = $mediaService->store($request->file('cover'), $request->user(), 'cups/covers', [
                'visibility' => $cup->visibility,
                'attachable' => $cup,
            ]);
            $cup->update(['cover_path' => $asset->path]);
        }

        $gamification->award(
            $request->user(),
            'cup_created',
            source: $cup,
            description: __('ui.cup_gamification_created')
        );

        return redirect()
            ->route('cups.show', $cup)
            ->with('status', __('ui.cup_created_status'));
    }

    private function validatedCupData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'summary' => ['nullable', 'string', 'max:255'],
            'summary_de' => ['nullable', 'string', 'max:255'],
            'summary_en' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string', 'max:6000'],
            'rules_de' => ['nullable', 'string', 'max:6000'],
            'rules_en' => ['nullable', 'string', 'max:6000'],
            'platform' => ['nullable', 'string', 'max:40'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:60'],
            'team_size' => ['required', 'integer', 'min:1', 'max:3'],
            'max_teams' => ['nullable', 'integer', 'min:2', 'max:256'],
            'status' => ['required', 'in:planned,active,finished,archived'],
            'visibility' => ['required', 'in:public,private'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'cup_description' => ['nullable', 'string', 'max:12000'],
            'cup_description_de' => ['nullable', 'string', 'max:12000'],
            'cup_description_en' => ['nullable', 'string', 'max:12000'],
            'scoring_rules' => ['nullable', 'string', 'max:6000'],
            'scoring_rules_de' => ['nullable', 'string', 'max:6000'],
            'scoring_rules_en' => ['nullable', 'string', 'max:6000'],
            'prize_first' => ['nullable', 'string', 'max:1000'],
            'prize_first_de' => ['nullable', 'string', 'max:1000'],
            'prize_first_en' => ['nullable', 'string', 'max:1000'],
            'prize_second' => ['nullable', 'string', 'max:1000'],
            'prize_second_de' => ['nullable', 'string', 'max:1000'],
            'prize_second_en' => ['nullable', 'string', 'max:1000'],
            'prize_third' => ['nullable', 'string', 'max:1000'],
            'prize_third_de' => ['nullable', 'string', 'max:1000'],
            'prize_third_en' => ['nullable', 'string', 'max:1000'],
            'prize_note' => ['nullable', 'string', 'max:3000'],
            'prize_note_de' => ['nullable', 'string', 'max:3000'],
            'prize_note_en' => ['nullable', 'string', 'max:3000'],
            'cashout_note' => ['nullable', 'string', 'max:2000'],
            'cashout_note_de' => ['nullable', 'string', 'max:2000'],
            'cashout_note_en' => ['nullable', 'string', 'max:2000'],
            'hall_of_fame_note' => ['nullable', 'string', 'max:2000'],
            'hall_of_fame_note_de' => ['nullable', 'string', 'max:2000'],
            'hall_of_fame_note_en' => ['nullable', 'string', 'max:2000'],
            'rules_preset' => ['nullable', 'string', 'in:classic_bounty,console_mini_fair,summer_trio_first_trophy'],
            'ai_prompt_preset' => ['nullable', 'string', 'in:classic_summary,console_platform,awards_first_trophy'],
            'verification_mode' => ['nullable', 'string', 'in:manual,ai'],
            'allowed_platforms' => ['nullable', 'array'],
            'allowed_platforms.*' => ['string', 'in:PC,PlayStation,Xbox'],
            'max_submissions_per_participant' => ['nullable', 'integer', 'min:0', 'max:99'],
            'max_scored_submissions_per_participant' => ['nullable', 'integer', 'min:0', 'max:99'],
            'require_profile_complete' => ['nullable', 'boolean'],
            'min_community_actions' => ['nullable', 'integer', 'min:0', 'max:20'],
            'cover' => ['nullable', 'image', 'max:'.config('hunthub.upload_limits.cup_cover_kb', 6144)],
        ]);
    }

    private function extractCupRulesSettings(Request $request, array &$validated): array
    {
        $rulesPreset = (string) ($validated['rules_preset'] ?? 'classic_bounty');
        if (! array_key_exists($rulesPreset, Cup::rulesPresetOptions())) {
            $rulesPreset = 'classic_bounty';
        }

        $defaultAiPreset = match ($rulesPreset) {
            'console_mini_fair' => 'console_platform',
            'summer_trio_first_trophy' => 'awards_first_trophy',
            default => 'classic_summary',
        };

        $verificationMode = CupOrganizerAccess::sanitizeRequestedVerificationMode(
            $request->user(),
            $validated['verification_mode'] ?? CupOrganizerAccess::VERIFICATION_MANUAL
        );

        $aiPreset = (string) ($validated['ai_prompt_preset'] ?? $defaultAiPreset);
        if (! array_key_exists($aiPreset, Cup::aiPromptPresetOptions())) {
            $aiPreset = $defaultAiPreset;
        }

        $allowedPlatforms = $validated['allowed_platforms'] ?? [];
        $allowedPlatforms = is_array($allowedPlatforms) ? $allowedPlatforms : [];
        $allowedPlatforms = collect($allowedPlatforms)
            ->filter(fn ($platform): bool => in_array($platform, ['PC', 'PlayStation', 'Xbox'], true))
            ->unique()
            ->values()
            ->all();

        if ($rulesPreset === 'console_mini_fair' && $allowedPlatforms === []) {
            $allowedPlatforms = ['PlayStation', 'Xbox'];
        }

        $validated['platform'] = $this->platformLabelFromAllowedPlatforms($allowedPlatforms);

        $maxUploads = $this->nullablePositiveInt($validated['max_submissions_per_participant'] ?? null);
        $maxScored = $this->nullablePositiveInt($validated['max_scored_submissions_per_participant'] ?? null);
        $minCommunityActions = max(0, (int) ($validated['min_community_actions'] ?? 0));
        $profileComplete = (bool) ($validated['require_profile_complete'] ?? false);

        if ($rulesPreset === 'console_mini_fair') {
            $maxUploads ??= 7;
            $maxScored ??= 5;
            $minCommunityActions = max(1, $minCommunityActions);
            $profileComplete = true;
        }

        if ($rulesPreset === 'summer_trio_first_trophy') {
            $maxUploads ??= 12;
            $maxScored ??= 5;
            $minCommunityActions = max(1, $minCommunityActions);
            $profileComplete = true;
        }

        foreach ([
            'rules_preset',
            'ai_prompt_preset',
            'verification_mode',
            'allowed_platforms',
            'max_submissions_per_participant',
            'max_scored_submissions_per_participant',
            'require_profile_complete',
            'min_community_actions',
        ] as $key) {
            unset($validated[$key]);
        }

        return [
            'verification_mode' => $verificationMode,
            'rules_preset' => $rulesPreset,
            'ai_prompt_preset' => $aiPreset,
            'platform_gate' => [
                'allowed_platforms' => $allowedPlatforms,
            ],
            'submission_limit' => [
                'max_uploads_per_participant' => $maxUploads,
                'max_scored_runs_per_participant' => $maxScored,
            ],
            'participation_requirements' => [
                'profile_complete' => $profileComplete,
                'min_community_actions' => $minCommunityActions,
            ],
        ];
    }

    private function extractCupContentSettings(array &$validated): array
    {
        $legacy = [
            'summary' => trim((string) ($validated['summary'] ?? '')),
            'description' => trim((string) ($validated['cup_description'] ?? '')),
            'rules' => trim((string) ($validated['rules'] ?? '')),
            'scoring_rules' => trim((string) ($validated['scoring_rules'] ?? '')),
            'prizes' => [
                'first' => trim((string) ($validated['prize_first'] ?? '')),
                'second' => trim((string) ($validated['prize_second'] ?? '')),
                'third' => trim((string) ($validated['prize_third'] ?? '')),
            ],
            'prize_note' => trim((string) ($validated['prize_note'] ?? '')),
            'cashout_note' => trim((string) ($validated['cashout_note'] ?? '')),
            'hall_of_fame_note' => trim((string) ($validated['hall_of_fame_note'] ?? '')),
        ];

        $content = ['locales' => []];

        foreach (['de', 'en'] as $locale) {
            $localeContent = [
                'summary' => trim((string) ($validated['summary_'.$locale] ?? ($locale === 'de' ? $legacy['summary'] : ''))),
                'description' => trim((string) ($validated['cup_description_'.$locale] ?? ($locale === 'de' ? $legacy['description'] : ''))),
                'rules' => trim((string) ($validated['rules_'.$locale] ?? ($locale === 'de' ? $legacy['rules'] : ''))),
                'scoring_rules' => trim((string) ($validated['scoring_rules_'.$locale] ?? ($locale === 'de' ? $legacy['scoring_rules'] : ''))),
                'prizes' => [
                    'first' => trim((string) ($validated['prize_first_'.$locale] ?? ($locale === 'de' ? $legacy['prizes']['first'] : ''))),
                    'second' => trim((string) ($validated['prize_second_'.$locale] ?? ($locale === 'de' ? $legacy['prizes']['second'] : ''))),
                    'third' => trim((string) ($validated['prize_third_'.$locale] ?? ($locale === 'de' ? $legacy['prizes']['third'] : ''))),
                ],
                'prize_note' => trim((string) ($validated['prize_note_'.$locale] ?? ($locale === 'de' ? $legacy['prize_note'] : ''))),
                'cashout_note' => trim((string) ($validated['cashout_note_'.$locale] ?? ($locale === 'de' ? $legacy['cashout_note'] : ''))),
                'hall_of_fame_note' => trim((string) ($validated['hall_of_fame_note_'.$locale] ?? ($locale === 'de' ? $legacy['hall_of_fame_note'] : ''))),
            ];

            $localeContent = $this->cleanContentSettings($localeContent);
            if ($localeContent !== []) {
                $content['locales'][$locale] = $localeContent;
            }
        }

        $content = $this->cleanContentSettings($content);
        $validated['summary'] = $this->firstLocalizedContentValue($content, 'summary');
        $validated['rules'] = $this->firstLocalizedContentValue($content, 'rules');

        foreach ([
            'summary_de', 'summary_en', 'rules_de', 'rules_en',
            'cup_description', 'cup_description_de', 'cup_description_en',
            'scoring_rules', 'scoring_rules_de', 'scoring_rules_en',
            'prize_first', 'prize_first_de', 'prize_first_en',
            'prize_second', 'prize_second_de', 'prize_second_en',
            'prize_third', 'prize_third_de', 'prize_third_en',
            'prize_note', 'prize_note_de', 'prize_note_en',
            'cashout_note', 'cashout_note_de', 'cashout_note_en',
            'hall_of_fame_note', 'hall_of_fame_note_de', 'hall_of_fame_note_en',
        ] as $key) {
            unset($validated[$key]);
        }

        return $content;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function platformLabelFromAllowedPlatforms(array $allowedPlatforms): string
    {
        return collect($allowedPlatforms)
            ->filter(fn ($platform): bool => in_array($platform, ['PC', 'PlayStation', 'Xbox'], true))
            ->unique()
            ->values()
            ->implode(' / ');
    }

    private function firstLocalizedContentValue(array $content, string $key): ?string
    {
        foreach (['de', 'en'] as $locale) {
            $value = data_get($content, 'locales.'.$locale.'.'.$key);
            if (trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function cleanContentSettings(array $content): array
    {
        if (isset($content['prizes']) && is_array($content['prizes'])) {
            $content['prizes'] = array_filter($content['prizes'], fn ($value): bool => trim((string) $value) !== '');
        }

        if (isset($content['locales']) && is_array($content['locales'])) {
            $content['locales'] = array_filter($content['locales'], fn ($value): bool => is_array($value) && $value !== []);
        }

        return array_filter($content, function ($value): bool {
            if (is_array($value)) {
                return $value !== [];
            }

            return trim((string) $value) !== '';
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'cup';
        $slug = $base;
        $counter = 2;

        while (Cup::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
