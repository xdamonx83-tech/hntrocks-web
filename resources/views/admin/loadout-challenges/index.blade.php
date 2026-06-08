@extends('admin.layouts.app')

@section('title', __('ui.loadout_admin_meta_title'))
@section('admin_heading', __('ui.loadout_admin_title'))

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>{{ __('ui.loadout_admin_title') }}</h1>
        <p>{{ __('ui.loadout_admin_intro') }}</p>
    </div>
    <a class="hh-link-button" href="{{ route('loadout-challenges.index') }}" target="_blank" rel="noopener">{{ __('ui.loadout_admin_open_public') }}</a>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>{{ __('ui.validation_error_title') }}</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="hh-admin-stat-grid">
    <article class="hh-card hh-card-compact"><strong>{{ $stats['active'] }}</strong><span>{{ __('ui.loadout_status_active') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['draft'] }}</strong><span>{{ __('ui.loadout_status_draft') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['archived'] }}</strong><span>{{ __('ui.loadout_status_archived') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['pending_submissions'] }}</strong><span>{{ __('ui.loadout_submission_status_pending') }}</span></article>
</section>

<section class="hh-card hh-card-compact hh-admin-contract-form-card">
    <h2>{{ __('ui.loadout_admin_create') }}</h2>
    <form class="hh-admin-contract-form" method="post" action="{{ route('admin.loadout-challenges.store') }}">
        @csrf
        <div>
            <label>{{ __('ui.loadout_admin_title_field') }}</label>
            <input type="text" name="title" value="{{ old('title') }}" maxlength="160" required placeholder="{{ __('ui.loadout_admin_title_placeholder') }}">
        </div>
        <div>
            <label>Slug</label>
            <input type="text" name="slug" value="{{ old('slug') }}" maxlength="180" placeholder="optional">
        </div>
        <div>
            <label>{{ __('ui.status') }}</label>
            <select name="status" required>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="hh-admin-contract-form-wide">
            <label>{{ __('ui.loadout_admin_summary') }}</label>
            <input type="text" name="summary" value="{{ old('summary') }}" maxlength="240" placeholder="{{ __('ui.loadout_admin_summary_placeholder') }}">
        </div>
        <div class="hh-admin-contract-form-wide">
            <label>{{ __('ui.loadout_description') }}</label>
            <textarea name="description" rows="4" maxlength="8000">{{ old('description') }}</textarea>
        </div>
        <div class="hh-admin-contract-form-wide">
            <label>{{ __('ui.loadout_rules') }}</label>
            <textarea name="rules" rows="4" maxlength="8000">{{ old('rules') }}</textarea>
        </div>
        <div class="hh-admin-contract-form-wide">
            <label>{{ __('ui.loadout_allowed_loadout') }}</label>
            <textarea name="loadout_notes" rows="4" maxlength="8000">{{ old('loadout_notes') }}</textarea>
        </div>
        <div>
            <label>{{ __('ui.loadout_starts') }}</label>
            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}">
        </div>
        <div>
            <label>{{ __('ui.loadout_ends') }}</label>
            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}">
        </div>
        <div>
            <label>XP</label>
            <input type="number" name="xp_reward" value="{{ old('xp_reward', 50) }}" min="0" max="100000">
        </div>
        <div>
            <label>Badge</label>
            <select name="badge_slug">
                <option value="">—</option>
                @foreach($badges as $badge)
                    <option value="{{ $badge->slug }}" @selected(old('badge_slug') === $badge->slug)>{{ $badge->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>{{ __('ui.contract_admin_sort') }}</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="100000">
        </div>
        <label class="hh-admin-check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> {{ __('ui.featured') }}</label>
        <div class="hh-admin-contract-form-actions">
            <button class="hh-primary-button" type="submit">{{ __('ui.loadout_admin_create_button') }}</button>
        </div>
    </form>
</section>

<section class="hh-card hh-card-compact hh-filter-card hh-section-space">
    <form class="hh-admin-filter" method="get" action="{{ route('admin.loadout-challenges.index') }}">
        <div>
            <label>{{ __('ui.status') }}</label>
            <select name="status">
                <option value="">{{ __('ui.all') }}</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>{{ __('ui.loadout_submission_status') }}</label>
            <select name="submission_status">
                <option value="">{{ __('ui.all') }}</option>
                @foreach($submissionStatusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedSubmissionStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="hh-primary-button" type="submit">{{ __('ui.filter') }}</button>
    </form>
</section>

<section class="hh-admin-report-list hh-section-space">
    @forelse($challenges as $challenge)
        <article class="hh-card hh-card-compact hh-admin-contract-card">
            <div class="hh-admin-contract-head">
                <div>
                    <p class="hh-kicker">{{ $challenge->runtimeStatusLabel() }} · {{ $challenge->accepted_submissions_count }}/{{ $challenge->submissions_count }} {{ __('ui.loadout_accepted_short') }}</p>
                    <h2>{{ $challenge->title }}</h2>
                    <p>{{ $challenge->summary ?: __('ui.loadout_no_summary') }}</p>
                    <div class="hh-admin-report-context">
                        <span>{{ $challenge->xp_reward }} XP</span>
                        @if($challenge->starts_at)<span>{{ __('ui.loadout_starts') }}: {{ $challenge->starts_at->format('d.m.Y H:i') }}</span>@endif
                        @if($challenge->ends_at)<span>{{ __('ui.loadout_ends') }}: {{ $challenge->ends_at->format('d.m.Y H:i') }}</span>@endif
                        <a href="{{ route('loadout-challenges.show', $challenge) }}" target="_blank" rel="noopener">{{ __('ui.open') }}</a>
                    </div>
                </div>
                <form method="post" action="{{ route('admin.loadout-challenges.destroy', $challenge) }}" onsubmit="return confirm('{{ __('ui.loadout_admin_delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button class="hh-secondary-button" type="submit">{{ __('ui.delete') }}</button>
                </form>
            </div>

            <details class="hh-admin-contract-details">
                <summary>{{ __('ui.contract_admin_edit') }}</summary>
                <form class="hh-admin-contract-form" method="post" action="{{ route('admin.loadout-challenges.update', $challenge) }}">
                    @csrf
                    @method('PUT')
                    <div>
                        <label>{{ __('ui.loadout_admin_title_field') }}</label>
                        <input type="text" name="title" value="{{ old('title', $challenge->title) }}" maxlength="160" required>
                    </div>
                    <div>
                        <label>Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $challenge->slug) }}" maxlength="180">
                    </div>
                    <div>
                        <label>{{ __('ui.status') }}</label>
                        <select name="status" required>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $challenge->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hh-admin-contract-form-wide">
                        <label>{{ __('ui.loadout_admin_summary') }}</label>
                        <input type="text" name="summary" value="{{ old('summary', $challenge->summary) }}" maxlength="240">
                    </div>
                    <div class="hh-admin-contract-form-wide">
                        <label>{{ __('ui.loadout_description') }}</label>
                        <textarea name="description" rows="4" maxlength="8000">{{ old('description', $challenge->description) }}</textarea>
                    </div>
                    <div class="hh-admin-contract-form-wide">
                        <label>{{ __('ui.loadout_rules') }}</label>
                        <textarea name="rules" rows="4" maxlength="8000">{{ old('rules', $challenge->rules) }}</textarea>
                    </div>
                    <div class="hh-admin-contract-form-wide">
                        <label>{{ __('ui.loadout_allowed_loadout') }}</label>
                        <textarea name="loadout_notes" rows="4" maxlength="8000">{{ old('loadout_notes', $challenge->loadout_notes) }}</textarea>
                    </div>
                    <div>
                        <label>{{ __('ui.loadout_starts') }}</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $challenge->starts_at?->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div>
                        <label>{{ __('ui.loadout_ends') }}</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $challenge->ends_at?->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div>
                        <label>XP</label>
                        <input type="number" name="xp_reward" value="{{ old('xp_reward', $challenge->xp_reward) }}" min="0" max="100000">
                    </div>
                    <div>
                        <label>Badge</label>
                        <select name="badge_slug">
                            <option value="">—</option>
                            @foreach($badges as $badge)
                                <option value="{{ $badge->slug }}" @selected(old('badge_slug', $challenge->badge_slug) === $badge->slug)>{{ $badge->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ __('ui.contract_admin_sort') }}</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $challenge->sort_order) }}" min="0" max="100000">
                    </div>
                    <label class="hh-admin-check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $challenge->is_featured))> {{ __('ui.featured') }}</label>
                    <div class="hh-admin-contract-form-actions">
                        <button class="hh-primary-button" type="submit">{{ __('ui.save') }}</button>
                    </div>
                </form>
            </details>
        </article>
    @empty
        <section class="hh-card hh-card-compact"><p class="hh-muted">{{ __('ui.loadout_admin_empty') }}</p></section>
    @endforelse
</section>

<div class="hh-pagination">{{ $challenges->links() }}</div>

<section class="hh-page-header hh-section-space">
    <div>
        <p class="hh-kicker">Review</p>
        <h1>{{ __('ui.loadout_admin_submissions_title') }}</h1>
        <p>{{ __('ui.loadout_admin_submissions_intro') }}</p>
    </div>
</section>

<section class="hh-admin-report-list">
    @forelse($submissions as $submission)
        <article class="hh-card hh-card-compact">
            <div class="hh-admin-report-head">
                <div>
                    <p class="hh-kicker">{{ $submission->statusLabel() }} · {{ $submission->challenge?->title }}</p>
                    <h2>{{ $submission->user?->name ?? $submission->user?->username ?? __('ui.unknown_user') }}</h2>
                    @if($submission->outcome)<p><strong>{{ __('ui.loadout_submission_outcome') }}:</strong> {{ $submission->outcome }}</p>@endif
                    <p>{{ $submission->body }}</p>
                    <div class="hh-admin-report-context">
                        <span>{{ $submission->created_at->format('d.m.Y H:i') }}</span>
                        @if($submission->media)<a href="{{ $submission->media->url() }}" target="_blank" rel="noopener">{{ __('ui.loadout_open_proof') }}</a>@endif
                        @if($submission->xp_awarded_at)<span>XP: {{ $submission->xp_awarded_at->format('d.m.Y H:i') }}</span>@endif
                    </div>
                </div>
            </div>
            <form class="hh-admin-report-form" method="post" action="{{ route('admin.loadout-challenges.submissions.update', $submission) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="submission_status" value="{{ $selectedSubmissionStatus }}">
                <div>
                    <label>{{ __('ui.status') }}</label>
                    <select name="status" required>
                        @foreach($submissionStatusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $submission->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hh-admin-form-wide">
                    <label>{{ __('ui.admin_note') }}</label>
                    <textarea name="admin_note" rows="3" maxlength="3000">{{ old('admin_note', $submission->admin_note) }}</textarea>
                </div>
                <button class="hh-primary-button" type="submit">{{ __('ui.save') }}</button>
            </form>
        </article>
    @empty
        <section class="hh-card hh-card-compact"><p class="hh-muted">{{ __('ui.loadout_submissions_empty') }}</p></section>
    @endforelse
</section>

<div class="hh-pagination">{{ $submissions->links() }}</div>
@endsection
