@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.preview_lfg_page_title'))
@section('main_class', 'lfg-main')

@php
    use Illuminate\Support\Str;

    $filters = $filters ?? [];
    $viewer = auth()->user();
    $activeSort = (string) ($filters['sort'] ?? 'newest');
    $activePlatform = (string) ($filters['platform'] ?? '');
    $activeStatus = (string) ($filters['status'] ?? '');
    $mineActive = ! empty($filters['mine']);
    $voiceActive = ! empty($filters['voice_required']);
    $queryText = (string) ($filters['q'] ?? '');
    $managedCount = (int) (($managedLfgPosts ?? collect())->count());

    $filterUrl = function (array $params = []) {
        $query = array_merge(request()->except(['page']), $params);

        foreach ($query as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($query[$key]);
            }
        }

        return route('lfg.index', $query);
    };

    $formatNumber = fn (int $value): string => number_format($value, 0, app()->getLocale() === 'de' ? ',' : '.', app()->getLocale() === 'de' ? '.' : ',');
    $shortDateFormat = app()->getLocale() === 'de' ? 'd. M' : 'M j';

    $platformOptions = [
        '' => __('ui.lfg_filter_all'),
        'Xbox' => 'Xbox',
        'PlayStation' => 'PlayStation',
        'PC' => 'PC',
        'Crossplay' => 'Crossplay',
    ];

    $statusClass = static fn (?string $status): string => match ($status) {
        'open' => 'open',
        'full' => 'full',
        'closed' => 'closed',
        default => 'neutral',
    };
@endphp

@section('content')
    <div class="lfg-shell">
        <section class="lfg-toolbar" aria-label="{{ __('ui.preview_lfg_overview_aria') }}">
            <div class="lfg-toolbar-top">
                <nav class="lfg-main-tabs" aria-label="{{ __('ui.preview_lfg_tabs_aria') }}">
                    <a href="{{ $filterUrl(['sort' => 'newest', 'mine' => null, 'status' => null]) }}" @class(['active' => ! $mineActive && $activeSort === 'newest' && $activeStatus === ''])>
                        {{ __('ui.preview_lfg_active_lfgs') }} <span>{{ $formatNumber((int) $posts->total()) }}</span>
                    </a>
                    <a href="{{ $filterUrl(['mine' => 1, 'sort' => 'newest']) }}" @class(['active' => $mineActive])>
                        {{ __('ui.preview_lfg_my_search') }} <span>{{ $formatNumber($managedCount) }}</span>
                    </a>
                    <a href="{{ $filterUrl(['sort' => 'open_slots', 'mine' => null]) }}" @class(['active' => ! $mineActive && $activeSort === 'open_slots'])>
                        {{ __('ui.preview_lfg_free_slots') }}
                    </a>
                </nav>

                <div class="lfg-toolbar-actions">
                    <a class="lfg-sort-btn" href="{{ $filterUrl(['sort' => $activeSort === 'expiring' ? 'newest' : 'expiring']) }}">
                        {{ $activeSort === 'expiring' ? __('ui.lfg_sort_newest') : __('ui.lfg_sort_expiring') }}
                        <i class="ph ph-caret-down" aria-hidden="true"></i>
                    </a>
                    <a class="btn-create" href="{{ route('lfg.create') }}" data-hnt-lfg-create-open>{{ __('ui.lfg_create') }}</a>
                </div>
            </div>

            @if (session('status'))
                <div class="lfg-alert success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="lfg-alert warning">{{ $errors->first() }}</div>
            @endif

            <form class="lfg-search-form" method="GET" action="{{ route('lfg.index') }}">
                @foreach (['platform', 'playstyle', 'region', 'language', 'preferred_time', 'experience_level', 'status', 'voice_required', 'mine', 'sort'] as $field)
                    @if (! blank($filters[$field] ?? null))
                        <input type="hidden" name="{{ $field }}" value="{{ $filters[$field] }}">
                    @endif
                @endforeach
                <label class="lfg-search-input" for="lfg-preview-search">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <input id="lfg-preview-search" type="search" name="q" value="{{ $queryText }}" placeholder="{{ __('ui.preview_lfg_search_placeholder') }}">
                </label>
                <button class="btn-create" type="submit">{{ __('ui.search') }}</button>
            </form>

            <div class="lfg-filter-row" aria-label="{{ __('ui.preview_lfg_filters_aria') }}">
                @foreach($platformOptions as $platform => $label)
                    <a href="{{ $filterUrl(['platform' => $platform]) }}" @class(['active' => $activePlatform === $platform])>{{ $label }}</a>
                @endforeach
                <a href="{{ $filterUrl(['voice_required' => $voiceActive ? null : 1]) }}" @class(['active' => $voiceActive])>{{ __('ui.preview_lfg_voice') }}</a>
                <a href="{{ $filterUrl(['status' => $activeStatus === 'open' ? null : 'open']) }}" @class(['active' => $activeStatus === 'open'])>{{ __('ui.preview_lfg_open') }}</a>
                <a href="{{ $filterUrl(['mine' => $mineActive ? null : 1]) }}" @class(['active' => $mineActive])>{{ __('ui.preview_lfg_mine') }}</a>
            </div>
        </section>

        @if ($posts->isEmpty())
            <section class="lfg-empty-card">
                <span class="lfg-empty-icon" aria-hidden="true">☊</span>
                <h1>{{ __('ui.preview_lfg_empty_title') }}</h1>
                <p>{{ __('ui.preview_lfg_empty_text') }}</p>
                <div class="lfg-empty-actions">
                    <a class="btn-following" href="{{ route('lfg.index') }}">{{ __('ui.preview_lfg_reset_filters') }}</a>
                    <a class="btn-create" href="{{ route('lfg.create') }}" data-hnt-lfg-create-open>{{ __('ui.lfg_create') }}</a>
                </div>
            </section>
        @else
            <section class="lfg-list-grid" id="hntLfgList" aria-label="{{ __('ui.preview_lfg_list_aria') }}">
                @foreach ($posts as $post)
                    @php
                        $author = $post->user;
                        $profile = $author?->profile;
                        $slotsOpen = $post->slotsOpen();
                        $tags = array_values(array_filter(array_merge(
                            [
                                $post->slots_total ? __('ui.preview_lfg_team_size_'.((int) $post->slots_total === 2 ? 'duo' : 'trio')) : null,
                                $post->voice_required ? __('ui.preview_lfg_voice') : null,
                            ],
                            array_slice($post->displayTags(), 0, 5)
                        )));
                        $platform = $post->localizedOptionLabel('platform', $post->platform) ?: $post->platform ?: __('ui.preview_lfg_platform_open');
                        $region = $post->localizedOptionLabel('region', $post->region) ?: $post->region ?: __('ui.preview_lfg_region_open');
                        $playstyle = $post->localizedOptionLabel('playstyle', $post->playstyle) ?: $post->playstyle ?: __('ui.preview_lfg_playstyle_open');
                        $isOwner = $viewer && $post->isOwner($viewer);
                        $buttonLabel = $isOwner ? __('ui.preview_lfg_manage') : ($post->isOpen() ? __('ui.preview_lfg_request') : __('ui.preview_lfg_view'));
                        $buttonClass = $post->isOpen() && ! $isOwner ? '' : 'muted';
                        $postHashtags = \App\Support\Hashtag::labelsForText($post->title . ' ' . $post->body, 4);
                    @endphp
                    <article @class(['lfg-card', 'featured' => $loop->first && ! $mineActive]) data-lfg-card>
                        <div class="lfg-user">
                            <a href="{{ route('profile.public', $author) }}" class="lfg-avatar-link" aria-label="{{ __('ui.preview_lfg_profile_aria', ['name' => $author?->name]) }}">
                                <img class="avatar lfg-avatar" src="{{ $author?->avatarUrl() }}" alt="{{ $author?->name }}">
                            </a>
                            <div class="lfg-user-copy">
                                <a href="{{ route('lfg.show', $post) }}"><h2>{{ $post->title }}</h2></a>
                                <p>{{ $author?->name }} · {{ '@'.$author?->username }} · {{ $post->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>

                        <a class="lfg-join-btn {{ $buttonClass }}" href="{{ route('lfg.show', $post) }}">{{ $buttonLabel }}</a>

                        <div class="lfg-card-body">
                            <p>{{ Str::limit(strip_tags((string) $post->body), 150) }}</p>
                            <div class="lfg-card-stats">
                                <span class="lfg-status-pill {{ $statusClass($post->status) }}">{{ $post->statusLabel() }}</span>
                                <span>{{ __('ui.preview_lfg_slots_open_short', ['count' => $slotsOpen]) }}</span>
                                <span>{{ __('ui.preview_lfg_pending_short', ['count' => (int) ($post->pending_count ?? 0)]) }}</span>
                                @if($post->expires_at)
                                    <span>{{ __('ui.preview_lfg_until_short', ['date' => $post->expires_at->translatedFormat($shortDateFormat)]) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="lfg-tags">
                            <span>{{ $platform }}</span>
                            <span>{{ $region }}</span>
                            <span>{{ $playstyle }}</span>
                            @foreach(array_slice($tags, 0, 5) as $tag)
                                <span>{{ $tag }}</span>
                            @endforeach
                            @foreach($postHashtags as $hashtag)
                                <a href="{{ $hashtag['url'] }}">{{ $hashtag['label'] }}</a>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </section>

            @if($posts->hasMorePages())
                <div class="lfg-load-more-wrap" id="hntLfgLoadMoreWrap">
                    <a class="btn-create lfg-load-more-btn" href="{{ $posts->nextPageUrl() }}" data-lfg-load-more>{{ __('ui.preview_lfg_load_more') }}</a>
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const list = document.getElementById('hntLfgList');
    const wrap = document.getElementById('hntLfgLoadMoreWrap');
    const button = document.querySelector('[data-lfg-load-more]');

    if (!list || !wrap || !button) {
        return;
    }

    button.addEventListener('click', async (event) => {
        event.preventDefault();

        const url = button.getAttribute('href');
        if (!url) {
            return;
        }

        button.classList.add('is-loading');
        button.textContent = @json(__('ui.preview_lfg_loading'));

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('load failed');
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const incomingCards = doc.querySelectorAll('[data-lfg-card]');
            const incomingButton = doc.querySelector('[data-lfg-load-more]');

            incomingCards.forEach((card) => list.appendChild(document.importNode(card, true)));

            if (incomingButton) {
                button.setAttribute('href', incomingButton.getAttribute('href'));
                button.classList.remove('is-loading');
                button.textContent = @json(__('ui.preview_lfg_load_more'));
            } else {
                wrap.remove();
            }
        } catch (error) {
            window.location.href = url;
        }
    });
})();
</script>
@endpush
