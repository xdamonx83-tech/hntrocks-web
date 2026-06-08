@extends('themes.socialite.layouts.app')

@section('title', $post->title . ' · Team-LFG')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post->body ?: __('ui.team_lfg_no_description')), 150))

@php
    $author = $post->user;
    $team = $post->team;
    $viewer = auth()->user();
    $tags = collect($post->displayTags())->filter()->values();
    $description = filled($post->body) ? $post->body : __('ui.team_lfg_no_description');
    $canReportTeamLfg = ! $canManage;
    $profileUrl = function ($user) {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };
    $heroImage = $team?->coverUrl() ?: $author->coverUrl();
    $heroAvatar = $team?->avatarUrl() ?: $author->avatarUrl();
    $heroName = $team?->name ?: $author->name;
@endphp

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
    @endif

    <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 overflow-hidden mb-6">
        <div class="relative h-56 bg-slate-100 dark:bg-dark3 overflow-hidden">
            <img src="{{ $heroImage }}" alt="{{ $heroName }}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-transparent"></div>
            <div class="absolute top-4 right-4 flex items-center gap-2">
                <a href="{{ route('team-lfg.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-white dark:bg-dark2/90 dark:text-white">
                    <ion-icon name="arrow-back-outline" class="text-lg"></ion-icon>
                    {{ __('ui.team_lfg_back_overview') }}
                </a>
                <a href="{{ route('team-lfg.show', ['post' => $post, 'classic_team_lfg' => 1]) }}" class="inline-flex items-center gap-2 rounded-lg bg-black/45 px-3 py-2 text-sm font-semibold text-white hover:bg-black/60">
                    <ion-icon name="open-outline" class="text-lg"></ion-icon>
                    {{ __('ui.classic') }}
                </a>
            </div>
            <div class="absolute bottom-5 left-5 right-5">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-blue-600 dark:bg-dark2 dark:text-blue-400">{{ $post->typeLabel() }}</span>
                            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white">{{ $post->statusLabel() }}</span>
                            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight">{{ $post->title }}</h1>
                    </div>
                    <div class="flex gap-2">
                        @if ($canManage)
                            <a href="{{ route('team-lfg.edit', $post) }}" class="button bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg font-semibold">{{ __('ui.team_lfg_edit_action') }}</a>
                        @elseif ($canReportTeamLfg)
                            <button class="button bg-white/95 text-gray-700 hover:bg-white px-4 py-2 rounded-lg font-semibold text-tooltip-tft" type="button" title="{{ __('ui.team_lfg_report') }}" data-title="{{ __('ui.team_lfg_report') }}" aria-label="{{ __('ui.team_lfg_report') }}" data-hh-report-open data-hh-report-type="team_lfg" data-hh-report-id="{{ $post->id }}" data-hh-report-label="{{ __('ui.team_lfg_report_label', ['title' => $post->title]) }}">
                                {{ __('ui.report_short') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="p-5 flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 dark:border-slate-700/50">
            @if ($team)
                <a href="{{ route('teams.show', $team) }}" class="flex items-center gap-3 min-w-0">
                    <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="w-12 h-12 rounded-full object-cover bg-slate-100">
                    <span class="min-w-0">
                        <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $team->name }}</span>
                        <span class="block text-sm text-gray-500 truncate">{{ __('ui.team_lfg_members_count', ['count' => $team->activeMembers->count()]) }} · {{ $team->recruitmentLabel() }}</span>
                    </span>
                </a>
            @else
                <a href="{{ $profileUrl($author) }}" class="flex items-center gap-3 min-w-0">
                    <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" class="w-12 h-12 rounded-full object-cover bg-slate-100">
                    <span class="min-w-0">
                        <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $author->name }}</span>
                        <span class="block text-sm text-gray-500 truncate">{{ '@'.$author->username }}</span>
                    </span>
                </a>
            @endif

            <div class="flex flex-wrap gap-2 text-sm">
                <span class="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-gray-700 dark:bg-dark3 dark:text-white">{{ __('ui.status') }}: {{ $post->statusLabel() }}</span>
                @if ($post->isTeamSeekingPlayers())
                    <span class="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-gray-700 dark:bg-dark3 dark:text-white">{{ __('ui.team_lfg_free') }}: {{ $post->slotsOpen() }}</span>
                @endif
                <span class="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-gray-700 dark:bg-dark3 dark:text-white">{{ $post->voiceLabel() }}</span>
            </div>
        </div>
    </article>

    <div class="lg:flex lg:items-start gap-6">
        <main class="min-w-0 flex-1 space-y-6">
            <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                <h2 class="text-xl font-bold mb-3">{{ __('ui.team_lfg_description_title') }}</h2>
                <div class="prose prose-slate max-w-none dark:prose-invert text-gray-600 dark:text-white/80 leading-7">
                    <p>{!! \App\Support\MentionRenderer::render($description) !!}</p>
                </div>
                <div class="flex flex-wrap gap-2 mt-5">
                    @forelse ($tags as $tag)
                        <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-gray-600 dark:bg-dark3 dark:text-white/80">{{ $tag }}</span>
                    @empty
                        <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-gray-600 dark:bg-dark3 dark:text-white/80">{{ __('ui.team_lfg_no_description') }}</span>
                    @endforelse
                </div>
            </article>

            @if ($post->canApplyAsUser($viewer))
                <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                    <h2 class="text-xl font-bold mb-4">{{ __('ui.team_lfg_apply_title') }}</h2>
                    <form method="post" action="{{ route('team-lfg.applications.store', $post) }}" class="space-y-3">
                        @csrf
                        <label for="message" class="block text-sm font-bold text-gray-600 dark:text-white/80">{{ __('ui.team_lfg_message_optional') }}</label>
                        <textarea id="message" name="message" rows="4" maxlength="900" class="w-full rounded-xl bg-slate-100 px-3 py-3 text-sm outline-none dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.team_lfg_apply_placeholder') }}"></textarea>
                        <button class="rounded-lg bg-blue-600 px-4 py-2 font-bold text-white" type="submit">{{ __('ui.team_lfg_apply_button') }}</button>
                    </form>
                </article>
            @elseif ($post->isPlayerSeekingTeam() && ! $canManage && ! $viewerApplication && $manageableTeams->isNotEmpty())
                <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                    <h2 class="text-xl font-bold mb-4">{{ __('ui.team_lfg_invite_title') }}</h2>
                    <form method="post" action="{{ route('team-lfg.applications.store', $post) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="team_id" class="block text-sm font-bold text-gray-600 dark:text-white/80 mb-1">{{ __('ui.team_lfg_team_title') }}</label>
                            <select id="team_id" name="team_id" required class="w-full rounded-xl bg-slate-100 px-3 py-3 text-sm outline-none dark:bg-dark3 dark:text-white">
                                @foreach ($manageableTeams as $manageableTeam)
                                    <option value="{{ $manageableTeam->id }}" @disabled($post->hasApplicationFromTeam($manageableTeam->id))>
                                        {{ $manageableTeam->name }}{{ $post->hasApplicationFromTeam($manageableTeam->id) ? ' · '.__('ui.team_lfg_team_already_invited') : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <label for="message" class="block text-sm font-bold text-gray-600 dark:text-white/80">{{ __('ui.team_lfg_message_optional') }}</label>
                        <textarea id="message" name="message" rows="4" maxlength="900" class="w-full rounded-xl bg-slate-100 px-3 py-3 text-sm outline-none dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.team_lfg_invite_placeholder') }}"></textarea>
                        <button class="rounded-lg bg-blue-600 px-4 py-2 font-bold text-white" type="submit">{{ __('ui.team_lfg_invite_button') }}</button>
                    </form>
                </article>
            @elseif ($viewerApplication)
                <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                    <h2 class="text-xl font-bold mb-3">{{ __('ui.team_lfg_your_request') }}</h2>
                    <p class="text-sm text-gray-500">{{ __('ui.status') }}: <strong>{{ $viewerApplication->statusLabel() }}</strong></p>
                    @if ($viewerApplication->team)
                        <p class="text-sm text-gray-500 mt-1">{{ __('ui.team_lfg_team_title') }}: <strong>{{ $viewerApplication->team->name }}</strong></p>
                    @endif
                    @if ($viewerApplication->message)
                        <p class="mt-3 text-gray-600 dark:text-white/75">{!! nl2br(e($viewerApplication->message)) !!}</p>
                    @endif
                </article>
            @endif

            @if ($canManage)
                <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                    <h2 class="text-xl font-bold mb-4">{{ $post->isTeamSeekingPlayers() ? __('ui.team_lfg_applications_title') : __('ui.team_lfg_invitations_title') }}</h2>
                    <div class="space-y-3">
                        @forelse ($post->applications as $application)
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-dark3">
                                <div class="flex items-start justify-between gap-3">
                                    @if ($application->team)
                                        <a href="{{ route('teams.show', $application->team) }}" class="flex items-center gap-3 min-w-0">
                                            <img src="{{ $application->team->avatarUrl() }}" alt="{{ $application->team->name }}" class="w-11 h-11 rounded-full object-cover">
                                            <span class="min-w-0">
                                                <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $application->team->name }}</span>
                                                <span class="block text-sm text-gray-500">{{ $application->statusLabel() }} · {{ __('ui.team_lfg_invited_by', ['name' => $application->user->name]) }} · {{ $application->created_at->diffForHumans() }}</span>
                                            </span>
                                        </a>
                                    @else
                                        <a href="{{ $profileUrl($application->user) }}" class="flex items-center gap-3 min-w-0">
                                            <img src="{{ $application->user->avatarUrl() }}" alt="{{ $application->user->name }}" class="w-11 h-11 rounded-full object-cover">
                                            <span class="min-w-0">
                                                <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $application->user->name }}</span>
                                                <span class="block text-sm text-gray-500">{{ $application->statusLabel() }} · {{ $application->created_at->diffForHumans() }}</span>
                                            </span>
                                        </a>
                                    @endif

                                    @if ($application->status === 'pending')
                                        <div class="flex gap-2 shrink-0">
                                            <form method="post" action="{{ route('team-lfg.applications.accept', [$post, $application]) }}">@csrf<button class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white" type="submit">{{ __('ui.team_lfg_accept') }}</button></form>
                                            <form method="post" action="{{ route('team-lfg.applications.reject', [$post, $application]) }}">@csrf<button class="rounded-lg bg-slate-200 px-3 py-2 text-sm font-bold text-gray-700 dark:bg-slate-700 dark:text-white" type="submit">{{ __('ui.team_lfg_reject') }}</button></form>
                                        </div>
                                    @endif
                                </div>
                                <p class="mt-3 text-sm text-gray-600 dark:text-white/75">{{ $application->message ?: __('ui.no_message_given') }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500">{{ $post->isTeamSeekingPlayers() ? __('ui.team_lfg_no_applications') : __('ui.team_lfg_no_invitations') }}</p>
                        @endforelse
                    </div>
                </article>
            @endif
        </main>

        <aside class="lg:w-80 shrink-0 space-y-6 max-lg:mt-6">
            <section class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                <h2 class="text-lg font-bold mb-4">{{ __('ui.team_lfg_details_title') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_type_label') }}</dt><dd class="font-bold text-right">{{ $post->typeLabel() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.status') }}</dt><dd class="font-bold text-right">{{ $post->statusLabel() }}</dd></div>
                    @if ($post->isTeamSeekingPlayers())
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_wanted') }}</dt><dd class="font-bold text-right">{{ __('ui.team_lfg_filled_count', ['filled' => $post->slots_filled, 'total' => $post->slots_total]) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_free') }}</dt><dd class="font-bold text-right">{{ $post->slotsOpen() }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_platform') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('platform', $post->platform) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_playstyle') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('playstyle', $post->playstyle) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_region') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('region', $post->region) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_language') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('language', $post->language) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_preferred_time') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('preferred_time', $post->preferred_time) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.team_lfg_experience') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('experience_level', $post->experience_level) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                </dl>
            </section>

            @if ($team)
                <section class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                    <h2 class="text-lg font-bold mb-4">{{ __('ui.team_lfg_team_title') }}</h2>
                    <a href="{{ route('teams.show', $team) }}" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 hover:bg-slate-100 dark:bg-dark3 dark:hover:bg-slate-800">
                        <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="w-12 h-12 rounded-full object-cover">
                        <span class="min-w-0">
                            <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $team->name }}</span>
                            <span class="block text-sm text-gray-500 truncate">{{ __('ui.team_lfg_members_count', ['count' => $team->activeMembers->count()]) }}</span>
                        </span>
                    </a>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
