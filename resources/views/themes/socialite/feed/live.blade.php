@extends('themes.socialite.layouts.app')

@php
    $socialiteFeedIsLiveRoute = request()->routeIs('feed.index');
    $socialiteFeedFilter = $socialiteFeedFilter ?? 'all';
    $socialiteFeedFilters = [
        'all' => ['label' => __('ui.feed_filter_all'), 'icon' => 'newspaper-outline'],
        'friends' => ['label' => __('ui.friends'), 'icon' => 'people-outline'],
        'teams' => ['label' => __('ui.teams'), 'icon' => 'shield-checkmark-outline'],
        'media' => ['label' => __('ui.feed_filter_media'), 'icon' => 'images-outline'],
        'mentions' => ['label' => __('ui.feed_filter_mentions'), 'icon' => 'at-outline'],
    ];
    $socialiteFilterUrl = function (string $filterKey): string {
        $query = request()->query();
        unset($query['page'], $query['fragment']);

        if ($filterKey === 'all') {
            unset($query['filter']);
        } else {
            $query['filter'] = $filterKey;
        }

        return request()->url() . (count($query) ? '?' . http_build_query($query) : '');
    };

    $socialiteMemberProfileUrl = function ($member): string {
        $username = data_get($member, 'username');

        if (! $username) {
            return route('members.index');
        }

        return (int) data_get($member, 'id') === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $member);
    };
@endphp

@section('title', $socialiteFeedIsLiveRoute ? __('ui.feed_title') . ' · HNT.rocks' : 'Socialite Feed Data Preview · HNT.rocks')
@section('meta_description', $socialiteFeedIsLiveRoute ? __('ui.feed_meta_description') : 'Socialite theme feed preview with HNT.rocks data.')

@section('content')
            <!-- timeline -->
            <div class="lg:flex 2xl:gap-16 gap-12 max-w-[1065px] mx-auto mt-5 md:mt-7" id="js-oversized">

                <div class="max-w-[680px] mx-auto">

                    <!-- feed story -->
                    <div class="md:max-w-[580px] mx-auto flex-1 xl:space-y-6 space-y-3">

                        <!-- add status / composer preview -->
                        <div class="bg-white rounded-xl shadow-sm md:p-4 p-2 space-y-4 text-sm font-medium border1 dark:bg-dark2">
                            <div class="flex items-center md:gap-3 gap-1">
                                <div class="flex-1 bg-slate-100 hover:bg-opacity-80 transition-all rounded-lg cursor-pointer dark:bg-dark3" uk-toggle="target: #create-status">
                                    <div class="py-2.5 text-center dark:text-white">{{ __('ui.feed_share_with_hunters') }}</div>
                                </div>
                                <div class="cursor-pointer hover:bg-opacity-80 p-1 px-1.5 rounded-xl transition-all bg-pink-100/60 hover:bg-pink-100 dark:bg-white/10 dark:hover:bg-white/20" uk-toggle="target: #create-status">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 stroke-pink-600 fill-pink-200/70" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M3.5 15.5l4.5 -4.5c.928 -.893 2.072 -.893 3 0l5 5" /><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l2.5 2.5" /></svg>
                                </div>
                                <div class="cursor-pointer hover:bg-opacity-80 p-1 px-1.5 rounded-xl transition-all bg-sky-100/60 hover:bg-sky-100 dark:bg-white/10 dark:hover:bg-white/20" uk-toggle="target: #create-status">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 stroke-sky-600 fill-sky-200/70" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-2 border1 dark:bg-dark2">
                            <div class="flex flex-nowrap items-center gap-2 text-sm font-semibold overflow-x-auto whitespace-nowrap py-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                @foreach($socialiteFeedFilters as $filterKey => $filterMeta)
                                    @php($isActiveFilter = $socialiteFeedFilter === $filterKey)
                                    <a
                                        href="{{ $socialiteFilterUrl($filterKey) }}"
                                        class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2 transition {{ $isActiveFilter ? 'bg-slate-900 text-white dark:bg-white dark:text-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-dark3 dark:text-white/80 dark:hover:bg-white/10' }}"
                                        aria-current="{{ $isActiveFilter ? 'page' : 'false' }}"
                                    >
                                        <ion-icon name="{{ $filterMeta['icon'] }}" class="text-lg"></ion-icon>
                                        <span>{{ $filterMeta['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        @include('themes.socialite.feed.partials.post-list', ['socialitePosts' => $socialitePosts])

                    </div>
                </div>

                <!-- sidebar -->
                <div class="flex-1 max-lg:hidden">
                    <div class="w-[320px] space-y-6" uk-sticky="end: #js-oversized; offset: 80">

                        <!-- peaple you might know -->
                        <div class="bg-white rounded-xl shadow-sm p-5 px-6 border1 dark:bg-dark2">
                            <div class="flex justify-between text-black dark:text-white">
                                <h3 class="font-bold text-base">{{ __('ui.feed_active_hunters') }}</h3>
                                <a href="{{ route('members.index') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                            </div>

                            <div class="space-y-4 capitalize text-xs font-normal mt-5 mb-2 text-gray-500 dark:text-white/80">
                                @forelse($socialiteMembers->take(5) as $member)
                                    <div class="flex items-center gap-3">
                                        <a href="{{ $socialiteMemberProfileUrl($member) }}">
                                            <img src="{{ $member->avatarUrl() }}" alt="{{ $member->name }}" class="bg-gray-200 rounded-full w-10 h-10 object-cover">
                                        </a>
                                        <div class="flex-1">
                                            <a href="{{ $socialiteMemberProfileUrl($member) }}"><h4 class="font-semibold text-sm text-black dark:text-white">{{ $member->name }}</h4></a>
                                            <div class="mt-0.5">Lvl {{ $member->level ?? 1 }} · {{ $member->profile?->platform ?: 'HNT' }}</div>
                                        </div>
                                        <a href="{{ $socialiteMemberProfileUrl($member) }}" class="text-sm rounded-full py-1.5 px-4 font-semibold bg-secondery">{{ __('ui.view') }}</a>
                                    </div>
                                @empty
                                    <p>{{ __('ui.feed_no_hunters_found') }}</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Pro Members -->
                        <div class="bg-white rounded-xl shadow-sm p-5 px-6 border1 dark:bg-dark2">
                            <div class="flex justify-between text-black dark:text-white">
                                <h3 class="font-bold text-base">{{ __('ui.feed_featured_teams') }}</h3>
                                <a href="{{ route('teams.index') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                @forelse($socialiteTeams->take(4) as $team)
                                    <a href="{{ route('teams.show', $team) }}" class="text-center p-3 rounded-xl bg-slate-50 hover:bg-slate-100 dark:bg-dark3 dark:hover:bg-white/10">
                                        <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="w-14 h-14 mx-auto rounded-full object-cover">
                                        <div class="mt-2 text-sm font-semibold text-black dark:text-white truncate">{{ $team->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-white/70">{{ trans_choice('ui.hunter_count', (int) $team->active_members_count, ['count' => (int) $team->active_members_count]) }}</div>
                                    </a>
                                @empty
                                    <div class="col-span-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.feed_no_teams_found') }}</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Trends -->
                        <div class="bg-white rounded-xl shadow-sm p-5 px-6 border1 dark:bg-dark2">
                            <div class="flex justify-between text-black dark:text-white">
                                <h3 class="font-bold text-base">{{ __('ui.feed_trends') }}</h3>
                            </div>
                            <div class="space-y-4 mt-4 text-sm">
                                <a href="{{ route('cups.index') }}" class="block"><div class="font-semibold text-black dark:text-white">{{ __('ui.feed_trend_cups') }}</div><div class="text-xs text-gray-500">{{ __('ui.feed_trend_cups_text') }}</div></a>
                                <a href="{{ route('lfg.index') }}" class="block"><div class="font-semibold text-black dark:text-white">{{ __('ui.lfg') }}</div><div class="text-xs text-gray-500">{{ __('ui.feed_trend_lfg_text') }}</div></a>
                                <a href="{{ route('moments.index') }}" class="block"><div class="font-semibold text-black dark:text-white">{{ __('ui.moments') }}</div><div class="text-xs text-gray-500">{{ __('ui.feed_trend_moments_text') }}</div></a>
                                <a href="{{ route('app-beta.index') }}" class="block"><div class="font-semibold text-black dark:text-white">{{ __('ui.android_app') }}</div><div class="text-xs text-gray-500">{{ __('ui.feed_trend_app_text') }}</div></a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
@endsection
