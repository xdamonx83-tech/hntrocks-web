@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.cup_team_page_title').' · '.$cup->title)
@section('main_class', 'cup-detail-main')

@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $registrationOpen = $cup->isRegistrationOpen();
    $submissionOpen = $cup->isSubmissionOpen();
    $submissionClosedReason = $submissionOpen ? null : $cup->submissionClosedReason();
    $viewerEligibility = $viewer ? $cup->participationEligibility($viewer) : ['eligible' => false, 'messages' => [__('ui.cup_requirement_login')]];
    $coverUrl = $cup->coverUrl();
    $viewerTeamMembers = $viewerTeam ? $viewerTeam->members->where('status', 'active')->values() : collect();
    $viewerTeamSize = max(1, (int) ($cup->team_size ?: 1));
    $viewerTeamMemberCount = $viewerTeamMembers->count();
    $viewerIsTeamCaptain = $viewerTeam ? $viewerTeam->isCaptain($viewer) : false;
    $viewerTeamComplete = $viewerTeam ? $viewerTeamMemberCount >= $viewerTeamSize : false;
    $viewerTeamRosterLocked = $viewerTeam ? $viewerTeam->isRosterLocked() : false;
    $viewerTeamCanSubmit = $viewerTeam && $viewerTeam->status === 'active' && $submissionOpen && $viewerIsTeamCaptain && $viewerTeamComplete;
    $viewerTeamInviteUrl = $viewerTeam ? route('cups.teams.join', [$cup, $viewerTeam->join_token]) : null;
    $defaultTeamName = trim((string) (($viewer?->username ?: $viewer?->name) ? (($viewer?->username ?: $viewer?->name).' Team') : __('ui.cup_team_default_name')));
    $profileUrl = static function ($user): string {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };

    $finderPlatformLabels = [
        'playstation' => __('ui.cup_team_finder_platform_playstation'),
        'xbox' => __('ui.cup_team_finder_platform_xbox'),
        'flexible' => __('ui.cup_team_finder_platform_flexible'),
    ];
@endphp

@section('content')
    <div class="cup-detail-shell hnt-cup-team-page">
        @if(session('status') || $errors->any())
            <article class="cup-panel" style="margin-bottom: 18px;">
                @if($errors->any())
                    <h2>{{ __('ui.please_check') }}</h2>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                @else
                    <h2>{{ __('ui.note') }}</h2>
                    <p>{{ session('status') }}</p>
                @endif
            </article>
        @endif

        <section class="cup-hero-card hnt-cup-team-page-hero">
            <div class="cup-cover-art" style="background-image: linear-gradient(180deg, rgba(26,26,24,0.18), rgba(26,26,24,0.82)), url('{{ $coverUrl }}');">
                <div class="cup-cover-shade"></div>
                <div class="cup-cover-actions">
                    <a class="btn-create" href="{{ route('cups.show', $cup) }}">
                        <i class="ph ph-caret-left" aria-hidden="true"></i>
                        {{ __('ui.cup_team_back_to_cup') }}
                    </a>
                </div>
            </div>

            <div class="cup-hero-body">
                <div class="cup-title-block">
                    <p class="cup-meta">{{ Str::upper(__('ui.cup_team_page_kicker')) }}</p>
                    <h1>{{ __('ui.cup_team_page_title') }}</h1>
                    <div class="cup-info-line">
                        <span class="cup-status">{{ $cup->title }}</span>
                        <span>{{ $cup->modeLabel() }}</span>
                        <span>{{ __('ui.cup_team_member_slots', ['count' => $viewerTeamMemberCount, 'size' => $viewerTeamSize]) }}</span>
                    </div>
                </div>
            </div>
        </section>

        @if($viewerTeam)
            <section class="cup-content-grid hnt-cup-team-hub-grid" id="cup-team-hub">
                <article class="cup-panel hnt-cup-team-hub-panel">
                    <div class="hnt-cup-team-hub-head">
                        <div>
                            <span>{{ __('ui.cup_team_hub_kicker') }}</span>
                            <h2>{{ __('ui.cup_team_hub_title') }}</h2>
                            <p>{{ __('ui.cup_team_hub_subtitle') }}</p>
                        </div>
                        <strong>{{ $viewerTeamMemberCount }}/{{ $viewerTeamSize }}</strong>
                    </div>

                    <div class="hnt-cup-team-card">
                        <div>
                            <span>{{ __('ui.cup_your_team') }}</span>
                            <h3>{{ $viewerTeam->displayName() }}</h3>
                        </div>
                        <div class="hnt-cup-team-statuses">
                            <span>{{ $viewerTeam->statusLabel() }}</span>
                            @if($viewerTeamRosterLocked)
                                <span>{{ __('ui.cup_team_roster_locked_badge') }}</span>
                            @endif
                            <span>{{ $viewerIsTeamCaptain ? __('ui.cup_team_captain_badge') : __('ui.cup_team_member_badge') }}</span>
                        </div>
                    </div>

                    @if($viewerIsTeamCaptain || $canManage)
                        <form class="hnt-cup-team-name-form" method="post" action="{{ route('cups.teams.update', [$cup, $viewerTeam]) }}">
                            @csrf
                            @method('patch')
                            <label for="cup-team-name-edit">{{ __('ui.cup_team_rename_label') }}</label>
                            <div class="hnt-cup-team-name-row">
                                <input id="cup-team-name-edit" type="text" name="name" maxlength="100" required value="{{ old('name', $viewerTeam->displayName()) }}">
                                <button class="btn-create" type="submit">{{ __('ui.cup_team_rename_button') }}</button>
                            </div>
                            <p>{{ __('ui.cup_team_rename_hint') }}</p>
                        </form>
                    @endif

                    <div class="hnt-cup-team-members">
                        @foreach($viewerTeamMembers as $member)
                            @php
                                $memberUser = $member->user;
                                $memberName = $memberUser?->username ?: $memberUser?->name ?: __('ui.preview_hnt_hunter');
                                $memberAvatar = $memberUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                            @endphp
                            <a class="hnt-cup-team-member" href="{{ $memberUser ? $profileUrl($memberUser) : route('members.index') }}">
                                <span class="hnt-cup-team-member-avatar hnt-avatar-shell"><img src="{{ $memberAvatar }}" alt=""></span>
                                <span>
                                    <strong>{{ $memberName }}</strong>
                                    <small>{{ $member->role === 'captain' ? __('ui.cup_team_captain_badge') : __('ui.cup_team_member_badge') }}</small>
                                </span>
                            </a>
                        @endforeach
                    </div>

                    @if($viewerIsTeamCaptain && ! $viewerTeamRosterLocked && $viewerTeamInviteUrl && $viewerTeamMemberCount < $viewerTeamSize)
                        <div class="hnt-cup-team-invite">
                            <span>{{ __('ui.cup_team_invite_title') }}</span>
                            <p>{{ __('ui.cup_team_invite_hint') }}</p>
                            <input type="text" readonly value="{{ $viewerTeamInviteUrl }}" onfocus="this.select()" aria-label="{{ __('ui.cup_invite_link') }}">
                        </div>
                    @endif

                    @if($viewerTeamRosterLocked)
                        <div class="hnt-cup-team-submit-state is-locked">
                            <strong>{{ __('ui.cup_team_roster_locked_title') }}</strong>
                            <p>{{ __('ui.cup_team_roster_locked_text') }}</p>
                        </div>
                    @endif

                    @if($viewerTeam && ! $submissionOpen)
                        <div class="hnt-cup-team-submit-state">
                            <strong>{{ __('ui.cup_submit_not_open_title') }}</strong>
                            <p>{{ $submissionClosedReason }}</p>
                        </div>
                    @elseif($viewerTeamCanSubmit)
                        <div class="hnt-cup-team-submit-state is-ready">
                            <strong>{{ __('ui.cup_team_submit_ready_title') }}</strong>
                            <p>{{ __('ui.cup_team_submit_ready_text') }}</p>
                        </div>
                    @elseif(! $viewerIsTeamCaptain)
                        <div class="hnt-cup-team-submit-state">
                            <strong>{{ __('ui.cup_submit_captain_only_title') }}</strong>
                            <p>{{ __('ui.cup_submit_captain_only_text') }}</p>
                        </div>
                    @elseif(! $viewerTeamComplete)
                        <div class="hnt-cup-team-submit-state">
                            <strong>{{ __('ui.cup_submit_team_incomplete_title') }}</strong>
                            <p>{{ __('ui.cup_submit_team_incomplete_text', ['count' => $viewerTeamMemberCount, 'size' => $viewerTeamSize]) }}</p>
                        </div>
                    @endif

                    <div class="hnt-cup-team-page-actions">
                        @if($viewerTeamCanSubmit)
                            <a class="btn-create" href="{{ route('cups.show.section', [$cup, 'submit']) }}">{{ __('ui.preview_cup_submit_screenshot') }}</a>
                        @endif
                        @if(! $viewerTeamRosterLocked)
                            <form method="post" action="{{ route('cups.teams.leave', [$cup, $viewerTeam]) }}">
                                @csrf
                                <button class="btn-following" type="submit">{{ __('ui.preview_cup_leave') }}</button>
                            </form>
                        @endif
                    </div>
                </article>

                <article class="cup-panel hnt-cup-chat-panel hnt-cup-team-chat-panel" id="cup-team-chat" data-cup-team-chat-panel data-cup-team-chat-url="{{ route('cups.teams.chat.index', [$cup, $viewerTeam]) }}" data-cup-team-chat-empty-text="{{ __('ui.cup_team_chat_empty') }}">
                    <div class="hnt-cup-chat-head">
                        <div>
                            <span>{{ __('ui.cup_team_chat_kicker') }}</span>
                            <h2>{{ __('ui.cup_team_chat_title') }}</h2>
                        </div>
                        <strong data-cup-team-chat-count>{{ $viewerTeamChatMessagesCount }}</strong>
                    </div>

                    <div class="hnt-cup-chat-list" data-cup-team-chat-list>
                        @forelse($viewerTeamChatMessages as $teamChatMessage)
                            @include('themes.hnt_preview.cups.partials.chat-message', ['chatMessage' => $teamChatMessage])
                        @empty
                            <p class="hnt-cup-chat-empty" data-cup-team-chat-empty>{{ __('ui.cup_team_chat_empty') }}</p>
                        @endforelse
                    </div>

                    <div class="hnt-cup-chat-compose">
                        <form method="post" action="{{ route('cups.teams.chat.store', [$cup, $viewerTeam]) }}" data-cup-team-chat-form data-error-text="{{ __('ui.cup_team_chat_send_failed') }}">
                            @csrf
                            <span class="hnt-cup-chat-avatar hnt-avatar-shell" aria-hidden="true">
                                <img src="{{ auth()->user()?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}" alt="">
                            </span>
                            <input name="body" type="text" maxlength="1200" required placeholder="{{ __('ui.cup_team_chat_placeholder') }}" autocomplete="off" data-cup-team-chat-input>
                            <button class="btn-create" type="submit" data-loading-text="{{ __('ui.preview_loading_short') }}">{{ __('ui.cup_team_chat_send') }}</button>
                        </form>
                        <p class="hnt-cup-chat-status" data-cup-team-chat-status hidden></p>
                    </div>
                </article>

                @if($teamFinderEnabled)
                    <article class="cup-panel hnt-cup-team-finder-panel" id="cup-team-finder">
                        <div class="hnt-cup-team-hub-head">
                            <div>
                                <span>{{ __('ui.cup_team_finder_kicker') }}</span>
                                <h2>{{ __('ui.cup_team_finder_title') }}</h2>
                                <p>{{ __('ui.cup_team_finder_intro') }}</p>
                            </div>
                        </div>

                        @if($viewerTeam && ($viewerIsTeamCaptain || $canManage))
                            <form class="hnt-cup-team-finder-toggle" method="post" action="{{ route('cups.teams.recruiting', [$cup, $viewerTeam]) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="is_recruiting" value="{{ $viewerTeam->isRecruiting() ? 0 : 1 }}">
                                <div>
                                    <strong>{{ $viewerTeam->isRecruiting() ? __('ui.cup_team_recruiting_active_title') : __('ui.cup_team_recruiting_inactive_title') }}</strong>
                                    <p>{{ $viewerTeam->isRecruiting() ? __('ui.cup_team_recruiting_active_text') : __('ui.cup_team_recruiting_inactive_text') }}</p>
                                </div>
                                <button class="{{ $viewerTeam->isRecruiting() ? 'btn-following' : 'btn-create' }}" type="submit" @disabled($viewerTeamRosterLocked || $viewerTeamMemberCount >= $viewerTeamSize || ! $registrationOpen)>
                                    {{ $viewerTeam->isRecruiting() ? __('ui.cup_team_recruiting_disable') : __('ui.cup_team_recruiting_enable') }}
                                </button>
                            </form>
                        @endif

                        @if($viewer && ! $viewerTeam)
                            <div class="hnt-cup-team-finder-self">
                                @if($viewerFinderPost)
                                    <div>
                                        <strong>{{ __('ui.cup_team_finder_your_post_title') }}</strong>
                                        <p>{{ __('ui.cup_team_finder_your_post_text') }}</p>
                                        @if($viewerFinderPost->message)
                                            <blockquote>{{ $viewerFinderPost->message }}</blockquote>
                                        @endif
                                    </div>
                                    <form method="post" action="{{ route('cups.team-finder.close', $cup) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="btn-following" type="submit">{{ __('ui.cup_team_finder_close_button') }}</button>
                                    </form>
                                @elseif($registrationOpen && ($viewerEligibility['eligible'] ?? false))
                                    <form class="hnt-cup-team-finder-form" method="post" action="{{ route('cups.team-finder.store', $cup) }}">
                                        @csrf
                                        <div>
                                            <label for="cup-team-finder-platform">{{ __('ui.cup_team_finder_platform_label') }}</label>
                                            <select id="cup-team-finder-platform" name="platform">
                                                <option value="">{{ __('ui.cup_team_finder_platform_none') }}</option>
                                                <option value="playstation">{{ __('ui.cup_team_finder_platform_playstation') }}</option>
                                                <option value="xbox">{{ __('ui.cup_team_finder_platform_xbox') }}</option>
                                                <option value="flexible">{{ __('ui.cup_team_finder_platform_flexible') }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="cup-team-finder-message">{{ __('ui.cup_team_finder_message_label') }}</label>
                                            <textarea id="cup-team-finder-message" name="message" maxlength="500" rows="3" placeholder="{{ __('ui.cup_team_finder_message_placeholder') }}">{{ old('message') }}</textarea>
                                        </div>
                                        <button class="btn-create" type="submit">{{ __('ui.cup_team_finder_save_button') }}</button>
                                    </form>
                                @else
                                    <div class="hnt-cup-team-locked-note">
                                        <h3>{{ __('ui.cup_team_finder_locked_title') }}</h3>
                                        @foreach(($viewerEligibility['messages'] ?? []) as $message)
                                            <p>{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="hnt-cup-team-finder-columns">
                            <div>
                                <h3>{{ __('ui.cup_team_finder_recruiting_teams_title') }}</h3>
                                @forelse($recruitingTeams as $recruitingTeam)
                                    @php
                                        $recruitingMembers = $recruitingTeam->members->where('status', 'active')->values();
                                        $recruitingCount = $recruitingMembers->count();
                                        $recruitingSize = $recruitingTeam->requiredMembersCount();
                                        $recruitingOwner = $recruitingTeam->owner;
                                    @endphp
                                    <div class="hnt-cup-team-finder-card">
                                        <div>
                                            <strong>{{ $recruitingTeam->displayName() }}</strong>
                                            <p>{{ __('ui.cup_team_member_slots', ['count' => $recruitingCount, 'size' => $recruitingSize]) }} · {{ __('ui.cup_team_finder_captain_label', ['name' => $recruitingOwner?->username ?: $recruitingOwner?->name ?: __('ui.preview_hnt_hunter')]) }}</p>
                                        </div>
                                        @if(! $viewerTeam && $viewer && $registrationOpen && ($viewerEligibility['eligible'] ?? false))
                                            <a class="btn-create" href="{{ route('cups.teams.join', [$cup, $recruitingTeam->join_token]) }}">{{ __('ui.cup_team_finder_join_button') }}</a>
                                        @elseif($viewerTeam && (int) $viewerTeam->id === (int) $recruitingTeam->id)
                                            <span class="hnt-cup-team-finder-pill">{{ __('ui.cup_team_finder_your_team_badge') }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <p class="hnt-cup-team-finder-empty">{{ __('ui.cup_team_finder_no_recruiting_teams') }}</p>
                                @endforelse
                            </div>

                            <div>
                                <h3>{{ __('ui.cup_team_finder_players_title') }}</h3>
                                @forelse($teamFinderPosts as $finderPost)
                                    @php
                                        $finderUser = $finderPost->user;
                                        $finderName = $finderUser?->username ?: $finderUser?->name ?: __('ui.preview_hnt_hunter');
                                        $finderAvatar = $finderUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                                        $finderPlatform = $finderPost->platform ? ($finderPlatformLabels[$finderPost->platform] ?? $finderPost->platform) : __('ui.cup_team_finder_platform_none_short');
                                    @endphp
                                    <div class="hnt-cup-team-finder-player-card">
                                        <a class="hnt-cup-team-member" href="{{ $finderUser ? $profileUrl($finderUser) : route('members.index') }}">
                                            <span class="hnt-cup-team-member-avatar hnt-avatar-shell"><img src="{{ $finderAvatar }}" alt=""></span>
                                            <span>
                                                <strong>{{ $finderName }}</strong>
                                                <small>{{ $finderPlatform }}</small>
                                            </span>
                                        </a>
                                        @if($finderPost->message)
                                            <p>{{ $finderPost->message }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="hnt-cup-team-finder-empty">{{ __('ui.cup_team_finder_no_players') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @endif
            </section>
        @else
            <section class="cup-content-grid hnt-cup-team-start-grid">
                <article class="cup-panel hnt-cup-team-hub-panel">
                    <div class="hnt-cup-team-hub-head">
                        <div>
                            <span>{{ __('ui.cup_team_page_kicker') }}</span>
                            <h2>{{ __('ui.cup_team_page_empty_title') }}</h2>
                            <p>{{ __('ui.cup_team_page_empty_text') }}</p>
                        </div>
                        <strong>0/{{ $viewerTeamSize }}</strong>
                    </div>

                    @if($registrationOpen && ($viewerEligibility['eligible'] ?? false))
                        <form class="hnt-cup-team-create-form" method="post" action="{{ route('cups.teams.store', $cup) }}">
                            @csrf
                            <label for="cup-team-name">{{ __('ui.cup_team_name_label') }}</label>
                            <input id="cup-team-name" type="text" name="name" maxlength="100" required value="{{ old('name', $defaultTeamName) }}">
                            <button class="btn-create" type="submit">{{ __('ui.preview_cup_join_team') }}</button>
                        </form>
                    @else
                        <div class="hnt-cup-team-locked-note">
                            <h3>{{ __('ui.preview_cup_join_closed') }}</h3>
                            @foreach(($viewerEligibility['messages'] ?? []) as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif
                </article>

                @if($teamFinderEnabled)
                    <article class="cup-panel hnt-cup-team-finder-panel" id="cup-team-finder">
                        <div class="hnt-cup-team-hub-head">
                            <div>
                                <span>{{ __('ui.cup_team_finder_kicker') }}</span>
                                <h2>{{ __('ui.cup_team_finder_title') }}</h2>
                                <p>{{ __('ui.cup_team_finder_intro') }}</p>
                            </div>
                        </div>

                        @if($viewerTeam && ($viewerIsTeamCaptain || $canManage))
                            <form class="hnt-cup-team-finder-toggle" method="post" action="{{ route('cups.teams.recruiting', [$cup, $viewerTeam]) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="is_recruiting" value="{{ $viewerTeam->isRecruiting() ? 0 : 1 }}">
                                <div>
                                    <strong>{{ $viewerTeam->isRecruiting() ? __('ui.cup_team_recruiting_active_title') : __('ui.cup_team_recruiting_inactive_title') }}</strong>
                                    <p>{{ $viewerTeam->isRecruiting() ? __('ui.cup_team_recruiting_active_text') : __('ui.cup_team_recruiting_inactive_text') }}</p>
                                </div>
                                <button class="{{ $viewerTeam->isRecruiting() ? 'btn-following' : 'btn-create' }}" type="submit" @disabled($viewerTeamRosterLocked || $viewerTeamMemberCount >= $viewerTeamSize || ! $registrationOpen)>
                                    {{ $viewerTeam->isRecruiting() ? __('ui.cup_team_recruiting_disable') : __('ui.cup_team_recruiting_enable') }}
                                </button>
                            </form>
                        @endif

                        @if($viewer && ! $viewerTeam)
                            <div class="hnt-cup-team-finder-self">
                                @if($viewerFinderPost)
                                    <div>
                                        <strong>{{ __('ui.cup_team_finder_your_post_title') }}</strong>
                                        <p>{{ __('ui.cup_team_finder_your_post_text') }}</p>
                                        @if($viewerFinderPost->message)
                                            <blockquote>{{ $viewerFinderPost->message }}</blockquote>
                                        @endif
                                    </div>
                                    <form method="post" action="{{ route('cups.team-finder.close', $cup) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="btn-following" type="submit">{{ __('ui.cup_team_finder_close_button') }}</button>
                                    </form>
                                @elseif($registrationOpen && ($viewerEligibility['eligible'] ?? false))
                                    <form class="hnt-cup-team-finder-form" method="post" action="{{ route('cups.team-finder.store', $cup) }}">
                                        @csrf
                                        <div>
                                            <label for="cup-team-finder-platform">{{ __('ui.cup_team_finder_platform_label') }}</label>
                                            <select id="cup-team-finder-platform" name="platform">
                                                <option value="">{{ __('ui.cup_team_finder_platform_none') }}</option>
                                                <option value="playstation">{{ __('ui.cup_team_finder_platform_playstation') }}</option>
                                                <option value="xbox">{{ __('ui.cup_team_finder_platform_xbox') }}</option>
                                                <option value="flexible">{{ __('ui.cup_team_finder_platform_flexible') }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="cup-team-finder-message">{{ __('ui.cup_team_finder_message_label') }}</label>
                                            <textarea id="cup-team-finder-message" name="message" maxlength="500" rows="3" placeholder="{{ __('ui.cup_team_finder_message_placeholder') }}">{{ old('message') }}</textarea>
                                        </div>
                                        <button class="btn-create" type="submit">{{ __('ui.cup_team_finder_save_button') }}</button>
                                    </form>
                                @else
                                    <div class="hnt-cup-team-locked-note">
                                        <h3>{{ __('ui.cup_team_finder_locked_title') }}</h3>
                                        @foreach(($viewerEligibility['messages'] ?? []) as $message)
                                            <p>{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="hnt-cup-team-finder-columns">
                            <div>
                                <h3>{{ __('ui.cup_team_finder_recruiting_teams_title') }}</h3>
                                @forelse($recruitingTeams as $recruitingTeam)
                                    @php
                                        $recruitingMembers = $recruitingTeam->members->where('status', 'active')->values();
                                        $recruitingCount = $recruitingMembers->count();
                                        $recruitingSize = $recruitingTeam->requiredMembersCount();
                                        $recruitingOwner = $recruitingTeam->owner;
                                    @endphp
                                    <div class="hnt-cup-team-finder-card">
                                        <div>
                                            <strong>{{ $recruitingTeam->displayName() }}</strong>
                                            <p>{{ __('ui.cup_team_member_slots', ['count' => $recruitingCount, 'size' => $recruitingSize]) }} · {{ __('ui.cup_team_finder_captain_label', ['name' => $recruitingOwner?->username ?: $recruitingOwner?->name ?: __('ui.preview_hnt_hunter')]) }}</p>
                                        </div>
                                        @if(! $viewerTeam && $viewer && $registrationOpen && ($viewerEligibility['eligible'] ?? false))
                                            <a class="btn-create" href="{{ route('cups.teams.join', [$cup, $recruitingTeam->join_token]) }}">{{ __('ui.cup_team_finder_join_button') }}</a>
                                        @elseif($viewerTeam && (int) $viewerTeam->id === (int) $recruitingTeam->id)
                                            <span class="hnt-cup-team-finder-pill">{{ __('ui.cup_team_finder_your_team_badge') }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <p class="hnt-cup-team-finder-empty">{{ __('ui.cup_team_finder_no_recruiting_teams') }}</p>
                                @endforelse
                            </div>

                            <div>
                                <h3>{{ __('ui.cup_team_finder_players_title') }}</h3>
                                @forelse($teamFinderPosts as $finderPost)
                                    @php
                                        $finderUser = $finderPost->user;
                                        $finderName = $finderUser?->username ?: $finderUser?->name ?: __('ui.preview_hnt_hunter');
                                        $finderAvatar = $finderUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                                        $finderPlatform = $finderPost->platform ? ($finderPlatformLabels[$finderPost->platform] ?? $finderPost->platform) : __('ui.cup_team_finder_platform_none_short');
                                    @endphp
                                    <div class="hnt-cup-team-finder-player-card">
                                        <a class="hnt-cup-team-member" href="{{ $finderUser ? $profileUrl($finderUser) : route('members.index') }}">
                                            <span class="hnt-cup-team-member-avatar hnt-avatar-shell"><img src="{{ $finderAvatar }}" alt=""></span>
                                            <span>
                                                <strong>{{ $finderName }}</strong>
                                                <small>{{ $finderPlatform }}</small>
                                            </span>
                                        </a>
                                        @if($finderPost->message)
                                            <p>{{ $finderPost->message }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="hnt-cup-team-finder-empty">{{ __('ui.cup_team_finder_no_players') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @endif
            </section>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    function isNearBottom(element) {
        if (!element) return true;
        return element.scrollHeight - element.scrollTop - element.clientHeight < 96;
    }

    function scrollChatToBottom(element) {
        if (element) {
            element.scrollTop = element.scrollHeight;
        }
    }

    function setChatStatus(element, message, className) {
        if (!element) return;
        element.hidden = !message;
        element.textContent = message || '';
        element.className = 'hnt-cup-chat-status' + (className ? ' ' + className : '');
    }

    function renderTeamChatEmptyState(panel, list) {
        if (!list) return;
        const text = panel?.getAttribute('data-cup-team-chat-empty-text') || '';
        list.innerHTML = text ? '<p class="hnt-cup-chat-empty" data-cup-team-chat-empty>' + text + '</p>' : '';
    }

    async function refreshCupTeamChat(panel, options) {
        if (!panel) return;

        const url = panel.getAttribute('data-cup-team-chat-url');
        const list = panel.querySelector('[data-cup-team-chat-list]');
        const counter = panel.querySelector('[data-cup-team-chat-count]');

        if (!url || !list) return;

        const shouldStickToBottom = options?.forceScroll || isNearBottom(list);
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json().catch(function () { return {}; });
        if (!response.ok) throw new Error(data.message || '');

        if (typeof data.html === 'string') {
            if (data.html.trim()) {
                list.innerHTML = data.html;
            } else {
                renderTeamChatEmptyState(panel, list);
            }
        }

        if (counter && typeof data.count !== 'undefined') {
            counter.textContent = data.count;
        }

        if (shouldStickToBottom) {
            scrollChatToBottom(list);
        }
    }

    document.querySelectorAll('[data-cup-team-chat-panel]').forEach(function (panel) {
        const list = panel.querySelector('[data-cup-team-chat-list]');
        scrollChatToBottom(list);
        window.setInterval(function () {
            refreshCupTeamChat(panel).catch(function () {});
        }, 10000);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) return;
        document.querySelectorAll('[data-cup-team-chat-panel]').forEach(function (panel) {
            refreshCupTeamChat(panel).catch(function () {});
        });
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('[data-cup-team-chat-form]');
        if (!form) return;

        event.preventDefault();

        const panel = form.closest('[data-cup-team-chat-panel]') || document;
        const input = form.querySelector('[data-cup-team-chat-input]');
        const button = form.querySelector('[type="submit"]');
        const status = panel.querySelector('[data-cup-team-chat-status]');
        const originalButtonText = button ? button.textContent : '';
        const loadingText = button ? (button.getAttribute('data-loading-text') || originalButtonText) : '';

        if (!input || !input.value.trim()) return;

        if (button) {
            button.disabled = true;
            button.textContent = loadingText;
        }

        setChatStatus(status, '', null);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json().catch(function () { return {}; });
            if (!response.ok) throw new Error(data.message || form.dataset.errorText || '');

            form.reset();
            input.focus();
            await refreshCupTeamChat(panel, { forceScroll: true });

            if (data.message) {
                setChatStatus(status, data.message, 'is-success');
                window.setTimeout(function () { setChatStatus(status, '', null); }, 2200);
            }
        } catch (error) {
            setChatStatus(status, error.message || form.dataset.errorText || '', 'is-error');
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalButtonText;
            }
        }
    });
})();
</script>
@endpush
