@extends('themes.socialite.layouts.app')

@section('title', __('ui.cup_feedback_meta_title'))
@section('meta_description', __('ui.cup_feedback_meta_description'))

@section('content')
@php
    $ratingFields = [
        'rating_overall' => __('ui.cup_feedback_rating_overall'),
        'rating_rules' => __('ui.cup_feedback_rating_rules'),
        'rating_scoring' => __('ui.cup_feedback_rating_scoring'),
        'rating_submission' => __('ui.cup_feedback_rating_submission'),
        'rating_fairness' => __('ui.cup_feedback_rating_fairness'),
    ];

    $oldLiked = (array) old('liked_options', []);
    $oldIssues = (array) old('issue_options', []);
    $oldIdeas = (array) old('idea_options', []);
@endphp

<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto">
    <div class="flex-1 min-w-0">
        <div class="page-heading">
            <h1 class="page-title">{{ __('ui.cup_feedback_title') }}</h1>
            <nav class="nav__underline">
                <ul class="group">
                    <li class="uk-active"><a href="#cup-feedback-form">{{ __('ui.cup_feedback_tab_feedback') }}</a></li>
                    <li><a href="#cup-feedback-ideas">{{ __('ui.cup_feedback_tab_ideas') }}</a></li>
                </ul>
            </nav>
        </div>

        @if (session('status'))
            <div class="box p-4 mb-6 border border-green-100 bg-green-50 text-green-700 dark:bg-green-500/10 dark:border-green-500/20 dark:text-green-200">
                <div class="flex items-center gap-3">
                    <ion-icon name="checkmark-circle" class="text-2xl"></ion-icon>
                    <p class="text-sm font-semibold">{{ session('status') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="box p-4 mb-6 border border-red-100 bg-red-50 text-red-700 dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-200">
                <p class="text-sm font-semibold">{{ __('ui.please_check') }}</p>
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="box overflow-hidden mb-6">
            <div class="relative bg-secondery dark:bg-white/5" style="min-height: 255px;">
                <img src="{{ asset('assets/socialite/images/ad_pattern.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950/90 via-slate-900/75 to-red-950/70"></div>
                <div class="relative p-6 sm:p-8 md:p-10 text-white max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide">
                        <ion-icon name="trophy-outline" class="text-lg"></ion-icon>
                        {{ __('ui.cup_feedback_kicker') }}
                    </div>
                    <h2 class="mt-5 text-3xl md:text-4xl font-bold leading-tight">{{ __('ui.cup_feedback_hero_title') }}</h2>
                    <p class="mt-4 text-sm md:text-base text-white/80 leading-7">{{ __('ui.cup_feedback_hero_text') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3 text-xs font-semibold text-white/80">
                        <span class="rounded-full bg-white/10 px-3 py-2">{{ __('ui.cup_feedback_pill_private') }}</span>
                        <span class="rounded-full bg-white/10 px-3 py-2">{{ __('ui.cup_feedback_pill_survey') }}</span>
                        <span class="rounded-full bg-white/10 px-3 py-2">{{ __('ui.cup_feedback_pill_nextcup') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <form id="cup-feedback-form" method="POST" action="{{ route('cup-feedback.store') }}" class="space-y-6">
            @csrf

            <div class="box p-5 sm:p-6">
                <div class="flex items-start gap-3 mb-5">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-secondery text-primary dark:bg-white/5">
                        <ion-icon name="flag-outline" class="text-xl"></ion-icon>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.cup_feedback_section_context') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-white/60">{{ __('ui.cup_feedback_section_context_text') }}</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="cup_id" class="mb-2 block text-sm font-semibold text-black dark:text-white">{{ __('ui.cup_feedback_cup_label') }}</label>
                        <select id="cup_id" name="cup_id" class="w-full !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5">
                            <option value="">{{ __('ui.cup_feedback_cup_global_option') }}</option>
                            @foreach ($cups as $cup)
                                <option value="{{ $cup->id }}" @selected((string) old('cup_id', $selectedCup?->id) === (string) $cup->id)>{{ $cup->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="category" class="mb-2 block text-sm font-semibold text-black dark:text-white">{{ __('ui.cup_feedback_category_label') }}</label>
                        <select id="category" name="category" class="w-full !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5" required>
                            @foreach ($categoryOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'improvement') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="box p-5 sm:p-6">
                <div class="flex items-start gap-3 mb-5">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-secondery text-primary dark:bg-white/5">
                        <ion-icon name="star-outline" class="text-xl"></ion-icon>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.cup_feedback_section_rating') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-white/60">{{ __('ui.cup_feedback_section_rating_text') }}</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($ratingFields as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="mb-2 block text-sm font-semibold text-black dark:text-white">{{ $label }}</label>
                            <select id="{{ $field }}" name="{{ $field }}" class="w-full !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5" @if($field === 'rating_overall') required @endif>
                                <option value="">{{ __('ui.cup_feedback_rating_empty') }}</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected((string) old($field) === (string) $i)>{{ $i }} / 5</option>
                                @endfor
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="would_join_again" class="mb-2 block text-sm font-semibold text-black dark:text-white">{{ __('ui.cup_feedback_join_label') }}</label>
                        <select id="would_join_again" name="would_join_again" class="w-full !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5" required>
                            @foreach ($joinOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('would_join_again', 'yes') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="preferred_next_format" class="mb-2 block text-sm font-semibold text-black dark:text-white">{{ __('ui.cup_feedback_format_label') }}</label>
                        <select id="preferred_next_format" name="preferred_next_format" class="w-full !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5">
                            <option value="">{{ __('ui.cup_feedback_format_empty') }}</option>
                            @foreach ($formatOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('preferred_next_format') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="box p-5 sm:p-6" id="cup-feedback-ideas">
                <div class="flex items-start gap-3 mb-5">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-secondery text-primary dark:bg-white/5">
                        <ion-icon name="bulb-outline" class="text-xl"></ion-icon>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.cup_feedback_section_options') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-white/60">{{ __('ui.cup_feedback_section_options_text') }}</p>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-3">
                    <div>
                        <h3 class="mb-3 text-sm font-bold text-black dark:text-white">{{ __('ui.cup_feedback_liked_title') }}</h3>
                        <div class="space-y-2">
                            @foreach ($likedOptions as $value => $label)
                                <label class="flex items-center gap-3 rounded-xl bg-secondery px-3 py-2 text-sm dark:bg-white/5">
                                    <input type="checkbox" name="liked_options[]" value="{{ $value }}" @checked(in_array($value, $oldLiked, true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-sm font-bold text-black dark:text-white">{{ __('ui.cup_feedback_issue_title') }}</h3>
                        <div class="space-y-2">
                            @foreach ($issueOptions as $value => $label)
                                <label class="flex items-center gap-3 rounded-xl bg-secondery px-3 py-2 text-sm dark:bg-white/5">
                                    <input type="checkbox" name="issue_options[]" value="{{ $value }}" @checked(in_array($value, $oldIssues, true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-sm font-bold text-black dark:text-white">{{ __('ui.cup_feedback_idea_title') }}</h3>
                        <div class="space-y-2">
                            @foreach ($ideaOptions as $value => $label)
                                <label class="flex items-center gap-3 rounded-xl bg-secondery px-3 py-2 text-sm dark:bg-white/5">
                                    <input type="checkbox" name="idea_options[]" value="{{ $value }}" @checked(in_array($value, $oldIdeas, true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="box p-5 sm:p-6">
                <div class="flex items-start gap-3 mb-5">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-secondery text-primary dark:bg-white/5">
                        <ion-icon name="chatbox-ellipses-outline" class="text-xl"></ion-icon>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.cup_feedback_section_ticket') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-white/60">{{ __('ui.cup_feedback_section_ticket_text') }}</p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div>
                        <label for="subject" class="mb-2 block text-sm font-semibold text-black dark:text-white">{{ __('ui.cup_feedback_subject_label') }}</label>
                        <input id="subject" name="subject" value="{{ old('subject') }}" maxlength="160" required class="w-full !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5" placeholder="{{ __('ui.cup_feedback_subject_placeholder') }}">
                    </div>
                    <div>
                        <label for="message" class="mb-2 block text-sm font-semibold text-black dark:text-white">{{ __('ui.cup_feedback_message_label') }}</label>
                        <textarea id="message" name="message" rows="7" maxlength="4000" required class="w-full rounded-xl bg-secondery !border-0 !text-sm leading-6 dark:!bg-white/5" placeholder="{{ __('ui.cup_feedback_message_placeholder') }}">{{ old('message') }}</textarea>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl bg-secondery px-3 py-3 text-sm dark:bg-white/5">
                        <input type="checkbox" name="contact_allowed" value="1" @checked(old('contact_allowed', '1'))>
                        <span>{{ __('ui.cup_feedback_contact_allowed') }}</span>
                    </label>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="submit" class="button bg-primary text-white !w-auto px-6">{{ __('ui.cup_feedback_submit') }}</button>
                    <p class="text-xs text-gray-500 dark:text-white/50">{{ __('ui.cup_feedback_private_note') }}</p>
                </div>
            </div>
        </form>
    </div>

    <aside class="lg:w-[330px] w-full space-y-6">
        <div class="box p-5">
            <h3 class="text-base font-bold text-black dark:text-white">{{ __('ui.cup_feedback_scoring_title') }}</h3>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-white/60">{{ __('ui.cup_feedback_scoring_text') }}</p>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between rounded-xl bg-secondery px-3 py-2 dark:bg-white/5"><span>{{ __('ui.cup_feedback_score_bounty') }}</span><strong>+2</strong></div>
                <div class="flex justify-between rounded-xl bg-secondery px-3 py-2 dark:bg-white/5"><span>{{ __('ui.cup_feedback_score_kill') }}</span><strong>+1</strong></div>
                <div class="flex justify-between rounded-xl bg-secondery px-3 py-2 dark:bg-white/5"><span>{{ __('ui.cup_feedback_score_no_extract') }}</span><strong>0</strong></div>
            </div>
        </div>

        <div class="box p-5">
            <h3 class="text-base font-bold text-black dark:text-white">{{ __('ui.cup_feedback_my_title') }}</h3>
            <div class="mt-4 space-y-3">
                @forelse ($myFeedback as $entry)
                    <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                        <p class="text-sm font-bold text-black dark:text-white">{{ $entry->subject }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-white/50">{{ $entry->cup?->title ?? __('ui.cup_feedback_cup_global_option') }} · {{ $entry->statusLabel() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-white/60">{{ __('ui.cup_feedback_my_empty') }}</p>
                @endforelse
            </div>
        </div>
    </aside>
</div>
@endsection
