@csrf
@if ($cup->exists)
    @method('put')
@endif

@php
    $cupSettings = is_array($cup->settings) ? $cup->settings : [];
    $selectedRulesPreset = old('rules_preset', data_get($cupSettings, 'rules_preset', 'classic_bounty'));
    $defaultAiPromptPreset = match ($selectedRulesPreset) {
        'console_mini_fair' => 'console_platform',
        'summer_trio_first_trophy' => 'awards_first_trophy',
        default => 'classic_summary',
    };
    $selectedAiPromptPreset = old('ai_prompt_preset', data_get($cupSettings, 'ai_prompt_preset', $defaultAiPromptPreset));
    $platformOptions = [
        'PC' => 'PC',
        'PlayStation' => 'PlayStation 5',
        'Xbox' => 'Xbox Series X|S',
    ];
    $selectedAllowedPlatforms = old('allowed_platforms', data_get($cupSettings, 'platform_gate.allowed_platforms', []));
    $selectedAllowedPlatforms = is_array($selectedAllowedPlatforms) ? $selectedAllowedPlatforms : [];
    if ($selectedAllowedPlatforms === [] && $cup->exists && method_exists($cup, 'allowedPlatforms')) {
        $selectedAllowedPlatforms = $cup->allowedPlatforms();
    }
    $selectedAllowedPlatforms = collect($selectedAllowedPlatforms)
        ->filter(fn ($platform): bool => array_key_exists((string) $platform, $platformOptions))
        ->unique()
        ->values()
        ->all();
    $maxSubmissionsPerParticipant = old('max_submissions_per_participant', data_get($cupSettings, 'submission_limit.max_uploads_per_participant'));
    $maxScoredSubmissionsPerParticipant = old('max_scored_submissions_per_participant', data_get($cupSettings, 'submission_limit.max_scored_runs_per_participant'));
    $requiresProfileComplete = (bool) old('require_profile_complete', data_get($cupSettings, 'participation_requirements.profile_complete', false));
    $minCommunityActions = old('min_community_actions', data_get($cupSettings, 'participation_requirements.min_community_actions', 0));

    $fieldClass = 'w-full rounded-xl bg-secondery !border-0 !text-sm !text-black dark:!bg-white/5 dark:!text-white';
    $textareaClass = $fieldClass . ' leading-6';
    $labelClass = 'block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-white/60 mb-2';
    $helpClass = 'mt-2 text-xs leading-5 text-gray-500 dark:text-white/60';
    $sectionTitleClass = 'text-base font-bold text-black dark:text-white';
    $sectionTextClass = 'text-sm leading-6 text-gray-500 dark:text-white/70';
    $cardClass = 'box p-5 px-6 space-y-5';
    $heroImage = $cup->exists && $cup->cover_path ? $cup->coverUrl() : asset('assets/socialite/images/product/product-10.jpg');
    $fallbackTitle = $cup->exists ? $cup->title : __('ui.cup_create_title');
    $displayTitle = old('title', $cup->title) ?: $fallbackTitle;
    $displayStatus = old('status', $cup->status ?: 'planned');
    $displayPlatform = $selectedAllowedPlatforms !== []
        ? collect($selectedAllowedPlatforms)->map(fn ($platform) => $platformOptions[$platform] ?? $platform)->implode(' / ')
        : (old('platform', $cup->platform) ?: __('ui.cup_form_all_open'));
    $displayTeamSize = old('team_size', $cup->team_size ?: 1);
    $displayStart = old('starts_at', $cup->starts_at?->format('Y-m-d\TH:i'));
    $displayEnd = old('ends_at', $cup->ends_at?->format('Y-m-d\TH:i'));
    $submitLabel = $cup->exists ? __('ui.cup_save') : __('ui.cup_create_title');
    $backUrl = $cup->exists ? route('cups.show', $cup) : route('cups.index');
    $backLabel = $cup->exists ? __('ui.cup_to_cup') : __('ui.cup_back_overview');

    $cupLocaleContentValue = function (string $field, string $locale) use ($cup): string {
        $settingKey = match ($field) {
            'summary' => 'summary',
            'cup_description' => 'description',
            'rules' => 'rules',
            'scoring_rules' => 'scoring_rules',
            'prize_first' => 'prizes.first',
            'prize_second' => 'prizes.second',
            'prize_third' => 'prizes.third',
            'prize_note' => 'prize_note',
            'cashout_note' => 'cashout_note',
            'hall_of_fame_note' => 'hall_of_fame_note',
            default => $field,
        };

        $legacy = match ($field) {
            'summary' => $cup->summary,
            'rules' => $cup->rules,
            default => '',
        };

        $stored = $cup->contentSetting('locales.'.$locale.'.'.$settingKey);
        if ($stored === null && $locale === 'de') {
            $stored = $cup->contentSetting($settingKey, $legacy);
        }

        return old($field.'_'.$locale, (string) ($stored ?? ''));
    };
@endphp

<div class="lg:flex 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-cup-form-shell">
    <div class="flex-1 space-y-6">
        <div class="rounded-lg box overflow-hidden">
            <div class="relative min-h-[260px] overflow-hidden rounded-t-xl bg-dark2">
                <img src="{{ $heroImage }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/70 to-black/20"></div>
                <div class="relative p-6 md:p-8 flex min-h-[260px] flex-col justify-end">
                    <div class="inline-flex w-max rounded-full bg-yellow-500/15 px-3 py-1 text-xs font-bold uppercase tracking-widest text-yellow-500">
                        {{ $cup->exists ? __('ui.cup_edit_title') : __('ui.cup_create_kicker') }}
                    </div>
                    <h1 class="mt-4 text-3xl font-bold leading-tight text-white md:text-4xl">{{ $displayTitle }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/75">
                        {{ $cup->exists ? __('ui.cup_edit_intro') : __('ui.cup_create_intro') }}
                    </p>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-8">
                <section class="space-y-4">
                    <div>
                        <h2 class="{{ $sectionTitleClass }}">{{ __('ui.cup_info_setup') }}</h2>
                        <p class="{{ $sectionTextClass }}">{{ __('ui.cup_create_intro') }}</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}" for="title">{{ __('ui.cup_form_name') }}</label>
                            <input class="{{ $fieldClass }}" id="title" name="title" type="text" value="{{ old('title', $cup->title) }}" maxlength="140" required>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="status">{{ __('ui.cup_table_status') }}</label>
                            <select class="{{ $fieldClass }}" id="status" name="status" required>
                                @foreach (['planned' => __('ui.cup_status_planned'), 'active' => __('ui.cup_status_active'), 'finished' => __('ui.cup_status_finished'), 'archived' => __('ui.cup_status_archived')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $cup->status ?: 'planned') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ __('ui.platform') }}</label>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach ($platformOptions as $platformValue => $platformLabel)
                                    <label class="hnt-cup-check-option flex cursor-pointer items-center gap-3 rounded-xl bg-secondery px-3 py-2 text-sm font-semibold text-black dark:bg-white/5 dark:text-white">
                                        <input class="hnt-cup-check-input" type="checkbox" name="allowed_platforms[]" value="{{ $platformValue }}" @checked(in_array($platformValue, $selectedAllowedPlatforms, true))>
                                        <span class="hnt-cup-check-box" aria-hidden="true"></span>
                                        <span>{{ $platformLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="{{ $helpClass }}">{{ __('ui.cup_form_allowed_platforms_hint') }}</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="region">{{ __('ui.region') }}</label>
                            <select class="{{ $fieldClass }}" id="region" name="region">
                                <option value="">{{ __('ui.cup_form_region_open') }}</option>
                                @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                    <option value="{{ $option }}" @selected(old('region', $cup->region) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="language">{{ __('ui.language') }}</label>
                            <input class="{{ $fieldClass }}" id="language" name="language" type="text" value="{{ old('language', $cup->language) }}" maxlength="60" placeholder="{{ __('ui.cup_form_language_placeholder') }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="visibility">{{ __('ui.profile_visibility') }}</label>
                            <select class="{{ $fieldClass }}" id="visibility" name="visibility" required>
                                @foreach (['public' => __('ui.cup_visibility_public'), 'private' => __('ui.cup_visibility_private')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('visibility', $cup->visibility ?: 'public') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="team_size">{{ __('ui.cup_form_team_size') }}</label>
                            <input class="{{ $fieldClass }}" id="team_size" name="team_size" type="number" min="1" max="4" value="{{ old('team_size', $cup->team_size ?: 1) }}" required>
                            <p class="{{ $helpClass }}">{{ __('ui.cup_form_team_size_hint') }}</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="max_teams">{{ __('ui.cup_form_max_teams') }}</label>
                            <input class="{{ $fieldClass }}" id="max_teams" name="max_teams" type="number" min="2" max="256" value="{{ old('max_teams', $cup->max_teams) }}">
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="{{ $sectionTitleClass }}">{{ __('ui.cup_info_period') }}</h2>
                        <p class="{{ $sectionTextClass }}">{{ __('ui.cup_detail_banner_text') }}</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}" for="starts_at">{{ __('ui.cup_start') }}</label>
                            <input class="{{ $fieldClass }}" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $cup->starts_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="ends_at">{{ __('ui.cup_end') }}</label>
                            <input class="{{ $fieldClass }}" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $cup->ends_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="registration_opens_at">{{ __('ui.cup_form_registration_opens') }}</label>
                            <input class="{{ $fieldClass }}" id="registration_opens_at" name="registration_opens_at" type="datetime-local" value="{{ old('registration_opens_at', $cup->registration_opens_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="registration_closes_at">{{ __('ui.cup_form_registration_closes') }}</label>
                            <input class="{{ $fieldClass }}" id="registration_closes_at" name="registration_closes_at" type="datetime-local" value="{{ old('registration_closes_at', $cup->registration_closes_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="{{ $sectionTitleClass }}">{{ __('ui.cup_form_ruleset_title') }}</h2>
                        <p class="{{ $sectionTextClass }}">{{ __('ui.cup_form_ruleset_help') }}</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}" for="rules_preset">{{ __('ui.cup_form_rules_preset') }}</label>
                            <select class="{{ $fieldClass }}" id="rules_preset" name="rules_preset">
                                @foreach (\App\Models\Cup::rulesPresetOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedRulesPreset === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $helpClass }}">{{ __('ui.cup_form_rules_preset_hint') }}</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="ai_prompt_preset">{{ __('ui.cup_form_ai_prompt_preset') }}</label>
                            <select class="{{ $fieldClass }}" id="ai_prompt_preset" name="ai_prompt_preset">
                                @foreach (\App\Models\Cup::aiPromptPresetOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedAiPromptPreset === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $helpClass }}">{{ __('ui.cup_form_ai_prompt_preset_hint') }}</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="max_submissions_per_participant">{{ __('ui.cup_form_max_submissions_per_participant') }}</label>
                            <input class="{{ $fieldClass }}" id="max_submissions_per_participant" name="max_submissions_per_participant" type="number" min="0" max="99" value="{{ $maxSubmissionsPerParticipant }}" placeholder="7">
                            <p class="{{ $helpClass }}">{{ __('ui.cup_form_max_submissions_per_participant_hint') }}</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="max_scored_submissions_per_participant">{{ __('ui.cup_form_max_scored_submissions_per_participant') }}</label>
                            <input class="{{ $fieldClass }}" id="max_scored_submissions_per_participant" name="max_scored_submissions_per_participant" type="number" min="0" max="99" value="{{ $maxScoredSubmissionsPerParticipant }}" placeholder="5">
                            <p class="{{ $helpClass }}">{{ __('ui.cup_form_max_scored_submissions_per_participant_hint') }}</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="min_community_actions">{{ __('ui.cup_form_min_community_actions') }}</label>
                            <input class="{{ $fieldClass }}" id="min_community_actions" name="min_community_actions" type="number" min="0" max="20" value="{{ $minCommunityActions }}" placeholder="1">
                            <p class="{{ $helpClass }}">{{ __('ui.cup_form_min_community_actions_hint') }}</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ __('ui.cup_form_require_profile_complete') }}</label>
                            <label class="hnt-cup-check-option flex cursor-pointer items-start gap-3 rounded-xl bg-secondery p-3 text-sm dark:bg-white/5">
                                <input type="hidden" name="require_profile_complete" value="0">
                                <input class="hnt-cup-check-input mt-1" type="checkbox" name="require_profile_complete" value="1" @checked($requiresProfileComplete)>
                                <span class="hnt-cup-check-box mt-1" aria-hidden="true"></span>
                                <span>
                                    <span class="font-semibold text-black dark:text-white">{{ __('ui.cup_form_require_profile_complete') }}</span>
                                    <span class="block {{ $helpClass }} mt-1">{{ __('ui.cup_form_require_profile_complete_hint') }}</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </section>

                <section class="space-y-5">
                    <div>
                        <h2 class="{{ $sectionTitleClass }}">{{ __('ui.cup_form_content_title') }}</h2>
                        <p class="{{ $sectionTextClass }}">{{ __('ui.cup_form_multilingual_help') }}</p>
                    </div>

                    <div class="grid xl:grid-cols-2 gap-5">
                        @foreach (['de' => __('ui.language_german'), 'en' => __('ui.language_english')] as $locale => $languageLabel)
                            <div class="rounded-xl bg-secondery p-4 dark:bg-white/5 space-y-4">
                                <h3 class="text-sm font-bold text-black dark:text-white">{{ __('ui.cup_form_language_section', ['language' => $languageLabel]) }}</h3>

                                <div>
                                    <label class="{{ $labelClass }}" for="summary_{{ $locale }}">{{ __('ui.cup_form_summary') }}</label>
                                    <textarea class="{{ $textareaClass }}" id="summary_{{ $locale }}" name="summary_{{ $locale }}" rows="3" maxlength="255" placeholder="{{ __('ui.cup_form_summary_placeholder') }}">{{ $cupLocaleContentValue('summary', $locale) }}</textarea>
                                    <p class="{{ $helpClass }}">{{ __('ui.cup_form_summary_help') }}</p>
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}" for="cup_description_{{ $locale }}">{{ __('ui.cup_form_description') }}</label>
                                    <textarea class="{{ $textareaClass }}" id="cup_description_{{ $locale }}" name="cup_description_{{ $locale }}" rows="6" maxlength="12000" placeholder="{{ __('ui.cup_form_description_placeholder') }}">{{ $cupLocaleContentValue('cup_description', $locale) }}</textarea>
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}" for="rules_{{ $locale }}">{{ __('ui.cup_form_rules') }}</label>
                                    <textarea class="{{ $textareaClass }}" id="rules_{{ $locale }}" name="rules_{{ $locale }}" rows="8" maxlength="6000" placeholder="{{ __('ui.cup_form_rules_placeholder') }}">{{ $cupLocaleContentValue('rules', $locale) }}</textarea>
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}" for="scoring_rules_{{ $locale }}">{{ __('ui.cup_form_scoring_rules') }}</label>
                                    <textarea class="{{ $textareaClass }}" id="scoring_rules_{{ $locale }}" name="scoring_rules_{{ $locale }}" rows="4" maxlength="6000" placeholder="{{ __('ui.cup_form_scoring_rules_placeholder') }}">{{ $cupLocaleContentValue('scoring_rules', $locale) }}</textarea>
                                </div>

                                <div class="pt-2 border-t border-slate-200 dark:border-white/10 space-y-4">
                                    <h4 class="text-sm font-bold text-black dark:text-white">{{ __('ui.cup_form_prizes_title') }}</h4>
                                    <p class="{{ $helpClass }}">{{ __('ui.cup_form_prizes_help') }}</p>

                                    <div>
                                        <label class="{{ $labelClass }}" for="prize_first_{{ $locale }}">{{ __('ui.cup_form_prize_first') }}</label>
                                        <textarea class="{{ $textareaClass }}" id="prize_first_{{ $locale }}" name="prize_first_{{ $locale }}" rows="3" maxlength="1000" placeholder="{{ __('ui.cup_form_prize_first_placeholder') }}">{{ $cupLocaleContentValue('prize_first', $locale) }}</textarea>
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}" for="prize_second_{{ $locale }}">{{ __('ui.cup_form_prize_second') }}</label>
                                        <textarea class="{{ $textareaClass }}" id="prize_second_{{ $locale }}" name="prize_second_{{ $locale }}" rows="3" maxlength="1000" placeholder="{{ __('ui.cup_form_prize_second_placeholder') }}">{{ $cupLocaleContentValue('prize_second', $locale) }}</textarea>
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}" for="prize_third_{{ $locale }}">{{ __('ui.cup_form_prize_third') }}</label>
                                        <textarea class="{{ $textareaClass }}" id="prize_third_{{ $locale }}" name="prize_third_{{ $locale }}" rows="3" maxlength="1000" placeholder="{{ __('ui.cup_form_prize_third_placeholder') }}">{{ $cupLocaleContentValue('prize_third', $locale) }}</textarea>
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}" for="prize_note_{{ $locale }}">{{ __('ui.cup_form_prize_note') }}</label>
                                        <textarea class="{{ $textareaClass }}" id="prize_note_{{ $locale }}" name="prize_note_{{ $locale }}" rows="4" maxlength="3000" placeholder="{{ __('ui.cup_form_prize_note_placeholder') }}">{{ $cupLocaleContentValue('prize_note', $locale) }}</textarea>
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}" for="cashout_note_{{ $locale }}">{{ __('ui.cup_form_cashout_note') }}</label>
                                        <textarea class="{{ $textareaClass }}" id="cashout_note_{{ $locale }}" name="cashout_note_{{ $locale }}" rows="3" maxlength="2000" placeholder="{{ __('ui.cup_form_cashout_note_placeholder') }}">{{ $cupLocaleContentValue('cashout_note', $locale) }}</textarea>
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}" for="hall_of_fame_note_{{ $locale }}">{{ __('ui.cup_form_hall_of_fame_note') }}</label>
                                        <textarea class="{{ $textareaClass }}" id="hall_of_fame_note_{{ $locale }}" name="hall_of_fame_note_{{ $locale }}" rows="3" maxlength="2000" placeholder="{{ __('ui.cup_form_hall_of_fame_note_placeholder') }}">{{ $cupLocaleContentValue('hall_of_fame_note', $locale) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="{{ $sectionTitleClass }}">{{ __('ui.cup_form_cover') }}</h2>
                        <p class="{{ $sectionTextClass }}">{{ __('ui.cup_cover_alt', ['title' => $displayTitle]) }}</p>
                    </div>
                    <input class="{{ $fieldClass }} p-3" id="cover" name="cover" type="file" accept="image/*">
                </section>
            </div>
        </div>
    </div>

    <aside class="2xl:w-[380px] lg:w-[330px] w-full">
        <div class="lg:space-y-6 space-y-4 lg:pb-8 max-lg:grid sm:grid-cols-2 max-lg:gap-6" uk-sticky="media: 1024; end: #js-cup-form-shell; offset: 80">
            <div class="{{ $cardClass }}">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-yellow-500">{{ __('ui.cup_info_setup') }}</p>
                    <h3 class="mt-2 text-xl font-bold text-black dark:text-white">{{ $displayTitle }}</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-white/70">{{ $cup->exists ? __('ui.cup_edit_intro') : __('ui.cup_create_intro') }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                        <div class="text-xs text-gray-500 dark:text-white/60">{{ __('ui.cup_table_status') }}</div>
                        <div class="mt-1 font-bold text-black dark:text-white">{{ __('ui.cup_status_'.$displayStatus) }}</div>
                    </div>
                    <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                        <div class="text-xs text-gray-500 dark:text-white/60">{{ __('ui.platform') }}</div>
                        <div class="mt-1 font-bold text-black dark:text-white">{{ $displayPlatform }}</div>
                    </div>
                    <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                        <div class="text-xs text-gray-500 dark:text-white/60">{{ __('ui.cup_form_team_size') }}</div>
                        <div class="mt-1 font-bold text-black dark:text-white">{{ $displayTeamSize }}</div>
                    </div>
                    <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                        <div class="text-xs text-gray-500 dark:text-white/60">{{ __('ui.cup_form_rules_preset') }}</div>
                        <div class="mt-1 font-bold text-black dark:text-white">{{ \App\Models\Cup::rulesPresetOptions()[$selectedRulesPreset] ?? $selectedRulesPreset }}</div>
                    </div>
                </div>

                <div class="space-y-2 text-sm text-gray-500 dark:text-white/70">
                    <div class="flex justify-between gap-3"><span>{{ __('ui.cup_start') }}</span><strong class="text-black dark:text-white">{{ $displayStart ?: __('ui.cup_open') }}</strong></div>
                    <div class="flex justify-between gap-3"><span>{{ __('ui.cup_end') }}</span><strong class="text-black dark:text-white">{{ $displayEnd ?: __('ui.cup_open') }}</strong></div>
                    <div class="flex justify-between gap-3"><span>{{ __('ui.cup_form_max_submissions_per_participant') }}</span><strong class="text-black dark:text-white">{{ $maxSubmissionsPerParticipant ?: '∞' }}</strong></div>
                    <div class="flex justify-between gap-3"><span>{{ __('ui.cup_form_max_scored_submissions_per_participant') }}</span><strong class="text-black dark:text-white">{{ $maxScoredSubmissionsPerParticipant ?: '∞' }}</strong></div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button class="button bg-primary text-white flex-1 py-2" type="submit">{{ $submitLabel }}</button>
                    <a class="button bg-secondery px-4" href="{{ $backUrl }}" uk-tooltip="title: {{ $backLabel }}; offset: 8">
                        <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
                    </a>
                </div>
            </div>

            <div class="{{ $cardClass }}">
                <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.cup_form_ruleset_title') }}</h3>
                <div class="space-y-3 text-sm text-gray-500 dark:text-white/70">
                    <p>{{ __('ui.cup_form_rules_preset_hint') }}</p>
                    <p>{{ __('ui.cup_form_ai_prompt_preset_hint') }}</p>
                    <p>{{ __('ui.cup_form_allowed_platforms_hint') }}</p>
                </div>
            </div>
        </div>
    </aside>
</div>
