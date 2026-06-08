@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.members_title'))
@section('main_class', 'members-main')

@section('content')
@php
    $viewer = auth()->user();
    $relationship = $filters['relationship'] ?? 'all';
    $makeFilterUrl = static function (array $overrides = []) {
        return route('members.index', array_merge(request()->except(['page']), $overrides));
    };
@endphp

<div class="members-shell">
    <header class="members-hero">
        <span class="members-kicker">{{ __('ui.preview_members_kicker') }}</span>
        <div class="members-hero-row">
            <div>
                <h1>{{ __('ui.preview_members_title') }}</h1>
                <p>{{ __('ui.preview_members_text') }}</p>
            </div>
            <div class="members-hero-stats" aria-label="{{ __('ui.preview_members_stats_aria') }}">
                <div>
                    <strong>{{ number_format($members->total(), 0, ',', '.') }}</strong>
                    <span>{{ __('ui.members') }}</span>
                </div>
                <div>
                    <strong>{{ (int) ($relationshipCounts['friends'] ?? 0) }}</strong>
                    <span>{{ __('ui.friends') }}</span>
                </div>
                <div>
                    <strong>{{ (int) ($relationshipCounts['pending'] ?? 0) }}</strong>
                    <span>{{ __('ui.members_requests') }}</span>
                </div>
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="hnt-preview-alert success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="hnt-preview-alert danger">
            <strong>{{ __('ui.please_check') }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="members-toolbar" aria-label="{{ __('ui.preview_members_filters') }}">
        <div class="members-toolbar-top">
            <nav class="members-tabs" aria-label="{{ __('ui.members') }}">
                <a @class(['active' => $relationship === 'all']) href="{{ $makeFilterUrl(['relationship' => 'all']) }}">
                    {{ __('ui.members_all_players') }} <span>{{ $members->total() }}</span>
                </a>
                <a @class(['active' => $relationship === 'friends']) href="{{ $makeFilterUrl(['relationship' => 'friends']) }}">
                    {{ __('ui.friends') }} <span>{{ (int) ($relationshipCounts['friends'] ?? 0) }}</span>
                </a>
                <a @class(['active' => $relationship === 'pending']) href="{{ $makeFilterUrl(['relationship' => 'pending']) }}">
                    {{ __('ui.members_requests') }} <span>{{ (int) ($relationshipCounts['pending'] ?? 0) }}</span>
                </a>
            </nav>
        </div>

        <form class="members-filter-form" method="GET" action="{{ route('members.index') }}">
            <input type="hidden" name="relationship" value="{{ $relationship }}">

            <label class="members-search-field" for="members-search">
                <span>{{ __('ui.members_search_label') }}</span>
                <input id="members-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.members_search_label') }}">
            </label>

            <label class="members-select-field" for="members-platform">
                <span>{{ __('ui.platform') }}</span>
                <select id="members-platform" name="platform" onchange="this.form.submit()">
                    <option value="">{{ __('ui.members_platform_all') }}</option>
                    @foreach ($filterOptions['platforms'] as $option)
                        <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>

            <label class="members-select-field" for="members-playstyle">
                <span>{{ __('ui.playstyle') }}</span>
                <select id="members-playstyle" name="playstyle" onchange="this.form.submit()">
                    <option value="">{{ __('ui.members_all_playstyles') }}</option>
                    @foreach ($filterOptions['playstyles'] as $option)
                        <option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>

            <label class="members-select-field" for="members-region">
                <span>{{ __('ui.region') }}</span>
                <select id="members-region" name="region" onchange="this.form.submit()">
                    <option value="">{{ __('ui.preview_members_all_regions') }}</option>
                    @foreach ($filterOptions['regions'] as $option)
                        <option value="{{ $option }}" @selected(($filters['region'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>

            <label class="members-select-field" for="members-language">
                <span>{{ __('ui.language') }}</span>
                <select id="members-language" name="language" onchange="this.form.submit()">
                    <option value="">{{ __('ui.preview_members_all_languages') }}</option>
                    @foreach ($filterOptions['languages'] as $option)
                        <option value="{{ $option }}" @selected(($filters['language'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>

            <label class="hnt-lfg-check members-lfg-check">
                <input type="checkbox" name="lfg" value="1" @checked(($filters['lfg'] ?? '') === '1') onchange="this.form.submit()">
                <span>{{ __('ui.members_lfg_only') }}</span>
            </label>

            <div class="members-filter-actions">
                <button class="btn-create" type="submit">{{ __('ui.search') }}</button>
                <a class="members-reset-link" href="{{ route('members.index') }}">{{ __('ui.preview_members_reset') }}</a>
            </div>
        </form>
    </section>

    @if ($members->isEmpty())
        <section class="members-empty">
            <span>{{ __('ui.preview_members_empty_kicker') }}</span>
            <h2>{{ __('ui.members_empty_title') }}</h2>
            <p>{{ __('ui.members_empty_text') }}</p>
        </section>
    @else
        <section class="members-grid" aria-label="{{ __('ui.members') }}">
            @foreach ($members as $member)
                @php
                    $profile = $member->profile;
                    $friendship = $friendshipMap->get($member->id);
                    $isOwnCard = (int) auth()->id() === (int) $member->id;
                    $memberUrl = $isOwnCard ? route('profile.show') : route('profile.public', $member);
                    $memberFriendCount = $friendCounts[$member->id] ?? 0;
                    $memberBadges = $member->badges->take(4);
                    $remainingBadges = max(0, (int) ($member->badges_count ?? 0) - $memberBadges->count());
                    $headline = $profile?->headline ?: __('ui.members_no_headline');
                @endphp

                <article class="member-card">
                    <div class="member-card-top">
                        <a class="member-person" href="{{ $memberUrl }}">
                            <span class="avatar member-avatar hnt-avatar-shell">
                                <img src="{{ $member->avatarUrl() }}" alt="{{ $member->name }}">
                            </span>
                            <span class="member-copy">
                                <span class="member-name-row">
                                    <h2>{{ $member->name }}</h2>
                                </span>
                                <p>{{ '@'.$member->username }}</p>
                                <em class="member-level-pill">{{ __('ui.level') }} {{ $member->level ?? 1 }}</em>
                            </span>
                        </a>

                        <div class="member-badges" aria-label="{{ __('ui.preview_members_badges') }}">
                            @forelse ($memberBadges as $badge)
                                <span title="{{ $badge->name }}">
                                    @if($badge->iconUrl())
                                        <img src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                                    @else
                                        {{ $badge->icon ?: '◆' }}
                                    @endif
                                </span>
                            @empty
                                <span title="{{ __('ui.members_no_badges') }}">◆</span>
                            @endforelse

                            @if ($remainingBadges > 0)
                                <a href="{{ $memberUrl }}#profile-badges">+{{ $remainingBadges }}</a>
                            @endif
                        </div>
                    </div>

                    <p class="member-headline">{{ $headline }}</p>

                    <div class="member-tags">
                        <span>{{ $profile?->platform ?: __('ui.members_platform_open') }}</span>
                        <span>{{ $profile?->playstyle ?: __('ui.members_playstyle_open') }}</span>
                        <span>{{ $profile?->region ?: __('ui.members_region_open') }}</span>
                        <span @class(['is-active' => $profile?->is_lfg_available])>{{ $profile?->is_lfg_available ? __('ui.members_lfg_open') : __('ui.members_lfg_not_marked') }}</span>
                    </div>

                    <div class="member-card-bottom">
                        <div class="member-stats-row">
                            <span><strong>{{ (int) ($member->visible_feed_posts_count ?? 0) }}</strong>{{ __('ui.profile_posts_stat') }}</span>
                            <span><strong>{{ (int) $memberFriendCount }}</strong>{{ __('ui.friends') }}</span>
                        </div>

                        <div class="member-actions">
                            @if ($isOwnCard)
                                <a class="members-secondary-action" href="{{ route('profile.show') }}">{{ __('ui.members_my_profile') }}</a>
                                <a class="btn-create" href="{{ route('profile.edit') }}">{{ __('ui.members_edit') }}</a>
                            @elseif (! $friendship || $friendship->isDeclined())
                                <form method="post" action="{{ route('friends.store', $member) }}">
                                    @csrf
                                    <button class="members-secondary-action" type="submit">{{ __('ui.profile_add_friend') }}</button>
                                </form>
                                <a class="btn-create" href="{{ route('messages.with-user', $member) }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ route('messages.with-user', $member) }}">{{ __('ui.profile_message_singular') }}</a>
                            @elseif ($friendship->isPending() && $friendship->isRequester(auth()->user()))
                                <form method="post" action="{{ route('friends.destroy', $friendship) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="members-secondary-action" type="submit">{{ __('ui.members_friend_request_sent') }}</button>
                                </form>
                                <a class="btn-create" href="{{ route('messages.with-user', $member) }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ route('messages.with-user', $member) }}">{{ __('ui.profile_message_singular') }}</a>
                            @elseif ($friendship->isPending() && $friendship->isRecipient(auth()->user()))
                                <form method="post" action="{{ route('friends.accept', $friendship) }}">
                                    @csrf
                                    <button class="members-secondary-action" type="submit">{{ __('ui.profile_accept') }}</button>
                                </form>
                                <form method="post" action="{{ route('friends.decline', $friendship) }}">
                                    @csrf
                                    <button class="btn-create" type="submit">{{ __('ui.profile_decline') }}</button>
                                </form>
                            @elseif ($friendship->isAccepted())
                                <form method="post" action="{{ route('friends.destroy', $friendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_remove_confirm') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="members-secondary-action" type="submit">{{ __('ui.members_friends_active') }}</button>
                                </form>
                                <a class="btn-create" href="{{ route('messages.with-user', $member) }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ route('messages.with-user', $member) }}">{{ __('ui.profile_message_singular') }}</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        @if ($members->hasPages())
            <nav class="members-pager" aria-label="{{ __('ui.members_pages_aria') }}">
                @if ($members->onFirstPage())
                    <span>{{ __('ui.pagination_previous') }}</span>
                @else
                    <a href="{{ $members->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
                @endif

                <strong>{{ $members->currentPage() }}</strong>

                @if ($members->hasMorePages())
                    <a href="{{ $members->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
                @else
                    <span>{{ __('ui.pagination_next') }}</span>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
