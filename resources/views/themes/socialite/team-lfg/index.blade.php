@extends('themes.socialite.layouts.app')

@section('title', __('ui.team_lfg_index_title'))
@section('meta_description', __('ui.team_lfg_index_text'))

@php
    $filters = $filters ?? [];
    $activeType = $filters['type'] ?? 'all';
    $filterUrl = function (array $params = []) {
        $query = array_merge(request()->except(['page']), $params);
        foreach ($query as $key => $value) {
            if ($value === null || $value === '' || $value === false || $value === 'all') {
                unset($query[$key]);
            }
        }

        return route('team-lfg.index', $query);
    };
    $profileUrl = function ($user) {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };
@endphp

@section('content')
<div class="2xl:max-w-[1220px] max-w-[1065px] mx-auto py-8 px-1">
    <div class="page-heading">
        <div>
            <h1 class="page-title">{{ __('ui.team_lfg_index_title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-white/70 mt-1">{{ __('ui.team_lfg_index_text') }}</p>
        </div>

        <nav class="nav__underline flex items-center justify-between gap-4">
            <ul>
                <li class="{{ $activeType === 'all' || $activeType === '' ? 'uk-active' : '' }}"><a href="{{ $filterUrl(['type' => null]) }}">{{ __('ui.view_all') }}</a></li>
                <li class="{{ $activeType === 'team_seeks_players' ? 'uk-active' : '' }}"><a href="{{ $filterUrl(['type' => 'team_seeks_players']) }}">{{ __('ui.team_lfg_type_team_seeks_players') }}</a></li>
                <li class="{{ $activeType === 'player_seeks_team' ? 'uk-active' : '' }}"><a href="{{ $filterUrl(['type' => 'player_seeks_team']) }}">{{ __('ui.team_lfg_type_player_seeks_team') }}</a></li>
                <li class="{{ ! empty($filters['mine']) ? 'uk-active' : '' }}"><a href="{{ $filterUrl(['mine' => empty($filters['mine']) ? 1 : null]) }}">{{ __('ui.account') }}</a></li>
            </ul>

            <div class="flex items-center gap-3 max-sm:hidden">
                <a href="{{ route('team-lfg.create') }}" class="flex items-center gap-1.5 py-1.5 px-3 bg-primary text-white shadow rounded-md">
                    <ion-icon class="text-lg" name="add-outline"></ion-icon>
                    <span class="text-xs font-medium">{{ __('ui.team_lfg_create') }}</span>
                </a>
            </div>
        </nav>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('team-lfg.index') }}" class="relative -mt-3 mb-4" tabindex="-1" uk-slider="finite: true" uk-sticky="cls-active: bg-slate-100/60 z-30 backdrop-blur-lg px-6 py-1 dark:bg-slate-800/60; offset: 76; start: 10; animation: uk-animation-slide-top">
        <div class="py-1 overflow-hidden uk-slider-container">
            <ul class="py-2 uk-slider-items w-[calc(100%+0.10px)] capitalize text-sm font-semibold">
                <li class="w-auto pr-2.5">
                    <div class="relative min-w-[280px]">
                        <ion-icon name="search-outline" class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400"></ion-icon>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="w-full px-4 py-2 pl-11 rounded-lg bg-white shadow border-0 dark:bg-dark2" placeholder="{{ __('ui.team_lfg_index_title') }}">
                    </div>
                </li>
                <li class="w-auto pr-2.5">
                    <select name="type" class="w-48 px-4 py-2 rounded-lg bg-white shadow border-0 dark:bg-dark2">
                        <option value="">{{ __('ui.team_lfg_type_all') }}</option>
                        <option value="team_seeks_players" @selected(($filters['type'] ?? '') === 'team_seeks_players')>{{ __('ui.team_lfg_type_team_seeks_players') }}</option>
                        <option value="player_seeks_team" @selected(($filters['type'] ?? '') === 'player_seeks_team')>{{ __('ui.team_lfg_type_player_seeks_team') }}</option>
                    </select>
                </li>
                <li class="w-auto pr-2.5"><input type="text" name="platform" value="{{ $filters['platform'] ?? '' }}" class="w-32 px-4 py-2 rounded-lg bg-white shadow border-0 dark:bg-dark2" placeholder="{{ __('ui.platform') }}"></li>
                <li class="w-auto pr-2.5"><input type="text" name="region" value="{{ $filters['region'] ?? '' }}" class="w-28 px-4 py-2 rounded-lg bg-white shadow border-0 dark:bg-dark2" placeholder="{{ __('ui.region') }}"></li>
                <li class="w-auto pr-2.5"><input type="text" name="playstyle" value="{{ $filters['playstyle'] ?? '' }}" class="w-36 px-4 py-2 rounded-lg bg-white shadow border-0 dark:bg-dark2" placeholder="{{ __('ui.playstyle') }}"></li>
                <li class="w-auto pr-2.5">
                    <label class="px-4 py-2 rounded-lg bg-white shadow inline-flex items-center gap-2 dark:bg-dark2 cursor-pointer">
                        <input type="checkbox" name="voice_required" value="1" @checked(! empty($filters['voice_required'])) class="rounded border-gray-300">
                        <span>{{ __('ui.team_lfg_voice_only') }}</span>
                    </label>
                </li>
                <li class="w-auto pr-2.5"><button class="px-4 py-2 rounded-lg bg-primary text-white shadow inline-flex items-center gap-1.5" type="submit"><ion-icon name="search-outline"></ion-icon><span>{{ __('ui.search') }}</span></button></li>
                <li class="w-auto pr-2.5 sm:hidden"><a href="{{ route('team-lfg.create') }}" class="px-4 py-2 rounded-lg bg-white shadow inline-flex items-center gap-1.5 dark:bg-dark2"><ion-icon name="add-outline"></ion-icon><span>{{ __('ui.team_lfg_create') }}</span></a></li>
            </ul>
        </div>
    </form>

    @if ($posts->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-8 text-center">
            <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-secondery dark:bg-dark3"><ion-icon name="flag-outline" class="text-2xl"></ion-icon></div>
            <h3 class="text-lg font-bold text-black dark:text-white">{{ __('ui.team_lfg_empty_title') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.team_lfg_empty_index_text') }}</p>
            <a class="button bg-primary text-white mt-4 mx-auto !w-auto inline-flex" href="{{ route('team-lfg.create') }}">{{ __('ui.team_lfg_create') }}</a>
        </div>
    @else
        <div class="grid 2xl:grid-cols-4 lg:grid-cols-3 md:grid-cols-2 sm:grid-cols-2 gap-2.5 mt-6" uk-scrollspy="target: > div; cls: uk-animation-slide-bottom-small; delay: 80 ;repeat: true">
            @foreach ($posts as $post)
                @php
                    $author = $post->user;
                    $team = $post->team;
                    $visualName = $post->isTeamSeekingPlayers() && $team ? $team->name : $author->name;
                    $visualImage = $post->isTeamSeekingPlayers() && $team ? $team->avatarUrl() : $author->avatarUrl();
                    $visualCover = $post->isTeamSeekingPlayers() && $team ? $team->coverUrl() : $author->avatarUrl();
                    $visualUrl = $post->isTeamSeekingPlayers() && $team ? route('teams.show', $team) : $profileUrl($author);
                    $tags = collect($post->displayTags());
                    $slotsOpen = $post->slotsOpen();
                    $slotPercent = $post->isTeamSeekingPlayers() ? max(0, min(100, ((int) $post->slots_filled / max(1, (int) $post->slots_total)) * 100)) : null;
                @endphp
                <div class="card">
                    <a href="{{ route('team-lfg.show', $post) }}">
                        <div class="card-media h-36 bg-secondery dark:bg-dark3">
                            <img src="{{ $visualCover }}" alt="{{ $visualName }}" class="h-full w-full object-cover">
                            <div class="card-overly"></div>
                            <span class="absolute bg-black bg-opacity-60 bottom-2 font-semibold px-2 py-1 left-2 rounded text-white text-xs z-10">{{ $post->typeLabel() }}</span>
                            <span class="absolute bg-white/90 top-2 font-semibold px-2 py-1 right-2 rounded text-slate-800 text-xs z-10">{{ $post->statusLabel() }}</span>
                        </div>
                    </a>
                    <div class="card-body">
                        <a href="{{ $visualUrl }}" class="flex items-center gap-2 mb-2">
                            <img src="{{ $visualImage }}" alt="{{ $visualName }}" class="h-8 w-8 rounded-full object-cover bg-secondery dark:bg-dark3">
                            <p class="card-text truncate">{{ $visualName }} · {{ $post->created_at?->diffForHumans() }}</p>
                        </a>
                        <a href="{{ route('team-lfg.show', $post) }}"><h4 class="card-title text-sm line-clamp-2 mt-1.5">{{ $post->title }}</h4></a>
                        @if ($post->body)
                            <p class="card-text mt-2 line-clamp-3">{{ $post->body }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($tags->take(3) as $tag)
                                <span class="rounded-full bg-secondery px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-dark3 dark:text-white/70">{{ $tag }}</span>
                            @endforeach
                            <span class="rounded-full bg-secondery px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-dark3 dark:text-white/70">{{ $post->voiceLabel() }}</span>
                        </div>
                        @if ($post->isTeamSeekingPlayers())
                            <div class="mt-4 rounded-xl bg-secondery p-3 dark:bg-dark3">
                                <div class="mb-2 flex items-center justify-between text-xs font-bold text-gray-500 dark:text-white/70">
                                    <span>{{ __('ui.team_lfg_form_slots_total') }}</span>
                                    <span>{{ $post->slots_filled }}/{{ $post->slots_total }} · {{ $slotsOpen }} {{ __('ui.team_lfg_card_free_slots') }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-white dark:bg-slate-700"><div class="h-full rounded-full bg-primary" style="width: {{ $slotPercent }}%"></div></div>
                            </div>
                        @endif
                        <div class="card-list-info items-center gap-4 mt-3">
                            <div class="flex items-center gap-1.5"><ion-icon name="mail-unread-outline" class="text-lg"></ion-icon>{{ (int) $post->pending_count }}</div>
                            <a href="{{ route('team-lfg.show', $post) }}" class="flex ml-auto"><ion-icon name="arrow-redo-outline" class="text-lg"></ion-icon></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($posts->hasPages())
        <div class="flex justify-center gap-2 mt-6 text-sm font-semibold">
            @if ($posts->onFirstPage())
                <span class="px-4 py-2 rounded-full bg-secondery text-gray-400 dark:bg-dark3">{{ __('ui.previous') }}</span>
            @else
                <a class="px-4 py-2 rounded-full bg-white shadow-sm border1 dark:bg-dark2" href="{{ $posts->previousPageUrl() }}">{{ __('ui.previous') }}</a>
            @endif
            <span class="px-4 py-2 rounded-full bg-primary text-white">{{ $posts->currentPage() }}</span>
            @if ($posts->hasMorePages())
                <a class="px-4 py-2 rounded-full bg-white shadow-sm border1 dark:bg-dark2" href="{{ $posts->nextPageUrl() }}">{{ __('ui.next') }}</a>
            @else
                <span class="px-4 py-2 rounded-full bg-secondery text-gray-400 dark:bg-dark3">{{ __('ui.next') }}</span>
            @endif
        </div>
    @endif
</div>
@endsection
