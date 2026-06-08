@extends('themes.socialite.layouts.app')

@section('title', __('ui.gamification_page_title'))

@section('content')
@php
    $profileCompletion = (int) \App\Support\ProfileCompletion::score($user);
    $unlockedBadges = $user->badges->keyBy('id');
    $unlockedBadgesCount = (int) $unlockedBadges->count();
    $availableBadgesCount = (int) $availableBadges->count();
    $completedQuestsCount = (int) $user->questProgress->filter(fn ($progress) => filled($progress->completed_at))->count();
    $questsCount = (int) $quests->count();
    $levelProgress = (int) $levelProgressPercent;
    $unlockedList = $availableBadges->filter(fn ($badge) => $unlockedBadges->has($badge->id));
    $lockedList = $availableBadges->reject(fn ($badge) => $unlockedBadges->has($badge->id));
    $topBadges = $unlockedList->take(5);
    if ($topBadges->isEmpty()) {
        $topBadges = $availableBadges->take(5);
    }
    $fallbackUnlocked = asset('assets/vikinger/img/badge/uexp-b.png');
    $fallbackLocked = asset('assets/vikinger/img/badge/badge-empty.png');
    $fallbackPreview = asset('assets/vikinger/img/badge/uexp-s.png');
    $fallbackLockedPreview = asset('assets/vikinger/img/badge/blank-s.png');
    $questCovers = [
        asset('assets/vikinger/img/quest/cover/01.png'),
        asset('assets/vikinger/img/quest/cover/02.png'),
        asset('assets/vikinger/img/quest/cover/03.png'),
        asset('assets/vikinger/img/quest/cover/04.png'),
    ];
@endphp

<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-oversized">
    <div class="flex-1">
        <div class="max-w-[680px] w-full mx-auto content-area">
            <div class="page-heading">
                <h1 class="page-title">{{ __('ui.gamification_page_title') }}</h1>

                <nav class="nav__underline">
                    <ul uk-switcher="connect: #gamification-tabs ; animation: uk-animation-fade">
                        <li><a href="#">{{ __('ui.gamification_tab_all_badges') }}</a></li>
                        <li><a href="#">{{ __('ui.gamification_tab_unlocked') }}</a></li>
                        <li><a href="#">{{ __('ui.gamification_tab_quests') }}</a></li>
                    </ul>
                </nav>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-500/10 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="box p-5 sm:p-6 mb-6 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-5">
                    <div>
                        <p class="text-sm font-semibold text-blue-600 uppercase tracking-wide">{{ __('ui.gamification_collect_progress') }}</p>
                        <h2 class="mt-2 text-2xl font-bold text-black dark:text-white">{{ __('ui.gamification_badge_collection') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-white/70">{{ __('ui.gamification_badges_games_hint') }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center min-w-[260px]">
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                            <div class="text-2xl font-bold text-black dark:text-white">{{ $user->level }}</div>
                            <div class="text-xs font-semibold text-gray-500">{{ __('ui.gamification_level') }}</div>
                        </div>
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                            <div class="text-2xl font-bold text-black dark:text-white">{{ $unlockedBadgesCount }}</div>
                            <div class="text-xs font-semibold text-gray-500">{{ __('ui.gamification_badges') }}</div>
                        </div>
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5">
                            <div class="text-2xl font-bold text-black dark:text-white">{{ $completedQuestsCount }}</div>
                            <div class="text-xs font-semibold text-gray-500">{{ __('ui.gamification_quests') }}</div>
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs font-semibold text-gray-500">
                        <span>{{ number_format((int) $user->xp_total, 0, ',', '.') }} XP</span>
                        <span>{{ $levelProgress }}% {{ __('ui.gamification_next_level') }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-secondery dark:bg-white/10">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $levelProgress }}%"></div>
                    </div>
                </div>
            </div>

            <div id="gamification-tabs" class="uk-switcher">
                <div>
                    @if ($availableBadges->isEmpty())
                        <div class="card p-8 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                                <ion-icon name="ribbon-outline" class="text-3xl text-gray-500"></ion-icon>
                            </div>
                            <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.gamification_no_badges_title') }}</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.gamification_no_badges_text') }}</p>
                        </div>
                    @else
                        <div class="grid md:grid-cols-4 grid-cols-3 md:gap-3 gap-2" uk-scrollspy="target: > div; cls: uk-animation-scale-up; delay: 20 ;repeat: true">
                            @foreach ($availableBadges as $badge)
                                @php
                                    $isUnlocked = $unlockedBadges->has($badge->id);
                                    $badgeIcon = $badge->iconUrl();
                                    $badgeImage = $badgeIcon ?: ($isUnlocked ? $fallbackUnlocked : $fallbackLocked);
                                    $badgePreview = $badgeIcon ?: ($isUnlocked ? $fallbackPreview : $fallbackLockedPreview);
                                    $badgeXp = (int) ($badge->xp_reward ?? 0);
                                @endphp
                                <div>
                                    <div class="card {{ $isUnlocked ? '' : 'opacity-75' }}">
                                        <div class="card-media sm:aspect-[2/1.8] h-36 bg-secondery dark:bg-white/5 flex items-center justify-center">
                                            <img src="{{ $badgeImage }}" alt="{{ $badge->name }}" class="!w-auto !h-24 object-contain {{ $isUnlocked ? '' : 'grayscale opacity-80' }}">
                                            <div class="card-overly"></div>
                                            <div class="absolute top-2 left-2 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-black shadow dark:bg-dark2 dark:text-white">
                                                {{ $badgeXp > 0 ? $badgeXp . ' XP' : __('ui.gamification_reward') }}
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h4 class="card-title text-sm truncate">{{ $badge->name }}</h4>
                                            <p class="card-text">{{ $isUnlocked ? __('ui.gamification_unlocked') : __('ui.gamification_locked') }}</p>
                                        </div>
                                    </div>

                                    <div class="uk-drop w-80 max-md:!hidden" uk-drop="pos: right-center; boundary: !.content-area; offset: 10; animation: uk-animation-scale-up">
                                        <div class="card shadow-xl">
                                            <div class="card-media h-40 bg-secondery dark:bg-white/5 flex items-center justify-center">
                                                <img src="{{ $badgeImage }}" alt="{{ $badge->name }}" class="!w-auto !h-28 object-contain {{ $isUnlocked ? '' : 'grayscale opacity-80' }}">
                                                <div class="card-overly"></div>
                                            </div>
                                            <div class="card-body" uk-scrollspy="target: > * ; cls: uk-animation-slide-bottom-small ; delay: 60 ;repeat: true">
                                                <h4 class="card-title text-sm font-semibold">{{ $badge->name }}</h4>
                                                <p class="card-text">{{ $badge->rarityLabel() }} · {{ $badgeXp }} XP</p>
                                                <p class="text-sm mt-1.5 text-gray-600 dark:text-white/70">{{ $badge->description ?: __('ui.gamification_badge_default_text') }}</p>
                                                <div class="mt-3 rounded-lg bg-secondery p-3 text-xs font-semibold text-gray-600 dark:bg-white/5 dark:text-white/70">
                                                    {{ $isUnlocked ? __('ui.gamification_unlocked') : __('ui.gamification_locked') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    @if ($unlockedList->isEmpty())
                        <div class="card p-8 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                                <ion-icon name="lock-closed-outline" class="text-3xl text-gray-500"></ion-icon>
                            </div>
                            <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.gamification_no_unlocked_badges_title') }}</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.gamification_no_unlocked_badges_text') }}</p>
                        </div>
                    @else
                        <div class="grid md:grid-cols-4 grid-cols-3 md:gap-3 gap-2" uk-scrollspy="target: > div; cls: uk-animation-scale-up; delay: 20 ;repeat: true">
                            @foreach ($unlockedList as $badge)
                                @php
                                    $badgeImage = $badge->iconUrl() ?: $fallbackUnlocked;
                                    $badgeXp = (int) ($badge->xp_reward ?? 0);
                                @endphp
                                <div class="card">
                                    <div class="card-media sm:aspect-[2/1.8] h-36 bg-secondery dark:bg-white/5 flex items-center justify-center">
                                        <img src="{{ $badgeImage }}" alt="{{ $badge->name }}" class="!w-auto !h-24 object-contain">
                                        <div class="card-overly"></div>
                                    </div>
                                    <div class="card-body">
                                        <h4 class="card-title text-sm truncate">{{ $badge->name }}</h4>
                                        <p class="card-text">{{ $badgeXp }} XP</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    @forelse ($quests as $quest)
                        @php
                            $progress = $progressByQuestId->get($quest->id);
                            $target = max(1, (int) $quest->target_count);
                            $count = min((int) ($progress?->progress_count ?? 0), $target);
                            $percent = (int) min(100, round(($count / $target) * 100));
                            $done = filled($progress?->completed_at);
                            $questIcon = $quest->iconUrl();
                            $questBadgeImage = $questIcon ?: ($done ? asset('assets/vikinger/img/quest/completedq-b.png') : asset('assets/vikinger/img/quest/openq-b.png'));
                            $coverImage = $questCovers[($loop->iteration - 1) % count($questCovers)];
                        @endphp
                        <div class="card-list mb-4">
                            <div class="card-list-media md:h-full bg-secondery dark:bg-white/5">
                                <img src="{{ $coverImage }}" alt="{{ $quest->name }}" class="shadow">
                                <img src="{{ $questBadgeImage }}" class="!w-14 !h-14 absolute !top-1/2 !left-1/2 -translate-x-1/2 -translate-y-1/2 object-contain" alt="{{ $quest->name }}">
                            </div>
                            <div class="card-list-body">
                                <h3 class="card-list-title">{{ $quest->name }}</h3>
                                <p class="card-list-text">{{ $quest->description ?: __('ui.gamification_quest_default_text') }}</p>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-secondery dark:bg-white/10">
                                    <div class="h-full rounded-full bg-blue-600" style="width: {{ $percent }}%"></div>
                                </div>
                                <div class="card-list-info text-xs mt-2">
                                    <div>{{ $count }} / {{ $target }}</div>
                                    <div>·</div>
                                    <div>{{ $percent }}% {{ __('ui.gamification_completed') }}</div>
                                    <div>·</div>
                                    <div>{{ (int) $quest->xp_reward }} XP</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="card p-8 text-center">
                            <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.gamification_no_quests_title') }}</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.gamification_no_quests_text') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="2xl:w-[380px] lg:w-[330px] w-full">
        <div class="lg:space-y-6 space-y-4 lg:pb-8 max-lg:grid sm:grid-cols-2 max-lg:gap-6" uk-sticky="media: 1024; end: #js-oversized; offset: 80">
            <div class="box p-5 px-6">
                <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.gamification_sidebar_progress_title') }}</h3>
                <div class="mt-5 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10"><ion-icon name="stats-chart-outline" class="text-xl"></ion-icon></div>
                            <div>
                                <p class="text-sm font-semibold text-black dark:text-white">{{ __('ui.gamification_level') }} {{ $user->level }}</p>
                                <p class="text-xs text-gray-500">{{ number_format((int) $user->xp_total, 0, ',', '.') }} XP</p>
                            </div>
                        </div>
                        <strong class="text-sm text-blue-600">{{ $levelProgress }}%</strong>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-secondery dark:bg-white/10"><div class="h-full rounded-full bg-blue-600" style="width: {{ $levelProgress }}%"></div></div>
                    <div class="grid grid-cols-2 gap-2 text-center text-sm">
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5"><strong class="block text-lg text-black dark:text-white">{{ $profileCompletion }}%</strong><span class="text-xs text-gray-500">{{ __('ui.gamification_profile') }}</span></div>
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5"><strong class="block text-lg text-black dark:text-white">{{ $completedQuestsCount }}/{{ $questsCount }}</strong><span class="text-xs text-gray-500">{{ __('ui.gamification_quests') }}</span></div>
                    </div>
                </div>
            </div>

            <div class="box p-5 px-6">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h3 class="font-bold text-base">{{ __('ui.gamification_sidebar_top_badges') }}</h3>
                    <span class="text-sm text-blue-500">{{ $unlockedBadgesCount }}/{{ $availableBadgesCount }}</span>
                </div>
                <div class="side-list">
                    @forelse ($topBadges as $badge)
                        @php
                            $isUnlocked = $unlockedBadges->has($badge->id);
                            $badgeImage = $badge->iconUrl() ?: ($isUnlocked ? $fallbackPreview : $fallbackLockedPreview);
                        @endphp
                        <div class="side-list-item">
                            <img src="{{ $badgeImage }}" alt="{{ $badge->name }}" class="side-list-image rounded-md object-contain bg-secondery p-1 dark:bg-white/5 {{ $isUnlocked ? '' : 'grayscale opacity-70' }}">
                            <div class="flex-1 min-w-0">
                                <h4 class="side-list-title truncate">{{ $badge->name }}</h4>
                                <div class="side-list-info">{{ $isUnlocked ? __('ui.gamification_unlocked') : __('ui.gamification_locked') }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="mt-4 text-sm text-gray-500 dark:text-white/70">{{ __('ui.gamification_no_badges_text') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="box p-5 px-6">
                <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.gamification_sidebar_recent_xp') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($recentEvents->take(6) as $event)
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondery text-sm font-bold text-blue-600 dark:bg-white/5">+{{ (int) $event->points }}</div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-black dark:text-white line-clamp-1">{{ $event->description ?? $event->action }}</p>
                                <p class="text-xs text-gray-500">{{ $event->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.gamification_no_xp_text') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
