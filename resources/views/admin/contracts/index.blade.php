@extends('admin.layouts.app')

@section('title', __('ui.contract_admin_meta_title'))
@section('admin_heading', __('ui.contract_admin_title'))

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>{{ __('ui.contract_admin_title') }}</h1>
        <p>{{ __('ui.contract_admin_intro') }}</p>
    </div>
    <a class="hh-link-button" href="{{ route('contracts.index') }}" target="_blank" rel="noopener">{{ __('ui.contract_admin_open_public') }}</a>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-admin-stat-grid">
    <article class="hh-card hh-card-compact"><strong>{{ $stats['active'] }}</strong><span>{{ __('ui.contract_status_active') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['planned'] }}</strong><span>{{ __('ui.contract_status_planned') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['expired'] }}</strong><span>{{ __('ui.contract_status_expired') }}</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['inactive'] }}</strong><span>{{ __('ui.contract_status_inactive') }}</span></article>
</section>

<section class="hh-card hh-card-compact hh-admin-contract-form-card">
    <h2>{{ __('ui.contract_admin_create') }}</h2>
    <form class="hh-admin-contract-form" method="post" action="{{ route('admin.contracts.store') }}">
        @csrf
        <div>
            <label>{{ __('ui.contract_admin_name') }}</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="140" placeholder="{{ __('ui.contract_admin_name_placeholder') }}">
        </div>
        <div>
            <label>Slug</label>
            <input type="text" name="slug" value="{{ old('slug') }}" maxlength="100" placeholder="optional">
        </div>
        <div class="hh-admin-contract-form-wide">
            <label>{{ __('ui.contract_admin_description') }}</label>
            <textarea name="description" rows="3" maxlength="2000" placeholder="{{ __('ui.contract_admin_description_placeholder') }}">{{ old('description') }}</textarea>
        </div>
        <div>
            <label>{{ __('ui.contract_admin_action') }}</label>
            <select name="action" required>
                @foreach($actionOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('action') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>{{ __('ui.contract_admin_target') }}</label>
            <input type="number" name="target_count" value="{{ old('target_count', 1) }}" min="1" max="1000" required>
        </div>
        <div>
            <label>XP</label>
            <input type="number" name="xp_reward" value="{{ old('xp_reward', 25) }}" min="0" max="100000">
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
            <label>{{ __('ui.contract_admin_starts') }}</label>
            <input type="datetime-local" name="contract_starts_at" value="{{ old('contract_starts_at') }}">
        </div>
        <div>
            <label>{{ __('ui.contract_admin_ends') }}</label>
            <input type="datetime-local" name="contract_ends_at" value="{{ old('contract_ends_at') }}">
        </div>
        <div>
            <label>{{ __('ui.contract_admin_sort') }}</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="100000">
        </div>
        <label class="hh-admin-check"><input type="checkbox" name="is_active" value="1" checked> {{ __('ui.contract_admin_active') }}</label>
        <label class="hh-admin-check"><input type="checkbox" name="notify_on_completion" value="1" checked> {{ __('ui.contract_admin_notify') }}</label>
        <div class="hh-admin-contract-form-actions">
            <button class="hh-primary-button" type="submit">{{ __('ui.contract_admin_create_button') }}</button>
        </div>
    </form>
</section>

<section class="hh-admin-report-list">
    @forelse($contracts as $contract)
        <article class="hh-card hh-card-compact hh-admin-contract-card">
            <div class="hh-admin-contract-head">
                <div>
                    <p class="hh-kicker">{{ $contract->contractStatusLabel() }} · {{ $contract->actionLabel() }}</p>
                    <h2>{{ $contract->name }}</h2>
                    <p>{{ $contract->description ?: __('ui.contract_admin_no_description') }}</p>
                    <div class="hh-admin-report-context">
                        <span>{{ $contract->target_count }}×</span>
                        <span>{{ $contract->xp_reward }} XP</span>
                        <span>{{ $contract->completed_count }}/{{ $contract->progress_count }} {{ __('ui.contract_admin_completed_short') }}</span>
                        @if($contract->contract_starts_at)<span>{{ __('ui.contract_admin_starts') }}: {{ $contract->contract_starts_at->format('d.m.Y H:i') }}</span>@endif
                        @if($contract->contract_ends_at)<span>{{ __('ui.contract_admin_ends') }}: {{ $contract->contract_ends_at->format('d.m.Y H:i') }}</span>@endif
                    </div>
                </div>
                <form method="post" action="{{ route('admin.contracts.destroy', $contract) }}" onsubmit="return confirm('{{ __('ui.contract_admin_delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button class="hh-secondary-button" type="submit">{{ __('ui.delete') }}</button>
                </form>
            </div>

            <details class="hh-admin-contract-details">
                <summary>{{ __('ui.contract_admin_edit') }}</summary>
                <form class="hh-admin-contract-form" method="post" action="{{ route('admin.contracts.update', $contract) }}">
                    @csrf
                    @method('PUT')
                    <div>
                        <label>{{ __('ui.contract_admin_name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $contract->name) }}" required maxlength="140">
                    </div>
                    <div>
                        <label>Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $contract->slug) }}" maxlength="100">
                    </div>
                    <div class="hh-admin-contract-form-wide">
                        <label>{{ __('ui.contract_admin_description') }}</label>
                        <textarea name="description" rows="3" maxlength="2000">{{ old('description', $contract->description) }}</textarea>
                    </div>
                    <div>
                        <label>{{ __('ui.contract_admin_action') }}</label>
                        <select name="action" required>
                            @foreach($actionOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('action', $contract->action) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ __('ui.contract_admin_target') }}</label>
                        <input type="number" name="target_count" value="{{ old('target_count', $contract->target_count) }}" min="1" max="1000" required>
                    </div>
                    <div>
                        <label>XP</label>
                        <input type="number" name="xp_reward" value="{{ old('xp_reward', $contract->xp_reward) }}" min="0" max="100000">
                    </div>
                    <div>
                        <label>Badge</label>
                        <select name="badge_slug">
                            <option value="">—</option>
                            @foreach($badges as $badge)
                                <option value="{{ $badge->slug }}" @selected(old('badge_slug', $contract->badge_slug) === $badge->slug)>{{ $badge->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ __('ui.contract_admin_starts') }}</label>
                        <input type="datetime-local" name="contract_starts_at" value="{{ old('contract_starts_at', $contract->contract_starts_at?->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div>
                        <label>{{ __('ui.contract_admin_ends') }}</label>
                        <input type="datetime-local" name="contract_ends_at" value="{{ old('contract_ends_at', $contract->contract_ends_at?->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div>
                        <label>{{ __('ui.contract_admin_sort') }}</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $contract->sort_order) }}" min="0" max="100000">
                    </div>
                    <label class="hh-admin-check"><input type="checkbox" name="is_active" value="1" @checked($contract->is_active)> {{ __('ui.contract_admin_active') }}</label>
                    <label class="hh-admin-check"><input type="checkbox" name="notify_on_completion" value="1" @checked($contract->notify_on_completion)> {{ __('ui.contract_admin_notify') }}</label>
                    <div class="hh-admin-contract-form-actions">
                        <button class="hh-primary-button" type="submit">{{ __('ui.save') }}</button>
                    </div>
                </form>
            </details>
        </article>
    @empty
        <section class="hh-card hh-card-compact"><p class="hh-muted">{{ __('ui.contract_admin_empty') }}</p></section>
    @endforelse
</section>

<div class="hh-pagination">{{ $contracts->links() }}</div>
@endsection
