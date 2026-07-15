@extends('themes.socialite.layouts.app')

@section('title', __('ui.teams_index_title'))
@section('meta_description', __('ui.teams_index_banner_text'))

@php
    $filters = $filters ?? [];
    $activeSort = $filters['sort'] ?? 'newest';
    $filterUrl = function (array $params = []) {
        $query = array_merge(request()->except(['page']), $params);
        foreach ($query as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($query[$key]);
            }
        }

        return route('teams.index', $query);
    };
@endphp

@section('content')
<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto w-full" id="js-teams-oversized">
    <div class="flex-1 min-w-0">
        <div class="max-w-[680px] w-full mx-auto">
            <div class="page-heading">
                <h1 class="page-title">{{ __('ui.teams') }}</h1>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
            @endif

            <div class="box p-5">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.teams_manage_slider_title') }}</h3>
                    <a href="{{ route('teams.create') }}" class="text-sm text-blue-500">{{ __('ui.create_team') }}</a>
                </div>

                @if (($managedTeams ?? collect())->isNotEmpty())
                    <div class="relative mt-2" tabindex="-1" uk-slider>
                        <div class="overflow-hidden uk-slider-container">
                            <ul class="-ml-2 uk-slider-items w-[calc(100%+0.5rem)] pt-3 text-center" uk-scrollspy="target: > li; cls: uk-animation-scale-up; delay: 20 ;repeat: true">
                                @foreach ($managedTeams as $team)
                                    <li class="md:w-[14.28%] w-32 pr-3 pt-3">
                                        <a href="{{ route('teams.show', $team) }}">
                                            <div class="relative">
                                                <div class="card-media md:aspect-[2/1.8] max-lg:h-28 rounded-lg bg-slate-200 dark:bg-dark3">
                                                    <img src="{{ $team->coverUrl() }}" alt="{{ $team->name }}" class="h-full w-full object-cover">
                                                    <div class="card-overly"></div>
                                                </div>
                                                <h4 class="card-title text-sm pt-2 line-clamp-1">{{ $team->name }}</h4>
                                                @if ($team->recruitment_status === 'open')
                                                    <div class="bg-blue-600 rounded-full w-3 h-3 ring-4 ring-white dark:ring-slate-900 absolute top-0 right-0 -m-1 z-[2]"></div>
                                                @endif
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <a class="nav-prev !top-12" href="#" uk-slider-item="previous"><ion-icon name="chevron-back" class="text-2xl"></ion-icon></a>
                        <a class="nav-next !top-12" href="#" uk-slider-item="next"><ion-icon name="chevron-forward" class="text-2xl"></ion-icon></a>
                    </div>
                @else
                    <div class="mt-4 rounded-xl bg-secondery p-4 text-sm text-gray-600 dark:bg-dark3 dark:text-white/70">
                        {{ __('ui.teams_manage_empty_text') }}
                    </div>
                @endif
            </div>

            <nav class="mt-8 dark:border-slate-700 mb-6">
                <ul class="flex gap-2 text-xs text-center text-gray-600 capitalize font-semibold dark:text-white/80">
                    <li>
                        <a href="{{ $filterUrl(['sort' => 'newest', 'recruiting' => null]) }}" class="inline-flex items-center gap-2 py-2.5 px-4 rounded-full {{ $activeSort === 'newest' && empty($filters['recruiting']) ? 'text-white bg-black dark:bg-white dark:text-black' : 'hover:bg-secondery dark:hover:bg-white/10' }}">
                            {{ __('ui.teams_tab_suggestions') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $filterUrl(['sort' => 'members', 'recruiting' => null]) }}" class="inline-flex items-center gap-2 py-2.5 px-4 rounded-full {{ $activeSort === 'members' ? 'text-white bg-black dark:bg-white dark:text-black' : 'hover:bg-secondery dark:hover:bg-white/10' }}">
                            {{ __('ui.teams_tab_popular') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $filterUrl(['recruiting' => empty($filters['recruiting']) ? 1 : null, 'sort' => 'newest']) }}" class="inline-flex items-center gap-2 py-2.5 px-4 rounded-full {{ ! empty($filters['recruiting']) ? 'text-white bg-black dark:bg-white dark:text-black' : 'hover:bg-secondery dark:hover:bg-white/10' }}">
                            {{ __('ui.teams_tab_recruiting') }}
                        </a>
                    </li>
                </ul>
            </nav>

            @if ($teams->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-8 text-center">
                    <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-secondery dark:bg-dark3"><ion-icon name="people-outline" class="text-2xl"></ion-icon></div>
                    <h3 class="text-lg font-bold text-black dark:text-white">{{ __('ui.teams_no_results_title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.teams_no_results_text') }}</p>
                    <a class="button bg-primary text-white mt-4 mx-auto !w-auto inline-flex" href="{{ route('teams.create') }}">{{ __('ui.create_team') }}</a>
                </div>
            @else
                <div class="grid md:grid-cols-3 grid-cols-2 gap-2.5" uk-scrollspy="target: > div; cls: uk-animation-scale-up; delay: 20 ;repeat: true">
                    @foreach ($teams as $team)
                        @php
                            $memberCount = (int) ($team->members_count ?? $team->active_members_count ?? $team->activeMembers->count());
                        @endphp
                        <div class="card">
                            <a href="{{ route('teams.show', $team) }}">
                                <div class="card-media h-24 bg-slate-200 dark:bg-dark3">
                                    <img src="{{ $team->coverUrl() }}" alt="{{ $team->name }}" class="h-full w-full object-cover">
                                    <div class="card-overly"></div>
                                    @if ($team->recruitment_status === 'open')
                                        <div class="bg-blue-600 rounded-full w-3 h-3 ring-4 ring-white dark:ring-slate-900 absolute top-2 right-2 z-[2]"></div>
                                    @endif
                                </div>
                            </a>
                            <div class="card-body relative z-10">
                                <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="w-10 h-10 rounded-full object-cover mb-2 shadow md:-mt-11 -mt-7 relative border-2 border-white dark:border-slate-800 bg-white dark:bg-dark3">
                                <a href="{{ route('teams.show', $team) }}"><h4 class="card-title line-clamp-1">{{ $team->name }}</h4></a>
                                <div class="card-text mt-1">
                                    <div class="flex items-center flex-wrap space-x-1">
                                        <a href="{{ route('teams.show', $team) }}"><span>{{ trans_choice('ui.teams_members_count', $memberCount, ['count' => $memberCount]) }}</span></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($teams->hasMorePages())
                    <div class="flex justify-center my-6">
                        <a href="{{ $teams->nextPageUrl() }}" class="bg-white py-2 px-5 rounded-full shadow-md font-semibold text-sm dark:bg-dark2">{{ __('ui.load_more') }}</a>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="2xl:w-[380px] lg:w-[330px] w-full shrink-0">
        <div class="lg:space-y-6 space-y-4 lg:pb-8 max-lg:grid sm:grid-cols-2 max-lg:gap-6">
            <div class="box p-5 px-6">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.teams_widget_member_suggestions') }}</h3>
                    <a href="{{ route('members.index') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                </div>

                <div class="side-list">
                    @forelse (($memberSuggestions ?? collect()) as $member)
                        <div class="side-list-item">
                            <a href="{{ route('profile.public', $member) }}">
                                <img src="{{ $member->avatarUrl() }}" alt="{{ $member->username }}" class="side-list-image rounded-md object-cover">
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('profile.public', $member) }}"><h4 class="side-list-title truncate">{{ $member->name }}</h4></a>
                                <div class="side-list-info truncate">{{ '@' . $member->username }}</div>
                            </div>
                            <a href="{{ route('profile.public', $member) }}" class="button bg-secondery">{{ __('ui.profile') }}</a>
                        </div>
                    @empty
                        <div class="side-list-info py-3">{{ __('ui.teams_widget_empty') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.teams_widget_open_lfg') }}</h3>
                    <a href="{{ route('lfg.index') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                </div>

                <div class="side-list">
                    @forelse (($openLfgPosts ?? collect()) as $post)
                        <div class="side-list-item">
                            <a href="{{ route('lfg.show', $post) }}">
                                <img src="{{ $post->user->avatarUrl() }}" alt="{{ $post->user->username }}" class="side-list-image rounded-md object-cover">
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('lfg.show', $post) }}"><h4 class="side-list-title truncate">{{ $post->title }}</h4></a>
                                <div class="side-list-info truncate">{{ $post->slotsOpen() }} {{ __('ui.lfg_stat_free') }} · {{ $post->user->username }}</div>
                            </div>
                            <a href="{{ route('lfg.show', $post) }}" class="button bg-primary-soft text-primary dark:text-white">{{ __('ui.lfg') }}</a>
                        </div>
                    @empty
                        <div class="side-list-info py-3">{{ __('ui.teams_widget_empty') }}</div>
                    @endforelse
                </div>

                <a href="{{ route('lfg.index') }}" class="bg-secondery w-full text-black py-1.5 font-medium px-3.5 rounded-md text-sm mt-3 dark:text-white text-center block">{{ __('ui.see_all') }}</a>
            </div>

            <div class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.teams_widget_cups') }}</h3>
                    <a href="{{ route('cups.index') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                </div>

                <div class="side-list">
                    @forelse (($featuredCups ?? collect()) as $cup)
                        <div class="side-list-item">
                            <a href="{{ route('cups.show', $cup) }}">
                                <img src="{{ $cup->coverUrl() }}" alt="{{ $cup->title }}" class="side-list-image rounded-md object-cover">
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('cups.show', $cup) }}"><h4 class="side-list-title truncate">{{ $cup->title }}</h4></a>
                                <div class="side-list-info truncate">{{ $cup->statusLabel() }} · {{ (int) ($cup->participants_count ?? 0) }} {{ __('ui.members') }}</div>
                            </div>
                            <a href="{{ route('cups.show', $cup) }}" class="button bg-primary text-white">{{ __('ui.cups') }}</a>
                        </div>
                    @empty
                        <div class="side-list-info py-3">{{ __('ui.teams_widget_empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
