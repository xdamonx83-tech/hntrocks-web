@extends('themes.socialite.layouts.app')

@section('title', $team->name . ' · hnt.rocks Team')
@section('meta_description', $team->tagline ?: __('ui.team_default_tagline'))

@php
    $activeMembers = $team->members->where('status', 'active')->values();
    $pendingMembers = $team->members->where('status', 'pending')->values();
    $teamOrganizers = $activeMembers->filter(fn ($member) => in_array($member->role, ['owner', 'officer'], true))->values();
    $teamFriendshipMap = $teamFriendshipMap ?? collect();
    $teamLfgPosts = $teamLfgPosts ?? collect();
    $teamFeedPosts = $teamFeedPosts ?? collect();
    $teamLfgOpenCount = (int) ($teamLfgOpenCount ?? 0);
    $canPostToTeam = (bool) ($canPostToTeam ?? false);
    $isRecruiting = $team->recruitment_status === 'open';
    $isPrivate = $team->visibility === 'private';
    $teamCreatedDate = $team->created_at ? $team->created_at->format('d.m.Y') : '—';
    $viewerMembership = $viewerMembership ?? null;
    $canManage = (bool) ($canManage ?? false);
    $profileUrl = function ($user) {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };
    $activeTeamSection = $activeTeamSection ?? 'timeline';
    $teamPageMembers = $teamPageMembers ?? null;
    $teamPageLfgPosts = $teamPageLfgPosts ?? null;
    $filters = $filters ?? [];
    $friendCounts = $friendCounts ?? [];
    $teamTabClass = function (string $section) use ($activeTeamSection): string {
        $base = 'inline-block py-3 leading-8 px-3.5 border-b-2';

        return $section === $activeTeamSection
            ? $base . ' border-blue-600 text-blue-600'
            : $base . ' border-transparent hover:text-blue-600';
    };
@endphp

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
    @endif

    <section class="bg-white shadow lg:rounded-b-2xl lg:-mt-10 dark:bg-dark2 overflow-hidden">
        <div class="relative overflow-hidden w-full lg:h-72 h-40 bg-slate-200 dark:bg-dark3">
            <img src="{{ $team->coverUrl() }}" alt="{{ __('ui.team_cover') }}: {{ $team->name }}" class="h-full w-full object-cover inset-0">
            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-black/60 to-transparent"></div>

            @if ($canManage)
                <div class="absolute bottom-0 right-0 m-4 z-20">
                    <a href="{{ route('teams.edit', $team) }}" class="button bg-black/20 text-white flex items-center gap-2 backdrop-blur-small">
                        <ion-icon name="create-outline" class="text-lg"></ion-icon>
                        <span>{{ __('ui.team_edit') }}</span>
                    </a>
                </div>
            @endif
        </div>

        <div class="lg:px-10 md:p-5 p-4">
            <div class="flex lg:items-center justify-between gap-5 max-md:flex-col">
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="h-20 w-20 rounded-full object-cover ring-4 ring-white shadow-xl bg-white dark:ring-slate-800 dark:bg-dark3">
                    <div class="min-w-0">
                        <h1 class="md:text-2xl text-xl font-bold text-black dark:text-white truncate">{{ $team->name }}</h1>
                        <p class="font-normal text-gray-500 mt-1 flex gap-2 flex-wrap dark:text-white/80">
                            <span>{{ $team->tagline ?: __('ui.team_default_tagline') }}</span>
                        </p>
                        <p class="text-sm text-gray-500 mt-2 flex gap-2 flex-wrap dark:text-white/80">
                            <span>{{ $team->visibilityLabel() }}</span>
                            <span>•</span>
                            <span><b class="font-semibold text-black dark:text-white">{{ $team->members_count }}</b> {{ __('ui.team_members') }}</span>
                            <span>•</span>
                            <span><b class="font-semibold text-black dark:text-white">{{ $teamLfgOpenCount }}</b> {{ __('ui.team_lfg') }}</span>
                            @if ($canManage)
                                <span>•</span>
                                <span><b class="font-semibold text-black dark:text-white">{{ $pendingMembers->count() }}</b> {{ __('ui.team_requests') }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex -space-x-4 mr-2 max-sm:hidden">
                        @foreach ($activeMembers->take(5) as $member)
                            <img src="{{ $member->user->avatarUrl() }}" alt="{{ $member->user->name }}" class="w-10 h-10 rounded-full object-cover border-4 border-white dark:border-slate-800">
                        @endforeach
                    </div>

                    @if (! $viewerMembership && $isRecruiting)
                        <form method="post" action="{{ route('teams.join', $team) }}">
                            @csrf
                            <button class="button bg-primary flex items-center gap-1 text-white py-2 px-3.5 shadow" type="submit">
                                <ion-icon name="add-outline" class="text-xl"></ion-icon>
                                <span class="text-sm">{{ __('ui.team_join_action') }}</span>
                            </button>
                        </form>
                    @elseif ($viewerMembership?->status === 'pending')
                        <span class="button bg-secondery text-gray-700 dark:bg-dark3 dark:text-white">{{ __('ui.team_request_pending') }}</span>
                    @elseif ($viewerMembership && $viewerMembership->role !== 'owner')
                        <form method="post" action="{{ route('teams.leave', $team) }}" onsubmit="return confirm('{{ __('ui.team_leave_confirm') }}')">
                            @csrf
                            <button class="button bg-secondery text-gray-700 dark:bg-dark3 dark:text-white" type="submit">{{ __('ui.team_leave') }}</button>
                        </form>
                    @endif

                    <div>
                        <button type="button" class="rounded-lg bg-secondery flex px-2.5 py-2 dark:bg-dark3">
                            <ion-icon name="ellipsis-horizontal" class="text-xl"></ion-icon>
                        </button>
                        <div class="w-[240px]" uk-dropdown="pos: bottom-right; animation: uk-animation-scale-up uk-transform-origin-top-right; animate-out: true; mode: click;offset:10">
                            <nav>
                                <a href="{{ route('teams.members', $team) }}"><ion-icon class="text-xl" name="people-outline"></ion-icon>{{ __('ui.all_members') }}</a>
                                @if ($canManage)
                                    <a href="{{ route('teams.manage') }}"><ion-icon class="text-xl" name="settings-outline"></ion-icon>{{ __('ui.manage_teams') }}</a>
                                    <a href="{{ route('teams.invitations') }}"><ion-icon class="text-xl" name="mail-open-outline"></ion-icon>{{ __('ui.team_requests') }}</a>
                                @endif
                                <a href="{{ route('teams.team-lfg', $team) }}"><ion-icon class="text-xl" name="trail-sign-outline"></ion-icon>{{ __('ui.team_lfg') }}</a>
                                @unless ($canManage)
                                    <hr>
                                    <button type="button" class="w-full text-left text-red-400 hover:!bg-red-50 dark:hover:!bg-red-500/50" data-hh-report-open data-hh-report-type="team" data-hh-report-id="{{ $team->id }}" data-hh-report-label="Team: {{ $team->name }}">
                                        <ion-icon class="text-xl" name="flag-outline"></ion-icon>{{ __('ui.report_team') }}
                                    </button>
                                @endunless
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-gray-100 px-2 dark:border-slate-700">
            <nav class="flex gap-0.5 rounded-xl overflow-hidden -mb-px text-gray-500 font-medium text-sm overflow-x-auto dark:text-white">
                <a href="{{ route('teams.show', $team) }}" class="{{ $teamTabClass('timeline') }}">{{ __('ui.team_timeline') }}</a>
                <a href="{{ route('teams.info', $team) }}" class="{{ $teamTabClass('info') }}">{{ __('ui.team_info_section') }}</a>
                <a href="{{ route('teams.members', $team) }}" class="{{ $teamTabClass('members') }}">{{ __('ui.team_members') }}</a>
                <a href="{{ route('teams.team-lfg', $team) }}" class="{{ $teamTabClass('team-lfg') }}">{{ __('ui.team_lfg') }}</a>
            </nav>

            <div class="flex items-center gap-1 text-sm p-3 bg-secondery py-2 mr-2 rounded-xl max-md:hidden dark:bg-white/5">
                <ion-icon name="search-outline" class="text-lg"></ion-icon>
                <span class="text-gray-500 dark:text-white/70">{{ $team->recruitmentLabel() }}</span>
            </div>
        </div>
    </section>

    <div class="flex gap-6 mt-8 max-lg:flex-col">
        <main class="min-w-0 flex-1 xl:space-y-6 space-y-3">
            @if ($activeTeamSection === 'timeline')
                @if ($canPostToTeam)
                    <div class="bg-white rounded-xl shadow-sm md:p-4 p-2 space-y-4 text-sm font-medium border1 dark:bg-dark2">
                        <div class="flex items-center md:gap-3 gap-1">
                            <div class="flex-1 bg-slate-100 hover:bg-opacity-80 transition-all rounded-lg cursor-pointer dark:bg-dark3" uk-toggle="target: #create-team-status">
                                <div class="py-2.5 text-center dark:text-white">{{ __('ui.team_feed_placeholder', ['name' => auth()->user()->name, 'team' => $team->name]) }}</div>
                            </div>
                            <div class="cursor-pointer hover:bg-opacity-80 p-1 px-1.5 rounded-xl transition-all bg-pink-100/60 hover:bg-pink-100 dark:bg-white/10 dark:hover:bg-white/20" uk-toggle="target: #create-team-status">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 stroke-pink-600 fill-pink-200/70" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M3.5 15.5l4.5 -4.5c.928 -.893 2.072 -.893 3 0l5 5" /><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l2.5 2.5" /></svg>
                            </div>
                            <div class="cursor-pointer hover:bg-opacity-80 p-1 px-1.5 rounded-xl transition-all bg-sky-100/60 hover:bg-sky-100 dark:bg-white/10 dark:hover:bg-white/20" uk-toggle="target: #create-team-status">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 stroke-sky-600 fill-sky-200/70" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>
                            </div>
                        </div>
                    </div>

                    <div class="hidden lg:p-20" id="create-team-status" uk-modal="">
                        <div class="uk-modal-dialog tt relative overflow-hidden mx-auto bg-white shadow-xl rounded-lg md:w-[520px] w-full dark:bg-dark2">
                            <form method="post" action="{{ route('teams.feed.store', $team) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="text-center py-4 border-b mb-0 dark:border-slate-700">
                                    <h2 class="text-sm font-medium text-black dark:text-white">{{ __('ui.team_timeline') }}</h2>
                                    <button type="button" class="button-icon absolute top-0 right-0 m-2.5 uk-modal-close" aria-label="{{ __('ui.close') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-5 mt-3 p-2">
                                    <textarea class="w-full !text-black placeholder:!text-black !bg-white !border-transparent focus:!border-transparent focus:!ring-transparent !font-normal !text-xl dark:!text-white dark:placeholder:!text-white dark:!bg-slate-800" name="body" rows="6" maxlength="5000" placeholder="{{ __('ui.team_feed_placeholder', ['name' => auth()->user()->name, 'team' => $team->name]) }}">{{ old('body') }}</textarea>
                                    @error('body')
                                        <p class="px-3 text-sm font-semibold text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="p-4 pt-2 space-y-3">
                                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-white dark:hover:bg-white/5">
                                        <span class="flex items-center gap-2"><ion-icon name="images-outline" class="text-2xl text-pink-600"></ion-icon>{{ __('ui.media') }}</span>
                                        <span class="text-xs font-medium text-slate-400">{{ __('ui.image_video') }}</span>
                                        <input type="file" name="media[]" class="hidden" multiple accept="image/*,video/mp4,video/webm,video/quicktime">
                                    </label>

                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" class="button bg-secondery text-gray-700 !w-auto px-5 dark:bg-dark3 dark:text-white uk-modal-close">{{ __('ui.cancel') }}</button>
                                        <button type="submit" class="button bg-primary text-white !w-auto px-6">{{ __('ui.post') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                @forelse ($teamFeedPosts as $post)
                    @include('themes.socialite.feed.partials.post-card', ['post' => $post])
                @empty
                    <div class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-8 text-center">
                        <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-secondery dark:bg-dark3"><ion-icon name="chatbubbles-outline" class="text-2xl"></ion-icon></div>
                        <h3 class="text-lg font-bold text-black dark:text-white">{{ __('ui.team_updates_empty_title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.team_updates_empty_text') }}</p>
                    </div>
                @endforelse
            @elseif ($activeTeamSection === 'info')
                <div class="bg-white rounded-xl shadow-sm p-6 border1 dark:bg-dark2">
                    <div class="flex items-center justify-between mb-4 gap-4">
                        <h2 class="text-xl font-bold text-black dark:text-white">{{ __('ui.team_info') }}</h2>
                        @if ($canManage)
                            <a href="{{ route('teams.edit', $team) }}" class="button bg-secondery text-gray-700 dark:bg-dark3 dark:text-white">{{ __('ui.team_edit') }}</a>
                        @endif
                    </div>

                    <p class="text-sm text-gray-600 leading-6 dark:text-white/80">{!! nl2br(e($team->description ?: __('ui.team_no_description'))) !!}</p>

                    <div class="grid md:grid-cols-2 gap-3 mt-5 text-sm">
                        <div class="rounded-xl bg-secondery p-4 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.created') }}</div><div class="font-semibold text-black dark:text-white mt-1">{{ $teamCreatedDate }}</div></div>
                        <div class="rounded-xl bg-secondery p-4 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.team_status') }}</div><div class="font-semibold text-black dark:text-white mt-1">{{ $team->recruitmentLabel() }}</div></div>
                        <div class="rounded-xl bg-secondery p-4 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.platform') }}</div><div class="font-semibold text-black dark:text-white mt-1">{{ $team->platform ?: __('ui.no_information') }}</div></div>
                        <div class="rounded-xl bg-secondery p-4 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.region') }}</div><div class="font-semibold text-black dark:text-white mt-1">{{ $team->region ?: __('ui.no_information') }}</div></div>
                        <div class="rounded-xl bg-secondery p-4 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.playstyle') }}</div><div class="font-semibold text-black dark:text-white mt-1">{{ $team->playstyle ?: __('ui.no_information') }}</div></div>
                        <div class="rounded-xl bg-secondery p-4 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.language') }}</div><div class="font-semibold text-black dark:text-white mt-1">{{ $team->language ?: __('ui.no_information') }}</div></div>
                    </div>
                </div>
            @elseif ($activeTeamSection === 'members')
                <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                    <div class="flex items-center justify-between gap-4 mb-5 max-md:flex-col max-md:items-stretch">
                        <h2 class="text-xl font-bold text-black dark:text-white">{{ __('ui.team_members') }}</h2>
                        <form method="get" action="{{ route('teams.members', $team) }}" class="flex items-center gap-2 max-md:flex-col">
                            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.team_members_search') }}" class="w-56 max-md:w-full rounded-xl bg-secondery px-4 py-2 text-sm focus:!ring-0 dark:bg-dark3">
                            <select name="role" class="rounded-xl bg-secondery px-3 py-2 text-sm focus:!ring-0 dark:bg-dark3">
                                <option value="all" @selected(($filters['role'] ?? 'all') === 'all')>{{ __('ui.all_roles') }}</option>
                                <option value="owner" @selected(($filters['role'] ?? 'all') === 'owner')>{{ __('ui.role_owner') }}</option>
                                <option value="officer" @selected(($filters['role'] ?? 'all') === 'officer')>{{ __('ui.role_officer') }}</option>
                                <option value="member" @selected(($filters['role'] ?? 'all') === 'member')>{{ __('ui.role_member') }}</option>
                            </select>
                            <button class="button bg-primary text-white !w-auto px-4" type="submit">{{ __('ui.filter_apply') }}</button>
                        </form>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3">
                        @forelse (($teamPageMembers?->getCollection() ?? $activeMembers) as $member)
                            @php
                                $memberUser = $member->user;
                                $mutualCount = $friendCounts[(int) $member->user_id] ?? null;
                            @endphp
                            <div class="rounded-xl bg-secondery p-3 flex items-center gap-3 dark:bg-dark3">
                                <a href="{{ $profileUrl($memberUser) }}"><img src="{{ $memberUser->avatarUrl() }}" alt="{{ $memberUser->name }}" class="w-12 h-12 rounded-full object-cover bg-white"></a>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $profileUrl($memberUser) }}" class="font-bold text-black dark:text-white line-clamp-1">{{ $memberUser->name }}</a>
                                    <p class="text-xs text-gray-500 truncate">{{ '@'.$memberUser->username }} · {{ $member->roleLabel() }}</p>
                                    @if ($mutualCount !== null)
                                        <p class="text-xs text-gray-500 mt-1">{{ $mutualCount }} {{ $mutualCount === 1 ? __('ui.friend_singular') : __('ui.friend_plural') }}</p>
                                    @endif
                                </div>
                                @if (auth()->id() !== $member->user_id)
                                    <a href="{{ $profileUrl($memberUser) }}" class="button-icon w-9 h-9"><ion-icon name="person-outline" class="text-lg"></ion-icon></a>
                                @endif
                            </div>
                        @empty
                            <div class="sm:col-span-2 text-center py-10">
                                <h3 class="text-lg font-bold text-black dark:text-white">{{ __('ui.team_members_empty_title') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 dark:text-white/70">{{ __('ui.team_members_empty_text') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($teamPageMembers)
                        <div class="mt-5">{{ $teamPageMembers->links() }}</div>
                    @endif
                </div>
            @elseif ($activeTeamSection === 'team-lfg')
                <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <h2 class="text-xl font-bold text-black dark:text-white">{{ __('ui.team_lfg') }}</h2>
                        @if ($canManage)
                            <a href="{{ route('team-lfg.create') }}" class="button bg-primary text-white !w-auto px-4">{{ __('ui.team_lfg_create') }}</a>
                        @endif
                    </div>

                    <div class="grid md:grid-cols-2 gap-3">
                        @forelse (($teamPageLfgPosts?->getCollection() ?? $teamLfgPosts) as $post)
                            @php
                                $tags = collect($post->displayTags())->take(4);
                            @endphp
                            <a href="{{ route('team-lfg.show', $post) }}" class="block rounded-xl bg-secondery p-4 hover:bg-gray-100 dark:bg-dark3 dark:hover:bg-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-blue-600 mb-1">{{ $post->typeLabel() }}</p>
                                        <h3 class="font-bold text-black dark:text-white line-clamp-2">{{ $post->title }}</h3>
                                        <p class="text-xs text-gray-500 mt-2">{{ $post->statusLabel() }} · {{ $post->voiceLabel() }}</p>
                                    </div>
                                    @if ($post->isTeamSeekingPlayers())
                                        <span class="rounded-full bg-white px-2 py-1 text-xs font-bold text-gray-700 dark:bg-slate-700 dark:text-white">{{ max(0, (int) $post->slotsOpen()) }}/{{ $post->slots_total }}</span>
                                    @endif
                                </div>
                                @if ($tags->isNotEmpty())
                                    <div class="flex gap-1.5 flex-wrap mt-3">
                                        @foreach ($tags as $tag)
                                            <span class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold text-gray-500 dark:bg-slate-700 dark:text-white/80">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </a>
                        @empty
                            <div class="md:col-span-2 text-center py-10">
                                <h3 class="text-lg font-bold text-black dark:text-white">{{ __('ui.team_lfg_empty_title') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 dark:text-white/70">{{ __('ui.team_lfg_empty_text') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($teamPageLfgPosts)
                        <div class="mt-5">{{ $teamPageLfgPosts->links() }}</div>
                    @endif
                </div>
            @endif
        </main>

        <aside class="w-72 shrink-0 space-y-4 max-lg:w-full">
            @if ($activeTeamSection !== 'info')
                <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-black dark:text-white">{{ __('ui.team_info') }}</h3>
                        <a href="{{ route('teams.info', $team) }}" class="text-sm text-blue-600">{{ __('ui.team_info_section') }}</a>
                    </div>
                    <p class="text-sm text-gray-600 leading-6 dark:text-white/80 line-clamp-4">{!! nl2br(e($team->description ?: __('ui.team_no_description'))) !!}</p>

                    <div class="grid grid-cols-2 gap-2 mt-4 text-sm">
                        <div class="rounded-lg bg-secondery p-3 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.created') }}</div><div class="font-semibold text-black dark:text-white">{{ $teamCreatedDate }}</div></div>
                        <div class="rounded-lg bg-secondery p-3 dark:bg-dark3"><div class="text-xs text-gray-500">{{ __('ui.team_status') }}</div><div class="font-semibold text-black dark:text-white">{{ $team->recruitmentLabel() }}</div></div>
                    </div>
                </div>
            @endif

            @if ($activeTeamSection !== 'members')
                <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-black dark:text-white">{{ __('ui.team_members') }}</h3>
                        <a href="{{ route('teams.members', $team) }}" class="text-sm text-blue-600">{{ __('ui.all_members') }}</a>
                    </div>
                    <div class="space-y-3">
                        @forelse ($activeMembers->take(5) as $member)
                            <div class="flex items-center gap-3">
                                <a href="{{ $profileUrl($member->user) }}"><img src="{{ $member->user->avatarUrl() }}" alt="{{ $member->user->name }}" class="w-10 h-10 rounded-full object-cover"></a>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $profileUrl($member->user) }}" class="font-semibold text-black dark:text-white line-clamp-1">{{ $member->user->name }}</a>
                                    <p class="text-xs text-gray-500 truncate">{{ '@'.$member->user->username }} · {{ $member->roleLabel() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.team_active_members_empty') }}</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($activeTeamSection !== 'team-lfg')
                <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-black dark:text-white">{{ __('ui.team_lfg') }}</h3>
                        <a href="{{ route('teams.team-lfg', $team) }}" class="text-sm text-blue-600">{{ __('ui.team_lfg') }}</a>
                    </div>
                    <div class="space-y-3">
                        @forelse ($teamLfgPosts as $post)
                            @php
                                $tags = collect($post->displayTags())->take(2);
                            @endphp
                            <a href="{{ route('team-lfg.show', $post) }}" class="block rounded-xl bg-secondery p-3 hover:bg-gray-100 dark:bg-dark3 dark:hover:bg-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="font-semibold text-black dark:text-white line-clamp-1">{{ $post->title }}</h4>
                                        <p class="text-xs text-gray-500 mt-1">{{ $post->statusLabel() }} · {{ $post->voiceLabel() }}</p>
                                    </div>
                                    @if ($post->isTeamSeekingPlayers())
                                        <span class="rounded-full bg-white px-2 py-1 text-xs font-bold text-gray-700 dark:bg-slate-700 dark:text-white">{{ max(0, (int) $post->slotsOpen()) }}/{{ $post->slots_total }}</span>
                                    @endif
                                </div>
                                @if ($tags->isNotEmpty())
                                    <div class="flex gap-1.5 flex-wrap mt-2">
                                        @foreach ($tags as $tag)
                                            <span class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold text-gray-500 dark:bg-slate-700 dark:text-white/80">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.team_lfg_empty_text') }}</p>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                <h3 class="text-base font-bold text-black dark:text-white mb-3">{{ __('ui.team_rules') }}</h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-white/80">
                    <li class="flex gap-2"><ion-icon name="checkmark-circle-outline" class="text-lg text-blue-600"></ion-icon><span>{{ __('ui.team_rule_respect') }}</span></li>
                    <li class="flex gap-2"><ion-icon name="checkmark-circle-outline" class="text-lg text-blue-600"></ion-icon><span>{{ __('ui.team_rule_no_hate') }}</span></li>
                    <li class="flex gap-2"><ion-icon name="checkmark-circle-outline" class="text-lg text-blue-600"></ion-icon><span>{{ __('ui.team_rule_team_first') }}</span></li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
