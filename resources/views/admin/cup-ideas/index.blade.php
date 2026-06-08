@extends('admin.layouts.app')

@section('title', __('ui.cup_ideas_admin_meta_title'))
@section('admin_heading', __('ui.cup_ideas_admin_title'))

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>{{ __('ui.cup_ideas_admin_title') }}</h1>
        <p>{{ __('ui.cup_ideas_admin_intro') }}</p>
    </div>
    <a class="hh-link-button" href="{{ route('cup-ideas.index') }}" target="_blank" rel="noopener">{{ __('ui.cup_ideas_admin_open_public') }}</a>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-admin-stat-grid">
    <article class="hh-card hh-card-compact"><strong>{{ $stats['new'] }}</strong><span>{{ __('ui.cup_ideas_status_new') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['reviewing'] }}</strong><span>{{ __('ui.cup_ideas_status_reviewing') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['planned'] }}</strong><span>{{ __('ui.cup_ideas_status_planned') }} / {{ __('ui.cup_ideas_status_coming_soon') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['implemented'] }}</strong><span>{{ __('ui.cup_ideas_status_implemented') }}</span></article>
</section>

<section class="hh-card hh-card-compact">
    <form class="hh-admin-filter" method="get" action="{{ route('admin.cup-ideas.index') }}">
        <div>
            <label>{{ __('ui.cup_ideas_filter_category') }}</label>
            <select name="category">
                <option value="">{{ __('ui.cup_ideas_filter_all_categories') }}</option>
                @foreach($categoryOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>{{ __('ui.cup_ideas_filter_status') }}</label>
            <select name="status">
                <option value="">{{ __('ui.cup_ideas_filter_all_statuses') }}</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="hh-primary-button" type="submit">{{ __('ui.filter') }}</button>
        <a class="hh-link-button" href="{{ route('admin.cup-ideas.index') }}">{{ __('ui.reset') }}</a>
    </form>
</section>

<section class="hnt-admin-ideas-list">
    @forelse($ideas as $idea)
        <article class="hh-card hh-card-compact hnt-admin-idea-card">
            <div class="hnt-admin-idea-main">
                <div class="hnt-admin-idea-topline">
                    <span>{{ $idea->categoryLabel() }}</span>
                    <span>{{ $idea->statusLabel() }}</span>
                    <span>{{ $idea->votes_count }} {{ __('ui.cup_ideas_votes') }}</span>
                    @if($idea->is_featured)
                        <span>{{ __('ui.cup_ideas_featured') }}</span>
                    @endif
                </div>
                <h2>{{ $idea->title }}</h2>
                <p>{{ $idea->description }}</p>
                <div class="hnt-admin-idea-meta">
                    <span>{{ __('ui.author') }}: {{ $idea->user?->name ?? '—' }}</span>
                    @if($idea->cup)
                        <span>Cup: {{ $idea->cup->title }}</span>
                    @endif
                    <span>{{ $idea->created_at?->format('d.m.Y H:i') }}</span>
                </div>
            </div>

            <form class="hnt-admin-idea-form" method="post" action="{{ route('admin.cup-ideas.update', $idea) }}">
                @csrf
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
                <input type="hidden" name="filter_status" value="{{ $selectedStatus }}">
                <div>
                    <label>Status</label>
                    <select name="status">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($idea->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="hh-admin-check"><input type="checkbox" name="is_featured" value="1" @checked($idea->is_featured)> {{ __('ui.cup_ideas_featured') }}</label>
                <div>
                    <label>{{ __('ui.admin_note') }}</label>
                    <textarea name="admin_note" rows="4">{{ $idea->admin_note }}</textarea>
                </div>
                <button class="hh-primary-button" type="submit">{{ __('ui.save') }}</button>
            </form>
        </article>
    @empty
        <section class="hh-card hh-card-compact"><p class="hh-muted">{{ __('ui.cup_ideas_admin_empty') }}</p></section>
    @endforelse
</section>

<div class="hh-pagination">{{ $ideas->links() }}</div>
@endsection
