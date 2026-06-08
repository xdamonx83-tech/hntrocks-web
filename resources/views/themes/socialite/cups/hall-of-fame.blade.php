@extends('themes.socialite.layouts.app')

@section('title', __('ui.hall_of_fame'))
@section('meta_description', __('ui.hof_banner_text'))

@section('content')
@php
    $placementIcons = [
        1 => asset('assets/hnt/cups/hall-of-fame/place-1.png'),
        2 => asset('assets/hnt/cups/hall-of-fame/place-2.png'),
        3 => asset('assets/hnt/cups/hall-of-fame/place-3.png'),
    ];

    $rankLabel = static function (int $rank): string {
        return match ($rank) {
            1 => __('ui.hof_rank_champion'),
            2 => __('ui.hof_rank_second_place'),
            3 => __('ui.hof_rank_third_place'),
            default => __('ui.hof_rank_top_three'),
        };
    };

    $rankOrder = [2 => 1, 1 => 2, 3 => 3];

    $podiumEntries = collect();
    foreach ($hallCups as $hallEntry) {
        $cup = $hallEntry['cup'];
        foreach ($hallEntry['topThree'] as $index => $team) {
            $podiumEntries->push([
                'cup' => $cup,
                'team' => $team,
                'rank' => $index + 1,
            ]);
        }
    }

    $featuredPodiumEntries = $podiumEntries
        ->take(3)
        ->sortBy(fn ($entry) => $rankOrder[(int) $entry['rank']] ?? 99)
        ->values();
@endphp

<div class="max-w-6xl mx-auto max-lg:px-4">
    <div class="lg:py-16 py-10">
        <div class="text-center">
            <ion-icon name="trophy" class="text-5xl mb-6 text-amber-500 opacity-80"></ion-icon>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-white/70">{{ __('ui.cups') }}</p>
            <h1 class="lg:text-5xl lg:font-bold md:text-3xl text-2xl font-semibold bg-gradient-to-tr from-amber-500 to-red-600 bg-clip-text !text-transparent leading-relaxed">{{ __('ui.hof_socialite_title') }}</h1>
            <p class="max-w-2xl mx-auto text-sm text-gray-500 mt-3 dark:text-white/80">{{ __('ui.hof_banner_text') }}</p>
        </div>

        <div class="grid md:grid-cols-3 grid-cols-1 gap-5 max-w-2xl mx-auto lg:mt-12 mt-8">
            <div class="relative p-4 bg-white shadow-sm rounded-xl dark:bg-dark3">
                <div class="mb-3 text-sm text-gray-500 dark:text-white/70">{{ __('ui.hof_stat_cups') }}</div>
                <h2 class="text-3xl font-bold text-black dark:text-white">{{ $hallCups->count() }}</h2>
            </div>
            <div class="relative p-4 bg-white shadow-sm rounded-xl dark:bg-dark3">
                <div class="mb-3 text-sm text-gray-500 dark:text-white/70">{{ __('ui.hof_stat_winners') }}</div>
                <h2 class="text-3xl font-bold text-black dark:text-white">{{ $winnerCount }}</h2>
            </div>
            <div class="relative p-4 bg-white shadow-sm rounded-xl dark:bg-dark3">
                <div class="mb-3 text-sm text-gray-500 dark:text-white/70">{{ __('ui.hof_stat_finalists') }}</div>
                <h2 class="text-3xl font-bold text-black dark:text-white">{{ $finalistCount }}</h2>
            </div>
        </div>

        @if ($hallCups->isEmpty())
            <div class="md:p-8 p-5 bg-white shadow-sm rounded-xl mt-10 text-center dark:bg-dark3">
                <h2 class="text-xl font-bold text-black dark:text-white">{{ __('ui.hof_empty_title') }}</h2>
                <p class="text-sm text-gray-500 mt-3 dark:text-white/80">{{ __('ui.hof_empty_text') }}</p>
                <a href="{{ route('cups.index') }}" class="button bg-primary text-white !w-auto px-5 mt-5">{{ __('ui.cup_index_title') }}</a>
            </div>
        @else
            <div class="flex max-md:flex-col items-end justify-center gap-5 lg:mt-14 mt-10">
                @foreach ($featuredPodiumEntries as $entry)
                    @php
                        /** @var \App\Models\Cup $cup */
                        $cup = $entry['cup'];
                        $team = $entry['team'];
                        $rank = (int) $entry['rank'];
                        $player = $team->owner;
                        $soloCup = $cup->isSoloLeaderboard();
                        $displayName = $soloCup ? $team->displayName() : $team->name;
                        $profileUrl = $player ? route('profile.public', $player) : route('cups.show', $cup);
                        $isChampion = $rank === 1;
                    @endphp

                    <div class="relative p-4 bg-white shadow-sm rounded-xl dark:bg-dark3" style="flex: {{ $isChampion ? '1.16' : '1' }} 1 0; max-width: {{ $isChampion ? '390px' : '330px' }}; {{ $isChampion ? 'margin-bottom: 18px; box-shadow: 0 18px 40px rgba(15, 23, 42, .10);' : '' }}">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-white/70">#{{ $rank }} · {{ $rankLabel($rank) }}</div>
                                <h2 class="mt-1 text-lg font-bold text-black dark:text-white truncate">{{ $displayName }}</h2>
                            </div>
                            <a href="{{ $profileUrl }}" class="shrink-0">
                                <img src="{{ $player?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt="{{ $player?->name ?? $displayName }}" class="rounded-full object-cover" style="width: {{ $isChampion ? '48px' : '42px' }}; height: {{ $isChampion ? '48px' : '42px' }};">
                            </a>
                        </div>

                        <div class="mt-4 rounded-xl bg-secondery dark:bg-white/5 overflow-hidden" style="height: {{ $isChampion ? '178px' : '142px' }}; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ $placementIcons[$rank] ?? $placementIcons[3] }}" alt="{{ $rankLabel($rank) }}" style="max-width: 100%; max-height: 100%; object-fit: contain; padding: {{ $isChampion ? '10px' : '12px' }};">
                        </div>

                        <a href="{{ route('cups.show', $cup) }}" class="block mt-4 text-sm font-semibold text-blue-600 truncate">{{ $cup->title }}</a>
                        <p class="mt-1 text-xs text-gray-500 dark:text-white/70">{{ __('ui.hof_score_line', ['points' => $team->points_total, 'kills' => $team->kills_total, 'tokens' => $team->bounty_tokens_total]) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="md:p-8 p-5 bg-white shadow-sm rounded-xl mt-8 dark:bg-dark3">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-black dark:text-white">{{ __('ui.hof_socialite_archive_title') }}</h2>
                        <p class="text-sm text-gray-500 mt-1 dark:text-white/80">{{ __('ui.hof_socialite_archive_text') }}</p>
                    </div>
                    <a href="{{ route('cups.index') }}" class="text-sm font-semibold text-blue-600 shrink-0">{{ __('ui.cups') }}</a>
                </div>

                <div class="grid md:grid-cols-2 grid-cols-1 gap-5 mt-8">
                    <div class="flex gap-5 max-md:items-center max-md:flex-col">
                        <ion-icon name="shield-checkmark" class="flex shrink-0 p-2 text-2xl rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/20"></ion-icon>
                        <div>
                            <h3 class="text-black text-base font-medium dark:text-white">{{ __('ui.hof_verification_notice_title') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-white/80">{{ __('ui.hof_verification_notice_text') }}</p>
                        </div>
                    </div>

                    <div class="flex gap-5 max-md:items-center max-md:flex-col">
                        <ion-icon name="podium" class="flex shrink-0 p-2 text-2xl rounded-full bg-red-100 text-red-600 dark:bg-red-500/20"></ion-icon>
                        <div>
                            <h3 class="text-black text-base font-medium dark:text-white">{{ __('ui.hof_socialite_top_title') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-white/80">{{ __('ui.hof_final_leaderboard_text') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-5">
                @foreach ($hallCups as $hallEntry)
                    @php
                        /** @var \App\Models\Cup $cup */
                        $cup = $hallEntry['cup'];
                        $topThree = $hallEntry['topThree'];
                        $topFive = $hallEntry['topFive'];
                        $soloCup = $cup->isSoloLeaderboard();
                        $entryLabel = $soloCup ? __('ui.cup_table_player') : __('ui.cup_table_team');
                        $cupPeriod = $cup->ends_at
                            ? $cup->ends_at->translatedFormat('d.m.Y')
                            : ($cup->starts_at ? $cup->starts_at->translatedFormat('d.m.Y') : __('ui.cup_open'));
                        $archivePodium = collect($topThree)
                            ->values()
                            ->map(fn ($team, $index) => ['team' => $team, 'rank' => $index + 1])
                            ->sortBy(fn ($entry) => $rankOrder[(int) $entry['rank']] ?? 99)
                            ->values();
                    @endphp

                    <article class="bg-white shadow-sm rounded-xl overflow-hidden dark:bg-dark3">
                        <div class="relative bg-secondery dark:bg-white/5" style="position: relative; height: 220px; overflow: hidden;">
                            <img src="{{ $cup->coverUrl() }}" alt="{{ $cup->title }}" style="display: block; width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.72), rgba(0,0,0,.16), rgba(0,0,0,.05)); z-index: 1;"></div>
                            <div style="position: absolute; left: 24px; right: 24px; bottom: 22px; z-index: 2; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px;">
                                <div style="min-width: 0;">
                                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(255,255,255,.82);">{{ __('ui.hof_cup_subline', ['date' => $cupPeriod, 'mode' => $cup->modeLabel()]) }}</p>
                                    <h2 class="mt-1 text-2xl font-bold" style="color: #fff; line-height: 1.15;">{{ $cup->title }}</h2>
                                </div>
                                <a href="{{ route('cups.show', $cup) }}" class="button bg-white/90 text-black !w-auto px-4 shrink-0">{{ __('ui.hof_view_cup') }}</a>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="flex max-md:flex-col items-end justify-center gap-4">
                                @forelse ($archivePodium as $entry)
                                    @php
                                        $team = $entry['team'];
                                        $rank = (int) $entry['rank'];
                                        $player = $team->owner;
                                        $displayName = $soloCup ? $team->displayName() : $team->name;
                                        $profileUrl = $player ? route('profile.public', $player) : route('cups.show', $cup);
                                        $isChampion = $rank === 1;
                                    @endphp

                                    <a href="{{ $profileUrl }}" class="rounded-xl bg-secondery p-3 flex items-center gap-3 dark:bg-white/5" style="flex: {{ $isChampion ? '1.18' : '1' }} 1 0; {{ $isChampion ? 'min-height: 112px;' : 'min-height: 96px;' }}">
                                        <img src="{{ $placementIcons[$rank] ?? $placementIcons[3] }}" alt="{{ $rankLabel($rank) }}" class="shrink-0" style="width: {{ $isChampion ? '82px' : '64px' }}; height: {{ $isChampion ? '82px' : '64px' }}; object-fit: contain;">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-blue-600">#{{ $rank }} · {{ $rankLabel($rank) }}</p>
                                            <p class="font-bold text-black dark:text-white truncate" style="font-size: {{ $isChampion ? '1.05rem' : '1rem' }};">{{ $displayName }}</p>
                                            <p class="text-xs text-gray-500 dark:text-white/70 truncate">{{ __('ui.hof_score_line', ['points' => $team->points_total, 'kills' => $team->kills_total, 'tokens' => $team->bounty_tokens_total]) }}</p>
                                        </div>
                                    </a>
                                @empty
                                    <div class="w-full rounded-xl bg-secondery p-4 dark:bg-white/5">
                                        <p class="font-semibold text-black dark:text-white">{{ __('ui.hof_no_winners_title') }}</p>
                                        <p class="text-sm text-gray-500 mt-1 dark:text-white/70">{{ __('ui.hof_no_winners_text') }}</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="mt-5 border-t border-slate-100 pt-4 dark:border-white/10">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <h3 class="font-bold text-black dark:text-white">{{ __('ui.hof_final_leaderboard_title') }}</h3>
                                    <span class="text-xs text-gray-500 dark:text-white/70">{{ $entryLabel }}</span>
                                </div>

                                <div class="space-y-2">
                                    @forelse ($topFive as $index => $team)
                                        <div class="flex items-center gap-3 rounded-xl bg-secondery px-3 py-2 dark:bg-white/5">
                                            <div class="text-sm font-bold text-blue-600 w-8">#{{ $index + 1 }}</div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-black dark:text-white truncate">{{ $soloCup ? $team->displayName() : $team->name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-white/70 truncate">{{ $soloCup ? __('ui.cup_player') : __('ui.cup_captain') }}: {{ $team->owner?->name ?? __('ui.cup_unknown') }}</p>
                                            </div>
                                            <div class="flex items-center gap-4 text-right text-sm font-semibold text-black dark:text-white shrink-0">
                                                <span>{{ $team->points_total }} P</span>
                                                <span>{{ $team->bounty_tokens_total }} T</span>
                                                <span>{{ $team->kills_total }} K</span>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.hof_no_finalists') }}</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
