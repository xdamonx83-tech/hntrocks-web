@extends('themes.socialite.layouts.app')

@section('title', __('ui.lfg_index_title'))
@section('meta_description', __('ui.lfg_index_banner_text'))

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

        return route('lfg.index', $query);
    };
@endphp

@section('content')
<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto w-full" id="js-lfg-groups2">
    <div class="flex-1 min-w-0">
        <div class="max-w-[680px] w-full mx-auto">
            <div class="page-heading">
                <h1 class="page-title">{{ __('ui.lfg_index_title') }}</h1>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
            @endif

            <div class="box p-5 px-6 relative">
                <div class="flex items-center justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.lfg_my_posts') }}</h3>
                    <a href="{{ route('lfg.create') }}" class="text-sm text-blue-500">{{ __('ui.lfg_create_title') }}</a>
                </div>

                @if (($managedLfgPosts ?? collect())->isNotEmpty())
                    <div class="relative mt-4" tabindex="-1" uk-slider="finite: true">
                        <div class="uk-slider-container pb-1">
                            <ul class="uk-slider-items w-[calc(100%+14px)]">
                                @foreach ($managedLfgPosts as $managedPost)
                                    @php
                                        $managedAuthor = $managedPost->user;
                                        $managedOpen = $managedPost->slotsOpen();
                                    @endphp
                                    <li class="pr-3 xl:w-1/5 lg:w-1/4 md:w-1/4 sm:w-1/3 w-1/2">
                                        <a href="{{ route('lfg.show', $managedPost) }}" class="block group">
                                            <div class="relative h-16 overflow-hidden rounded-md bg-slate-200 dark:bg-dark3">
                                                <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-800 to-slate-950"></div>
                                                <div class="absolute inset-0 opacity-70 bg-[radial-gradient(circle_at_20%_20%,rgba(59,130,246,.36),transparent_35%),radial-gradient(circle_at_92%_0%,rgba(20,184,166,.24),transparent_32%)]"></div>
                                                <img src="{{ $managedAuthor->avatarUrl() }}" alt="{{ $managedAuthor->name }}" class="absolute right-2 bottom-2 h-9 w-9 rounded-full object-cover ring-2 ring-white/70 dark:ring-slate-900">
                                                @if ($managedOpen > 0)
                                                    <span class="absolute top-2 left-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:bg-slate-900/90">{{ $managedOpen }} {{ __('ui.lfg_stat_free') }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-sm font-semibold text-black line-clamp-1 dark:text-white">{{ $managedPost->title }}</p>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @if ($managedLfgPosts->count() > 4)
                            <a class="nav-next !top-7" href="#" uk-slider-item="next"><ion-icon name="chevron-forward" class="text-2xl"></ion-icon></a>
                        @endif
                    </div>
                @else
                    <div class="mt-4 rounded-xl bg-secondery p-4 text-sm leading-6 text-gray-600 dark:bg-dark3 dark:text-white/70">
                        {{ __('ui.lfg_empty_text') }}
                    </div>
                @endif
            </div>

            <nav class="mt-8 dark:border-slate-700 mb-6">
                <ul class="flex gap-2 text-xs text-center text-gray-600 capitalize font-semibold dark:text-white/80">
                    <li>
                        <a href="{{ $filterUrl(['sort' => 'newest', 'mine' => null]) }}" class="inline-flex items-center gap-2 py-2.5 px-4 rounded-full {{ $activeSort === 'newest' && empty($filters['mine']) ? 'text-white bg-black dark:bg-white dark:text-black' : 'hover:bg-secondery dark:hover:bg-white/10' }}">
                            {{ __('ui.lfg_market_suggestions') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $filterUrl(['sort' => 'open_slots', 'mine' => null]) }}" class="inline-flex items-center gap-2 py-2.5 px-4 rounded-full {{ $activeSort === 'open_slots' ? 'text-white bg-black dark:bg-white dark:text-black' : 'hover:bg-secondery dark:hover:bg-white/10' }}">
                            {{ __('ui.lfg_sort_open_slots') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $filterUrl(['mine' => empty($filters['mine']) ? 1 : null, 'sort' => 'newest']) }}" class="inline-flex items-center gap-2 py-2.5 px-4 rounded-full {{ ! empty($filters['mine']) ? 'text-white bg-black dark:bg-white dark:text-black' : 'hover:bg-secondery dark:hover:bg-white/10' }}">
                            {{ __('ui.lfg_my_posts') }}
                        </a>
                    </li>
                </ul>
            </nav>

            @if ($posts->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-8 text-center">
                    <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-secondery dark:bg-dark3"><ion-icon name="trail-sign-outline" class="text-2xl"></ion-icon></div>
                    <h3 class="text-lg font-bold text-black dark:text-white">{{ __('ui.lfg_empty_title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.lfg_empty_text') }}</p>
                    <a class="button bg-primary text-white mt-4 mx-auto !w-auto inline-flex" href="{{ route('lfg.create') }}">{{ __('ui.lfg_create_title') }}</a>
                </div>
            @else
                <div class="grid md:grid-cols-3 grid-cols-2 gap-2.5" uk-scrollspy="target: > div; cls: uk-animation-scale-up; delay: 20 ;repeat: true">
                    @foreach ($posts as $post)
                        @php
                            $author = $post->user;
                            $slotsOpen = $post->slotsOpen();
                            $platform = $post->localizedOptionLabel('platform', $post->platform) ?: $post->platform;
                            $region = $post->localizedOptionLabel('region', $post->region) ?: $post->region;
                        @endphp
                        <div class="card">
                            <div class="card-body relative z-10 !p-4">
                                <a href="{{ route('lfg.show', $post) }}" class="mb-3 flex items-center justify-between gap-2 text-[11px] font-bold text-gray-600 dark:text-white/70">
                                    <span class="rounded-full bg-secondery px-2.5 py-1 dark:bg-dark3">{{ $post->statusLabel() }}</span>
                                    <span class="rounded-full bg-secondery px-2.5 py-1 dark:bg-dark3">{{ $slotsOpen }} {{ __('ui.lfg_stat_free') }}</span>
                                </a>
                                <div class="flex items-start gap-3">
                                    <a href="{{ route('lfg.show', $post) }}" class="relative shrink-0">
                                        <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" class="w-10 h-10 rounded-full object-cover shadow border-2 border-white dark:border-slate-800 bg-white dark:bg-dark3">
                                        @if ($post->status === 'open' && $slotsOpen > 0)
                                            <span class="bg-blue-600 rounded-full w-3 h-3 ring-4 ring-white dark:ring-slate-900 absolute -top-0.5 -right-0.5 z-[2]"></span>
                                        @endif
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('lfg.show', $post) }}"><h4 class="card-title line-clamp-1">{{ $post->title }}</h4></a>
                                        <div class="card-text mt-1">
                                            <div class="flex items-center flex-wrap space-x-1">
                                                <a href="{{ route('lfg.show', $post) }}"><span>{{ $platform ?: __('ui.platform') }}</span></a>
                                                <span>·</span>
                                                <a href="{{ route('lfg.show', $post) }}"><span>{{ $region ?: __('ui.region') }}</span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($posts->hasMorePages())
                    <div class="flex justify-center my-6">
                        <a href="{{ $posts->nextPageUrl() }}" class="bg-white py-2 px-5 rounded-full shadow-md font-semibold text-sm dark:bg-dark2">{{ __('ui.load_more') }}</a>
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
                    <h3 class="font-bold text-base">{{ __('ui.lfg_widget_open_teams') }}</h3>
                    <a href="{{ route('teams.index', ['recruiting' => 1]) }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                </div>

                <div class="side-list">
                    @forelse (($openTeams ?? collect()) as $team)
                        <div class="side-list-item">
                            <a href="{{ route('teams.show', $team) }}">
                                <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="side-list-image rounded-md object-cover">
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('teams.show', $team) }}"><h4 class="side-list-title truncate">{{ $team->name }}</h4></a>
                                <div class="side-list-info truncate">{{ trans_choice('ui.teams_members_count', (int) ($team->members_count ?? 0), ['count' => (int) ($team->members_count ?? 0)]) }}</div>
                            </div>
                            <a href="{{ route('teams.show', $team) }}" class="button bg-primary-soft text-primary dark:text-white">{{ __('ui.teams') }}</a>
                        </div>
                    @empty
                        <div class="side-list-info py-3">{{ __('ui.teams_widget_empty') }}</div>
                    @endforelse
                </div>

                <a href="{{ route('teams.index', ['recruiting' => 1]) }}" class="bg-secondery w-full text-black py-1.5 font-medium px-3.5 rounded-md text-sm mt-3 dark:text-white text-center block">{{ __('ui.see_all') }}</a>
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
