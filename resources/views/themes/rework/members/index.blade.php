@extends('themes.rework.layouts.app')

@section('title', __('ui.rework_members_page_title'))
@section('body_class', 'members-page')
@section('left_col_class', 'members-left-col')

@section('content')
<section class="members-head">
    <div>
        <span class="members-eyebrow">{{ __('ui.rework_members_eyebrow') }}</span>
        <h1>{{ __('ui.rework_members_title') }}</h1>
    </div>
    <a class="members-filter-btn" data-members-filter-open href="#">
        <i aria-hidden="true" class="ph ph-funnel-simple ph-icon"></i>
        {{ __('ui.rework_members_filters') }}
    </a>
</section>

<section aria-label="{{ __('ui.rework_members_filter_aria') }}" class="members-controls">
    <div aria-label="{{ __('ui.rework_members_view_aria') }}" class="members-tabs" role="tablist">
        <a class="{{ ($filters['relationship'] ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('members.index', array_merge(request()->except(['relationship', 'page']), ['relationship' => 'all'])) }}">{{ __('ui.rework_members_all') }}</a>
        <a class="{{ ($filters['relationship'] ?? '') === 'friends' ? 'active' : '' }}" href="{{ route('members.index', array_merge(request()->except(['relationship', 'page']), ['relationship' => 'friends'])) }}">{{ __('ui.friends') }}{{ $relationshipCounts['friends'] ? ' ('.$relationshipCounts['friends'].')' : '' }}</a>
    </div>
    <form aria-label="{{ __('ui.rework_members_search_aria') }}" class="members-search" method="get" action="{{ route('members.index') }}">
        @foreach(['relationship', 'platform', 'playstyle', 'region', 'language', 'lfg'] as $membersSearchHiddenKey)
            @if(filled($filters[$membersSearchHiddenKey] ?? null))
                <input type="hidden" name="{{ $membersSearchHiddenKey }}" value="{{ $filters[$membersSearchHiddenKey] }}">
            @endif
        @endforeach
        <i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i>
        <input name="q" placeholder="{{ __('ui.search') }}" type="search" value="{{ $filters['q'] ?? '' }}"/>
    </form>
</section>

<section aria-label="{{ __('ui.rework_members_title') }}" class="members-grid" data-members-stream>
    @include('themes.rework.members.partials.member-items')
</section>

@if ($hasMoreMembers && $nextMembersPageUrl)
    <div class="members-load-more-wrap" data-members-load-more-wrap>
        <button
            class="members-load-more"
            type="button"
            data-members-load-more
            data-next-url="{{ $nextMembersPageUrl }}"
            data-loading-label="{{ __('ui.rework_loading') }}"
            data-ready-label="{{ __('ui.notifications_load_more') }}"
            data-error-label="{{ __('ui.rework_try_again') }}"
        >
            <span data-members-load-more-label>{{ __('ui.notifications_load_more') }}</span>
        </button>
    </div>
@endif
@endsection

@push('rework-modals')
<div aria-hidden="true" class="modal-backdrop members-filter-backdrop" data-members-filter-modal>
    <section aria-labelledby="members-filter-title" aria-modal="true" class="members-filter-modal" role="dialog">
        <header class="members-filter-head">
            <div>
                <span>{{ __('ui.rework_members_title') }}</span>
                <h2 id="members-filter-title">{{ __('ui.rework_members_filters') }}</h2>
            </div>
            <button aria-label="{{ __('ui.close') }}" data-members-filter-close type="button">
                <i aria-hidden="true" class="ph ph-x ph-icon"></i>
            </button>
        </header>

        <form class="members-filter-form" method="get" action="{{ route('members.index') }}">
            @if(filled($filters['q'] ?? null))
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
            @endif
            @if(filled($filters['relationship'] ?? null) && ($filters['relationship'] ?? 'all') !== 'all')
                <input type="hidden" name="relationship" value="{{ $filters['relationship'] }}">
            @endif

            <div class="members-filter-grid">
                <label class="members-field">
                    <span>{{ __('ui.platform') }}</span>
                    <select name="platform">
                        <option value="">{{ __('ui.members_all_platforms') }}</option>
                        @foreach(($filterOptions['platforms'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="members-field">
                    <span>{{ __('ui.playstyle') }}</span>
                    <select name="playstyle">
                        <option value="">{{ __('ui.members_all_playstyles') }}</option>
                        @foreach(($filterOptions['playstyles'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="members-field">
                    <span>{{ __('ui.region') }}</span>
                    <select name="region">
                        <option value="">{{ __('ui.members_all_regions') }}</option>
                        @foreach(($filterOptions['regions'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(($filters['region'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="members-field">
                    <span>{{ __('ui.language') }}</span>
                    <select name="language">
                        <option value="">{{ __('ui.members_all_languages') }}</option>
                        @foreach(($filterOptions['languages'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(($filters['language'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="members-check">
                    <input name="lfg" value="1" type="checkbox" @checked(($filters['lfg'] ?? null) === '1')/>
                    <span></span>
                    <strong>{{ __('ui.members_lfg_only') }}</strong>
                </label>
            </div>

            <footer class="members-filter-footer">
                <button class="btn" type="submit">{{ __('ui.search') }}</button>
                <a class="members-reset" href="{{ route('members.index') }}">{{ __('ui.rework_filters_reset') }}</a>
            </footer>
        </form>
    </section>
</div>
@endpush

@push('rework-scripts')
<script>
(() => {
    const modal = document.querySelector('[data-members-filter-modal]');
    const closeButtons = document.querySelectorAll('[data-members-filter-close]');

    window.closeMembersFilterModal = () => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-modal-open');
    };

    const openMembersFilterModal = () => {
        if (!modal) return;
        if (typeof closeAllDropdowns === 'function') closeAllDropdowns();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-modal-open');
    };

    document.querySelectorAll('[data-members-filter-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openMembersFilterModal();
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            window.closeMembersFilterModal();
        });
    });

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) window.closeMembersFilterModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') window.closeMembersFilterModal();
    });
})();
</script>
@endpush
