@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.cups').' · HNT Preview')
@section('main_class', 'cups-main')

@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $statusFilter = (string) ($filters['status'] ?? request('status', ''));
    $platformFilter = (string) ($filters['platform'] ?? request('platform', ''));
    $mineFilter = (bool) ($filters['mine'] ?? request()->boolean('mine'));
    $statusOptions = [
        '' => __('ui.cups'),
        'planned' => __('ui.cup_status_planned'),
        'active' => __('ui.cup_status_active'),
        'finished' => __('ui.cup_status_finished'),
    ];
    $platformOptions = [
        '' => __('ui.cup_all_platforms'),
        'PC' => 'PC',
        'PlayStation' => 'PlayStation',
        'Xbox' => 'Xbox',
        'Konsole' => 'Konsole',
    ];
    $queryWithoutPage = request()->except('page');
    $cardClasses = ['quick-cup', 'bayou-cup', 'summer-cup', 'loadout-cup', 'bounty-cup', 'console-cup', 'bloodline-cup', 'hall-cup'];
    $formatDateParts = static function ($date): array {
        if (! $date) {
            return ['day' => '—', 'month' => 'TBA'];
        }

        return ['day' => $date->format('d'), 'month' => Str::upper($date->translatedFormat('M'))];
    };
@endphp

@section('content')
    <div class="cups-shell">
        <header class="cups-toolbar">
            <div class="cups-toolbar-top">
                <nav class="cups-main-tabs" aria-label="{{ __('ui.preview_cups_status_aria') }}">
                    @foreach($statusOptions as $status => $label)
                        <a href="{{ route('cups.index', array_filter(array_merge($queryWithoutPage, ['status' => $status]), static fn($value) => $value !== '' && $value !== false && $value !== null)) }}" @class(['active' => $statusFilter === $status])>
                            @if($status === '')
                                <i class="ph ph-trophy" aria-hidden="true"></i>
                            @endif
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="cups-toolbar-actions">
                    @if($viewer?->isAdmin())
                        <a class="btn-create" href="{{ route('cups.create') }}">{{ __('ui.preview_cups_create') }}</a>
                    @endif
                    <a class="cups-sort-btn" href="{{ route('hall-of-fame.index') }}">
                        <i class="ph ph-star" aria-hidden="true"></i>
                        {{ __('ui.preview_cups_hof') }}
                    </a>
                </div>
            </div>

            <div class="cups-filter-row" aria-label="{{ __('ui.preview_cups_platforms_aria') }}">
                @foreach($platformOptions as $platform => $label)
                    <a href="{{ route('cups.index', array_filter(array_merge($queryWithoutPage, ['platform' => $platform]), static fn($value) => $value !== '' && $value !== false && $value !== null)) }}" @class(['active' => $platformFilter === $platform])>{{ $label }}</a>
                @endforeach
                @auth
                    <a href="{{ route('cups.index', array_filter(array_merge($queryWithoutPage, ['mine' => $mineFilter ? null : 1]), static fn($value) => $value !== '' && $value !== false && $value !== null)) }}" @class(['active' => $mineFilter])>{{ __('ui.preview_cups_mine') }}</a>
                @endauth
            </div>
        </header>

        <section class="cups-grid" aria-label="{{ __('ui.preview_cups_grid_aria') }}">
            @forelse($cups as $cup)
                @php
                    $dateSource = $cup->starts_at ?: $cup->registration_closes_at ?: $cup->created_at;
                    $dateParts = $formatDateParts($dateSource);
                    $coverUrl = $cup->coverUrl();
                    $statusClass = match ($cup->status) {
                        'active' => 'live',
                        'finished', 'archived' => 'ended',
                        default => '',
                    };
                    $mediaClass = Str::contains($cup->slug, 'quick') ? 'quick-cup' : $cardClasses[$loop->index % count($cardClasses)];
                    $participantLimit = $cup->participantLimit();
                    $participantLine = $participantLimit
                        ? $cup->active_teams_count.' / '.$participantLimit.' '.($cup->isSoloLeaderboard() ? __('ui.cup_participants') : __('ui.teams'))
                        : $cup->active_teams_count.' '.($cup->isSoloLeaderboard() ? __('ui.cup_participants') : __('ui.teams'));
                @endphp
                <a class="cup-list-card" href="{{ route('cups.show', $cup) }}">
                    <div class="cup-card-media {{ $mediaClass }}" style="background-image: linear-gradient(180deg, transparent, rgba(10, 11, 14, 0.74)), url('{{ $coverUrl }}');">
                        <span class="cup-card-status {{ $statusClass }}">{{ $cup->statusLabel() }}</span>
                        <span class="cup-card-date"><strong>{{ $dateParts['day'] }}</strong><small>{{ $dateParts['month'] }}</small></span>
                    </div>
                    <h2>{{ $cup->title }}</h2>
                    <p>{{ Str::limit($cup->displaySummary(), 118) }}</p>
                    <p>{{ $participantLine }} · {{ $cup->platform ?: __('ui.cup_all_platforms') }}</p>
                </a>
            @empty
                <article class="cup-panel" style="grid-column: 1 / -1;">
                    <h2>{{ __('ui.preview_cups_empty_title') }}</h2>
                    <p>{{ __('ui.preview_cups_empty_text') }}</p>
                    @if($viewer?->isAdmin())
                        <a class="btn-create" href="{{ route('cups.create') }}">{{ __('ui.preview_cups_create_first') }}</a>
                    @endif
                </article>
            @endforelse
        </section>

        @if($cups->hasPages())
            <div class="members-load-wrap">
                @if($cups->previousPageUrl())
                    <a class="btn-following" href="{{ $cups->previousPageUrl() }}">{{ __('ui.preview_pagination_back') }}</a>
                @endif
                @if($cups->nextPageUrl())
                    <a class="btn-create" href="{{ $cups->nextPageUrl() }}">{{ __('ui.preview_pagination_load_more') }}</a>
                @endif
            </div>
        @endif
    </div>
@endsection
