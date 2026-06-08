@extends('admin.layouts.app')

@section('title', __('ui.cup_feedback_admin_meta_title'))

@section('admin_heading', __('ui.cup_feedback_admin_title'))

@section('content')
@php
    $labelsFor = static function ($values, array $options): string {
        return collect($values ?? [])
            ->map(fn ($value) => $options[$value] ?? $value)
            ->filter()
            ->implode(', ');
    };
@endphp

<section class="hh-page-header">
    <div>
        <p class="hh-kicker">{{ __('ui.admin') }}</p>
        <h1>{{ __('ui.cup_feedback_admin_title') }}</h1>
        <p>{{ __('ui.cup_feedback_admin_intro') }}</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-admin-stat-grid">
    <article class="hh-card hh-card-compact"><strong>{{ $stats['new'] }}</strong><span>{{ __('ui.cup_feedback_status_new') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['reviewing'] }}</strong><span>{{ __('ui.cup_feedback_status_reviewing') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['planned'] }}</strong><span>{{ __('ui.cup_feedback_status_planned') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['resolved'] }}</strong><span>{{ __('ui.cup_feedback_status_resolved') }}</span></article>
</section>

<section class="hh-card hh-card-compact hh-filter-card">
    <form class="hh-admin-filter" method="get" action="{{ route('admin.cup-feedback.index') }}">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">{{ __('ui.all') }}</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category">Kategorie</label>
            <select id="category" name="category">
                <option value="">{{ __('ui.all') }}</option>
                @foreach($categoryOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="cup_id">Cup</label>
            <select id="cup_id" name="cup_id">
                <option value="">{{ __('ui.all') }}</option>
                @foreach($cups as $cup)
                    <option value="{{ $cup->id }}" @selected((string) ($filters['cup_id'] ?? '') === (string) $cup->id)>{{ $cup->title }}</option>
                @endforeach
            </select>
        </div>
        <button class="hh-primary-button" type="submit">{{ __('ui.filter') }}</button>
    </form>
</section>

<section class="hh-admin-report-list">
    @forelse($feedbackEntries as $feedback)
        @php($average = $feedback->averageRating())
        <article class="hh-card hh-card-compact">
            <div class="hh-admin-report-head">
                <div>
                    <p class="hh-kicker">{{ $feedback->statusLabel() }} · {{ $feedback->categoryLabel() }}</p>
                    <h2>{{ $feedback->subject }}</h2>
                    <p>{{ $feedback->message }}</p>

                    <div class="hh-admin-report-context">
                        <span>{{ $feedback->cup?->title ?? __('ui.cup_feedback_cup_global_option') }}</span>
                        <span>{{ $feedback->user?->username ? '@'.$feedback->user->username : ($feedback->user?->name ?? __('ui.deleted_user')) }}</span>
                        <span>{{ $feedback->submitted_at?->diffForHumans() ?? $feedback->created_at->diffForHumans() }}</span>
                        @if($feedback->assignee)
                            <span>{{ __('ui.cup_feedback_assigned_to') }}: {{ $feedback->assignee->username ? '@'.$feedback->assignee->username : $feedback->assignee->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="hh-admin-report-meta">
                    <span>{{ __('ui.cup_feedback_admin_avg_rating') }}</span>
                    <strong>{{ $average !== null ? number_format($average, 1, ',', '.') : '—' }}</strong>
                </div>
            </div>

            <div class="hh-admin-grid" style="margin-top: 1rem;">
                <div class="hh-card hh-card-compact">
                    <h3>{{ __('ui.cup_feedback_admin_rating') }}</h3>
                    <p class="hh-muted">{{ __('ui.cup_feedback_rating_overall') }}: {{ $feedback->rating_overall ?? '—' }}/5 · {{ __('ui.cup_feedback_rating_rules') }}: {{ $feedback->rating_rules ?? '—' }}/5 · {{ __('ui.cup_feedback_rating_scoring') }}: {{ $feedback->rating_scoring ?? '—' }}/5</p>
                    <p class="hh-muted">{{ __('ui.cup_feedback_rating_submission') }}: {{ $feedback->rating_submission ?? '—' }}/5 · {{ __('ui.cup_feedback_rating_fairness') }}: {{ $feedback->rating_fairness ?? '—' }}/5</p>
                    <p class="hh-muted">{{ __('ui.cup_feedback_would_join_again') }}: {{ $feedback->wouldJoinAgainLabel() }} · {{ __('ui.cup_feedback_preferred_format') }}: {{ $feedback->nextFormatLabel() }}</p>
                </div>
                <div class="hh-card hh-card-compact">
                    <h3>{{ __('ui.cup_feedback_admin_options') }}</h3>
                    <p class="hh-muted"><strong>{{ __('ui.cup_feedback_admin_liked') }}:</strong> {{ $labelsFor($feedback->liked_options, \App\Models\CupFeedbackEntry::likedOptions()) ?: '—' }}</p>
                    <p class="hh-muted"><strong>{{ __('ui.cup_feedback_admin_issues') }}:</strong> {{ $labelsFor($feedback->issue_options, \App\Models\CupFeedbackEntry::issueOptions()) ?: '—' }}</p>
                    <p class="hh-muted"><strong>{{ __('ui.cup_feedback_admin_ideas') }}:</strong> {{ $labelsFor($feedback->idea_options, \App\Models\CupFeedbackEntry::ideaOptions()) ?: '—' }}</p>
                </div>
            </div>

            <form class="hh-admin-report-form" method="post" action="{{ route('admin.cup-feedback.update', $feedback) }}">
                @csrf
                <div>
                    <label>Status</label>
                    <select name="status">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($feedback->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>{{ __('ui.cup_feedback_admin_note') }}</label>
                    <input type="text" name="admin_note" value="{{ $feedback->admin_note }}" placeholder="{{ __('ui.cup_feedback_admin_note_placeholder') }}">
                </div>
                <button class="hh-primary-button" type="submit">{{ __('ui.save') }}</button>
            </form>
        </article>
    @empty
        <section class="hh-card hh-card-compact">
            <p class="hh-muted">{{ __('ui.cup_feedback_admin_empty') }}</p>
        </section>
    @endforelse
</section>

<div class="hh-pagination">
    {{ $feedbackEntries->links() }}
</div>
@endsection
