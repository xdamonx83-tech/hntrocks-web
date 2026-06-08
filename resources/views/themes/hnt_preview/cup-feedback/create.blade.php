@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.cup_feedback_meta_title'))
@section('main_class', 'feed-main cup-feedback-main')

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
    $selectedCupId = old('cup_id', $selectedCup?->id);
@endphp

@section('content')
    <div class="cup-feedback-shell">
        @if (session('status'))
            <div class="hnt-crowns-alert success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="hnt-crowns-alert warning">
                <strong>{{ __('ui.please_check') }}</strong>
                <ul style="margin-top: 8px; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="feedback-hero" aria-label="{{ __('ui.cup_feedback_title') }}">
            <div class="feedback-hero-glow"></div>
            <div class="feedback-hero-copy">
                <span class="feedback-kicker">
                    <i class="ph ph-trophy" aria-hidden="true"></i>
                    {{ __('ui.cup_feedback_kicker') }}
                </span>
                <h1>{{ __('ui.cup_feedback_hero_title') }}</h1>
                <p>{{ __('ui.cup_feedback_hero_text') }}</p>
                <div class="feedback-hero-pills">
                    <span>{{ __('ui.cup_feedback_pill_private') }}</span>
                    <span>{{ __('ui.cup_feedback_pill_survey') }}</span>
                    <span>{{ __('ui.cup_feedback_pill_nextcup') }}</span>
                </div>
            </div>
        </section>

        <form id="cup-feedback-form" class="cup-feedback-form" action="{{ route('cup-feedback.store') }}" method="post">
            @csrf

            <section class="feedback-section feedback-context-section">
                <div class="feedback-section-title">
                    <span class="feedback-section-icon">
                        <i class="ph ph-flag" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('ui.cup_feedback_section_context') }}</h2>
                        <p>{{ __('ui.cup_feedback_section_context_text') }}</p>
                    </div>
                </div>

                <div class="feedback-form-grid two">
                    <label class="feedback-field" for="cup_id">
                        <span>{{ __('ui.cup_feedback_cup_label') }}</span>
                        <select id="cup_id" name="cup_id">
                            <option value="">{{ __('ui.cup_feedback_cup_global_option') }}</option>
                            @foreach ($cups as $cup)
                                <option value="{{ $cup->id }}" @selected((string) $selectedCupId === (string) $cup->id)>{{ $cup->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="feedback-field" for="category">
                        <span>{{ __('ui.cup_feedback_category_label') }}</span>
                        <select id="category" name="category" required>
                            @foreach ($categoryOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'improvement') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            <section class="feedback-section">
                <div class="feedback-section-title">
                    <span class="feedback-section-icon">
                        <i class="ph ph-star" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('ui.cup_feedback_section_rating') }}</h2>
                        <p>{{ __('ui.cup_feedback_section_rating_text') }}</p>
                    </div>
                </div>

                <div class="feedback-form-grid two">
                    @foreach ($ratingFields as $field => $label)
                        <label class="feedback-field" for="{{ $field }}">
                            <span>{{ $label }}</span>
                            <select id="{{ $field }}" name="{{ $field }}" @if($field === 'rating_overall') required @endif>
                                <option value="">{{ __('ui.cup_feedback_rating_empty') }}</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected((string) old($field) === (string) $i)>{{ $i }} / 5</option>
                                @endfor
                            </select>
                        </label>
                    @endforeach

                    <span class="feedback-grid-spacer" aria-hidden="true"></span>

                    <label class="feedback-field" for="would_join_again">
                        <span>{{ __('ui.cup_feedback_join_label') }}</span>
                        <select id="would_join_again" name="would_join_again" required>
                            @foreach ($joinOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('would_join_again', 'yes') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="feedback-field" for="preferred_next_format">
                        <span>{{ __('ui.cup_feedback_format_label') }}</span>
                        <select id="preferred_next_format" name="preferred_next_format">
                            <option value="">{{ __('ui.cup_feedback_format_empty') }}</option>
                            @foreach ($formatOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('preferred_next_format') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            <section class="feedback-section" id="cup-feedback-ideas">
                <div class="feedback-section-title">
                    <span class="feedback-section-icon">
                        <i class="ph ph-lightbulb" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('ui.cup_feedback_section_options') }}</h2>
                        <p>{{ __('ui.cup_feedback_section_options_text') }}</p>
                    </div>
                </div>

                <div class="feedback-check-columns">
                    <div>
                        <h3>{{ __('ui.cup_feedback_liked_title') }}</h3>
                        @foreach ($likedOptions as $value => $label)
                            <label>
                                <input type="checkbox" name="liked_options[]" value="{{ $value }}" @checked(in_array($value, $oldLiked, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div>
                        <h3>{{ __('ui.cup_feedback_issue_title') }}</h3>
                        @foreach ($issueOptions as $value => $label)
                            <label>
                                <input type="checkbox" name="issue_options[]" value="{{ $value }}" @checked(in_array($value, $oldIssues, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div>
                        <h3>{{ __('ui.cup_feedback_idea_title') }}</h3>
                        @foreach ($ideaOptions as $value => $label)
                            <label>
                                <input type="checkbox" name="idea_options[]" value="{{ $value }}" @checked(in_array($value, $oldIdeas, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="feedback-section feedback-ticket-section">
                <div class="feedback-section-title">
                    <span class="feedback-section-icon">
                        <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('ui.cup_feedback_section_ticket') }}</h2>
                        <p>{{ __('ui.cup_feedback_section_ticket_text') }}</p>
                    </div>
                </div>

                <div class="feedback-form-grid one">
                    <label class="feedback-field" for="subject">
                        <span>{{ __('ui.cup_feedback_subject_label') }}</span>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" maxlength="160" required placeholder="{{ __('ui.cup_feedback_subject_placeholder') }}">
                    </label>

                    <label class="feedback-field" for="message">
                        <span>{{ __('ui.cup_feedback_message_label') }}</span>
                        <textarea id="message" name="message" rows="7" maxlength="4000" required placeholder="{{ __('ui.cup_feedback_message_placeholder') }}">{{ old('message') }}</textarea>
                    </label>

                    <div class="feedback-check-columns" style="grid-template-columns: 1fr; gap: 0;">
                        <label>
                            <input type="checkbox" name="contact_allowed" value="1" @checked(old('contact_allowed', '1'))>
                            <span>{{ __('ui.cup_feedback_contact_allowed') }}</span>
                        </label>
                    </div>
                </div>

                <div class="feedback-actions">
                    <p class="post-text" style="margin: 0;">{{ __('ui.cup_feedback_private_note') }}</p>
                    <button type="submit" class="btn-create">{{ __('ui.cup_feedback_submit') }}</button>
                </div>
            </section>
        </form>

        <section class="hall-note-grid" style="margin-top: 34px;">
            <article class="hall-note-item">
                <i class="ph ph-star" aria-hidden="true"></i>
                <div>
                    <h3>{{ __('ui.cup_feedback_scoring_title') }}</h3>
                    <p>{{ __('ui.cup_feedback_scoring_text') }}</p>
                </div>
            </article>

            <article class="hall-note-item">
                <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
                <div>
                    <h3>{{ __('ui.cup_feedback_my_title') }}</h3>
                    @forelse ($myFeedback as $entry)
                        <p>{{ $entry->subject }} · {{ $entry->cup?->title ?? __('ui.cup_feedback_cup_global_option') }} · {{ $entry->statusLabel() }}</p>
                    @empty
                        <p>{{ __('ui.cup_feedback_my_empty') }}</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
@endsection
