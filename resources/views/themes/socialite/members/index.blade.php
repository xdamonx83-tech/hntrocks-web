@extends('themes.socialite.layouts.app')

@section('title', __('ui.members_title'))

@section('content')
@php
    $viewer = auth()->user();
    $currentRelationship = $filters['relationship'] ?? 'all';
    $filterUrl = function (array $overrides = []) {
        return route('members.index', array_filter(array_merge(request()->except('page'), $overrides), fn ($value) => $value !== null && $value !== ''));
    };
    $membersCollection = $members->getCollection();
    $featuredMembers = $membersCollection->filter(fn ($member) => (bool) ($member->profile?->is_lfg_available))->take(8);
    if ($featuredMembers->isEmpty()) {
        $featuredMembers = $membersCollection->take(8);
    }
    $lfgWidgetMembers = $membersCollection->filter(fn ($member) => (bool) ($member->profile?->is_lfg_available))->take(4);
    $recentWidgetMembers = $membersCollection->sortByDesc('created_at')->take(4);
    $topWidgetMembers = $membersCollection->sortByDesc(fn ($member) => (int) ($member->badges_count ?? 0) + (int) ($member->visible_feed_posts_count ?? 0))->take(4);
@endphp

<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-oversized">
    <div class="flex-1">
        <div class="max-w-[680px] w-full mx-auto">
            <div class="page-heading">
                <h1 class="page-title">{{ __('ui.members') }}</h1>

                <nav class="nav__underline">
                    <ul class="group">
                        <li class="{{ $currentRelationship === 'all' ? 'uk-active' : '' }}"><a href="{{ $filterUrl(['relationship' => 'all']) }}">{{ __('ui.members_all_players') }}</a></li>
                        <li class="{{ $currentRelationship === 'friends' ? 'uk-active' : '' }}"><a href="{{ $filterUrl(['relationship' => 'friends']) }}">{{ __('ui.friends') }}</a></li>
                        <li class="{{ $currentRelationship === 'pending' ? 'uk-active' : '' }}"><a href="{{ $filterUrl(['relationship' => 'pending']) }}">{{ __('ui.members_requests') }} {{ $relationshipCounts['pending'] ? '(' . $relationshipCounts['pending'] . ')' : '' }}</a></li>
                    </ul>
                </nav>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-500/10 dark:text-green-200">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-200">
                    <div>{{ __('ui.please_check') }}</div>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET" action="{{ route('members.index') }}" class="mb-6 grid gap-3 sm:grid-cols-2">
                <input type="hidden" name="relationship" value="{{ $currentRelationship }}">
                <div class="sm:col-span-2 relative">
                    <ion-icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-500"></ion-icon>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.members_search_label') }}" class="w-full !pl-11 !pr-4 !h-12 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5">
                </div>
                <select name="platform" onchange="this.form.submit()" class="w-full !h-11 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5">
                    <option value="">{{ __('ui.members_platform_all') }}</option>
                    @foreach ($filterOptions['platforms'] as $option)
                        <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="playstyle" onchange="this.form.submit()" class="w-full !h-11 rounded-xl bg-secondery !border-0 !text-sm dark:!bg-white/5">
                    <option value="">{{ __('ui.members_all_playstyles') }}</option>
                    @foreach ($filterOptions['playstyles'] as $option)
                        <option value="{{ $option }}" @selected(($filters['playstyle'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3 text-sm">
                    <label class="inline-flex items-center gap-2 font-medium text-gray-700 dark:text-white/80">
                        <input type="checkbox" name="lfg" value="1" @checked(($filters['lfg'] ?? null) === '1') onchange="this.form.submit()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ __('ui.members_lfg_only') }}</span>
                    </label>
                    <button type="submit" class="button bg-primary text-white !w-auto px-5">{{ __('ui.search') }}</button>
                </div>
            </form>

            @if ($featuredMembers->isNotEmpty())
                <div tabindex="-1" uk-slider="finite:true">
                    <div class="uk-slider-container pb-1">
                        <ul class="uk-slider-items grid-small">
                            @foreach ($featuredMembers as $featuredMember)
                                @php
                                    $featuredProfile = $featuredMember->profile;
                                    $isOwnFeatured = (int) $featuredMember->id === (int) $viewer->id;
                                    $featuredUrl = $isOwnFeatured ? route('profile.show') : ($featuredMember->username ? route('profile.public', $featuredMember) : route('members.index'));
                                @endphp
                                <li class="lg:w-1/4 sm:w-1/3 w-1/2">
                                    <div class="card uk-transition-toggle">
                                        <a href="{{ $featuredUrl }}">
                                            <div class="card-media sm:aspect-[2/1.9] h-40">
                                                <img src="{{ $featuredMember->avatarUrl() }}" alt="{{ $featuredMember->name }}">
                                                <div class="card-overly"></div>
                                            </div>
                                        </a>
                                        <div class="card-body p-3 w-full z-10 absolute bg-gradient-to-t bottom-0 from-black/60">
                                            <p class="card-text text-xs text-white/80">{{ $featuredProfile?->platform ?: __('ui.members_platform_open') }}</p>
                                            <a href="{{ $featuredUrl }}">
                                                <h4 class="card-title text-sm mt-0.5 !text-white">{{ $featuredMember->name }}</h4>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <a class="nav-prev" href="#" uk-slider-item="previous"><ion-icon name="chevron-back" class="text-2xl"></ion-icon></a>
                    <a class="nav-next" href="#" uk-slider-item="next"><ion-icon name="chevron-forward" class="text-2xl"></ion-icon></a>
                </div>
            @endif

            <div class="mt-10">
                @if ($members->isEmpty())
                    <div class="card p-8 text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                            <ion-icon name="people-outline" class="text-3xl text-gray-500"></ion-icon>
                        </div>
                        <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.members_empty_title') }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.members_empty_text') }}</p>
                    </div>
                @else
                    <div class="grid sm:grid-cols-3 grid-cols-2 gap-3" uk-scrollspy="target: > div; cls: uk-animation-scale-up; delay: 100 ;repeat: true">
                        @foreach ($members as $member)
                            @php
                                $profile = $member->profile;
                                $isOwnCard = (int) $member->id === (int) $viewer->id;
                                $memberUrl = $isOwnCard ? route('profile.show') : ($member->username ? route('profile.public', $member) : route('members.index'));
                                $friendship = $friendshipMap->get((int) $member->id);
                                $memberFriendCount = (int) ($friendCounts[(int) $member->id] ?? 0);
                                $memberMeta = collect([$profile?->platform, $profile?->playstyle])->filter()->implode(' · ');
                                $memberMeta = $memberMeta !== '' ? $memberMeta : __('ui.members_no_headline');
                            @endphp
                            <div class="card">
                                <a href="{{ $memberUrl }}">
                                    <div class="card-media sm:h-24 h-16">
                                        <img src="{{ $member->coverUrl() }}" alt="{{ $member->name }}">
                                        <div class="card-overly"></div>
                                    </div>
                                </a>
                                <div class="card-body relative z-10">
                                    <a href="{{ $memberUrl }}">
                                        <img src="{{ $member->avatarUrl() }}" alt="{{ $member->name }}" class="w-10 h-10 object-cover rounded-full sm:mb-2 mb-1 shadow -mt-8 relative border-2 border-white dark:border-dark2">
                                    </a>
                                    <a href="{{ $memberUrl }}"><h4 class="card-title truncate">{{ $member->name }}</h4></a>
                                    <p class="card-text truncate">{{ '@' . $member->username }}</p>
                                    <p class="card-text truncate">{{ $memberMeta }}</p>

                                    <div class="mt-3 flex items-center justify-between gap-2 text-xs text-gray-500 dark:text-white/70">
                                        <span>{{ $memberFriendCount }} {{ __('ui.friends') }}</span>
                                        <span>{{ (int) ($member->active_teams_count ?? 0) }} {{ __('ui.profile_teams_title') }}</span>
                                    </div>

                                    <div class="mt-3 flex gap-2">
                                        @if ($isOwnCard)
                                            <a href="{{ route('profile.edit') }}" class="button bg-primary text-white flex-1">{{ __('ui.members_edit') }}</a>
                                            <a href="{{ $memberUrl }}" class="button bg-secondery !w-auto dark:bg-white/10">{{ __('ui.view_profile') }}</a>
                                        @elseif (! $friendship || $friendship->isDeclined())
                                            <form method="POST" action="{{ route('friends.store', $member) }}" class="flex-1">
                                                @csrf
                                                <button type="submit" class="button bg-primary text-white w-full">{{ __('ui.profile_add_friend') }}</button>
                                            </form>
                                            <a href="{{ route('messages.with-user', $member) }}" class="button bg-secondery !w-auto dark:bg-white/10"><ion-icon name="chatbubble-ellipses-outline" class="text-xl"></ion-icon></a>
                                        @elseif ($friendship->isPending() && $friendship->isRequester($viewer))
                                            <form method="POST" action="{{ route('friends.destroy', $friendship) }}" class="flex-1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button bg-primary-soft text-primary dark:text-white w-full">{{ __('ui.members_friend_request_sent') }}</button>
                                            </form>
                                            <a href="{{ route('messages.with-user', $member) }}" class="button bg-secondery !w-auto dark:bg-white/10"><ion-icon name="chatbubble-ellipses-outline" class="text-xl"></ion-icon></a>
                                        @elseif ($friendship->isPending() && $friendship->isRecipient($viewer))
                                            <form method="POST" action="{{ route('friends.accept', $friendship) }}" class="flex-1">
                                                @csrf
                                                <button type="submit" class="button bg-primary text-white w-full">{{ __('ui.profile_accept') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('friends.decline', $friendship) }}">
                                                @csrf
                                                <button type="submit" class="button bg-secondery !w-auto dark:bg-white/10"><ion-icon name="close-outline" class="text-xl"></ion-icon></button>
                                            </form>
                                        @elseif ($friendship->isAccepted())
                                            <form method="POST" action="{{ route('friends.destroy', $friendship) }}" class="flex-1" onsubmit="return confirm('{{ __('ui.profile_friend_remove_confirm') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button bg-primary-soft text-primary dark:text-white w-full">{{ __('ui.members_friends_active') }}</button>
                                            </form>
                                            <a href="{{ route('messages.with-user', $member) }}" class="button bg-secondery !w-auto dark:bg-white/10"><ion-icon name="chatbubble-ellipses-outline" class="text-xl"></ion-icon></a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if ($members->hasMorePages())
                            <div class="flex justify-center my-6 lg:col-span-3 col-span-2">
                                <a href="{{ $members->nextPageUrl() }}" class="bg-white py-2 px-5 rounded-full shadow-md font-semibold text-sm dark:bg-dark2">{{ __('ui.load_more') }}</a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="2xl:w-[380px] lg:w-[330px] w-full">
        <div class="lg:space-y-6 space-y-4 lg:pb-8 max-lg:grid sm:grid-cols-2 max-lg:gap-6" uk-sticky="media: 1024; end: #js-oversized; offset: 80">
            <div class="box p-5 px-6">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.members_widget_lfg_ready') }}</h3>
                    <a href="{{ $filterUrl(['lfg' => '1', 'relationship' => 'all']) }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                </div>

                <div class="side-list">
                    @forelse ($lfgWidgetMembers as $widgetMember)
                        @php
                            $widgetUrl = $widgetMember->username ? route('profile.public', $widgetMember) : route('members.index');
                            if ((int) $widgetMember->id === (int) $viewer->id) { $widgetUrl = route('profile.show'); }
                        @endphp
                        <div class="side-list-item">
                            <a href="{{ $widgetUrl }}"><img src="{{ $widgetMember->avatarUrl() }}" alt="{{ $widgetMember->name }}" class="side-list-image rounded-full"></a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ $widgetUrl }}"><h4 class="side-list-title truncate">{{ $widgetMember->name }}</h4></a>
                                <div class="side-list-info truncate">{{ $widgetMember->profile?->playstyle ?: __('ui.members_playstyle_open') }}</div>
                            </div>
                            @if ((int) $widgetMember->id === (int) $viewer->id)
                                <a class="button bg-secondery" href="{{ $widgetUrl }}">{{ __('ui.view_profile') }}</a>
                            @else
                                <a class="button bg-primary-soft text-primary dark:text-white" href="{{ route('messages.with-user', $widgetMember) }}">{{ __('ui.profile_message_singular') }}</a>
                            @endif
                        </div>
                    @empty
                        <p class="mt-4 text-sm text-gray-500 dark:text-white/70">{{ __('ui.members_widget_lfg_empty') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.members_widget_new_hunters') }}</h3>
                    <a href="{{ route('members.index') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                </div>

                <div class="side-list">
                    @foreach ($recentWidgetMembers as $recentMember)
                        @php
                            $recentUrl = $recentMember->username ? route('profile.public', $recentMember) : route('members.index');
                            if ((int) $recentMember->id === (int) $viewer->id) { $recentUrl = route('profile.show'); }
                        @endphp
                        <div class="side-list-item">
                            <a href="{{ $recentUrl }}"><img src="{{ $recentMember->avatarUrl() }}" alt="{{ $recentMember->name }}" class="side-list-image rounded-full"></a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ $recentUrl }}"><h4 class="side-list-title truncate">{{ $recentMember->name }}</h4></a>
                                <div class="side-list-info truncate">{{ __('ui.account_member_since') }} {{ optional($recentMember->created_at)->format('d.m.Y') }}</div>
                            </div>
                            <a class="button bg-secondery" href="{{ $recentUrl }}">{{ __('ui.view_profile') }}</a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.members_widget_active_hunters') }}</h3>
                    <a href="{{ route('feed.index') }}" class="text-sm text-blue-500">{{ __('ui.feed') }}</a>
                </div>

                <div class="side-list">
                    @foreach ($topWidgetMembers as $topMember)
                        @php
                            $topUrl = $topMember->username ? route('profile.public', $topMember) : route('members.index');
                            if ((int) $topMember->id === (int) $viewer->id) { $topUrl = route('profile.show'); }
                        @endphp
                        <div class="side-list-item">
                            <a href="{{ $topUrl }}"><img src="{{ $topMember->avatarUrl() }}" alt="{{ $topMember->name }}" class="side-list-image rounded-full"></a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ $topUrl }}"><h4 class="side-list-title truncate">{{ $topMember->name }}</h4></a>
                                <div class="side-list-info truncate">{{ (int) ($topMember->visible_feed_posts_count ?? 0) }} {{ __('ui.profile_posts_stat') }} · {{ (int) ($topMember->badges_count ?? 0) }} {{ __('ui.profile_badges_stat') }}</div>
                            </div>
                            <a class="button bg-primary text-white" href="{{ $topUrl }}">{{ __('ui.view_profile') }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
