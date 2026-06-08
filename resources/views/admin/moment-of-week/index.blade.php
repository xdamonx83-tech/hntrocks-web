@extends('admin.layouts.app')

@section('title', __('ui.moment_week_admin_meta_title'))
@section('admin_heading', __('ui.moment_week_admin_title'))

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>{{ __('ui.moment_week_admin_title') }}</h1>
        <p>{{ __('ui.moment_week_admin_intro') }}</p>
    </div>
    <a class="hh-link-button" href="{{ route('moment-of-week.index') }}" target="_blank" rel="noopener">{{ __('ui.moment_week_admin_open_public') }}</a>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="hh-alert hh-alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="hh-admin-stat-grid">
    <article class="hh-card hh-card-compact"><strong>{{ $stats['active'] }}</strong><span>{{ __('ui.moment_week_status_active') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['archived'] }}</strong><span>{{ __('ui.moment_week_status_archived') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['moments'] }}</strong><span>{{ __('ui.moments') }}</span></article>
</section>

<section class="hh-card hh-card-compact hnt-admin-moment-create">
    <h2>{{ __('ui.moment_week_admin_create') }}</h2>
    <form method="post" action="{{ route('admin.moment-of-week.store') }}" class="hnt-admin-moment-form">
        @csrf
        <div class="hnt-admin-moment-form-wide">
            <label>{{ __('ui.moment_week_admin_select_moment') }}</label>
            <select name="moment_id" required>
                <option value="">{{ __('ui.moment_week_admin_select_placeholder') }}</option>
                @foreach($candidateMoments as $moment)
                    <option value="{{ $moment->id }}" @selected((int) old('moment_id') === (int) $moment->id)>
                        #{{ $moment->id }} · {{ $moment->caption ?: __('ui.moment_week_untitled') }} · {{ $moment->user?->name ?? __('ui.unknown_user') }} · {{ $moment->published_at?->format('d.m.Y') }} · {{ (int) $moment->likes_count }} {{ __('ui.likes') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>{{ __('ui.moment_week_admin_title_field') }}</label>
            <input type="text" name="title" maxlength="160" value="{{ old('title') }}" placeholder="{{ __('ui.moment_week_admin_title_placeholder') }}">
        </div>
        <div>
            <label>{{ __('ui.moment_week_admin_start') }}</label>
            <input type="date" name="week_starts_at" value="{{ old('week_starts_at', now()->startOfWeek()->toDateString()) }}">
        </div>
        <div>
            <label>{{ __('ui.moment_week_admin_end') }}</label>
            <input type="date" name="week_ends_at" value="{{ old('week_ends_at', now()->endOfWeek()->toDateString()) }}">
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <label class="hh-admin-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> {{ __('ui.moment_week_admin_make_current') }}</label>
        <div class="hnt-admin-moment-form-wide">
            <label>{{ __('ui.moment_week_admin_note') }}</label>
            <textarea name="note" rows="4" placeholder="{{ __('ui.moment_week_admin_note_placeholder') }}">{{ old('note') }}</textarea>
        </div>
        <div class="hnt-admin-moment-form-actions">
            <button class="hh-primary-button" type="submit">{{ __('ui.moment_week_admin_create_button') }}</button>
        </div>
    </form>
</section>

<section class="hnt-admin-moment-list">
    @forelse($spotlights as $spotlight)
        @php($moment = $spotlight->moment)
        <article class="hh-card hh-card-compact hnt-admin-moment-card">
            <div class="hnt-admin-moment-main">
                @if($moment)
                    <a href="{{ route('moments.show', $moment) }}" target="_blank" rel="noopener" class="hnt-admin-moment-thumb">
                        <img src="{{ $moment->coverUrl() }}" alt="{{ $spotlight->title ?: $moment->caption ?: __('ui.moment_week_title') }}">
                    </a>
                @endif
                <div>
                    <div class="hnt-admin-moment-topline">
                        <span>{{ $spotlight->statusLabel() }}</span>
                        @if($spotlight->is_active)
                            <span>{{ __('ui.moment_week_admin_current') }}</span>
                        @endif
                        <span>{{ $spotlight->dateLabel() }}</span>
                    </div>
                    <h2>{{ $spotlight->title ?: ($moment?->caption ?: __('ui.moment_week_untitled')) }}</h2>
                    <p>{{ $spotlight->note ?: \Illuminate\Support\Str::limit((string) $moment?->description, 180) }}</p>
                    <div class="hnt-admin-moment-meta">
                        <span>{{ __('ui.author') }}: {{ $moment?->user?->name ?? '—' }}</span>
                        <span>{{ (int) ($moment?->likes_count ?? 0) }} {{ __('ui.likes') }}</span>
                        <span>{{ (int) ($moment?->comments_count ?? 0) }} {{ __('ui.comments') }}</span>
                        <span>{{ (int) ($moment?->views_count ?? 0) }} {{ __('ui.views') }}</span>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('admin.moment-of-week.update', $spotlight) }}" class="hnt-admin-moment-form hnt-admin-moment-form-small">
                @csrf
                @method('put')
                <input type="hidden" name="moment_id" value="{{ $moment?->id }}">
                <div>
                    <label>{{ __('ui.moment_week_admin_title_field') }}</label>
                    <input type="text" name="title" maxlength="160" value="{{ old('title', $spotlight->title) }}">
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($spotlight->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>{{ __('ui.moment_week_admin_start') }}</label>
                    <input type="date" name="week_starts_at" value="{{ $spotlight->week_starts_at?->toDateString() }}">
                </div>
                <div>
                    <label>{{ __('ui.moment_week_admin_end') }}</label>
                    <input type="date" name="week_ends_at" value="{{ $spotlight->week_ends_at?->toDateString() }}">
                </div>
                <label class="hh-admin-check"><input type="checkbox" name="is_active" value="1" @checked($spotlight->is_active)> {{ __('ui.moment_week_admin_make_current') }}</label>
                <div class="hnt-admin-moment-form-wide">
                    <label>{{ __('ui.moment_week_admin_note') }}</label>
                    <textarea name="note" rows="3">{{ $spotlight->note }}</textarea>
                </div>
                <div class="hnt-admin-moment-actions">
                    <button class="hh-primary-button" type="submit">{{ __('ui.save') }}</button>
                    @if($moment)
                        <a class="hh-link-button" href="{{ route('moments.show', $moment) }}" target="_blank" rel="noopener">{{ __('ui.open') }}</a>
                    @endif
                </div>
            </form>

            <form method="post" action="{{ route('admin.moment-of-week.destroy', $spotlight) }}" onsubmit="return confirm('{{ __('ui.moment_week_admin_delete_confirm') }}')" class="hnt-admin-moment-delete">
                @csrf
                @method('delete')
                <button class="hh-danger-button" type="submit">{{ __('ui.delete') }}</button>
            </form>
        </article>
    @empty
        <section class="hh-card hh-card-compact"><p class="hh-muted">{{ __('ui.moment_week_admin_empty') }}</p></section>
    @endforelse
</section>

<div class="hh-pagination">{{ $spotlights->links() }}</div>
@endsection
