@extends('themes.socialite.layouts.app')

@section('title', $cup->title.' · '.__('ui.cup_detail_title_suffix'))
@section('meta_description', $cup->displaySummary())
@section('canonical_url', route('cups.show', $cup))
@section('og_type', 'event')
@section('og_image', $cup->coverUrl())
@section('robots', ($activeSection ?? 'overview') === 'overview' ? 'index,follow' : 'noindex,follow')

@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $soloCup = $cup->isSoloLeaderboard();
    $bayouBloodCup = $cup->isBayouBloodCup();
    $activeTeams = $cup->teams->where('status', 'active')->values();
    $teamCount = $activeTeams->count();
    $teamLimit = $cup->participantLimit();
    $participantCount = $soloCup ? $teamCount : $cup->teams->sum(fn ($team) => $team->members->where('status', 'active')->count());
    $entryLabel = $soloCup ? __('ui.cup_participants') : __('ui.teams');
    $entryTableLabel = $soloCup ? __('ui.cup_table_player') : __('ui.cup_table_team');
    $submissionCount = $cup->submissions->count();
    $visibleSubmissions = $canManage
        ? $cup->submissions
        : ($viewerTeam ? $cup->submissions->where('cup_team_id', $viewerTeam->id) : collect());
    $visibleSubmissionCount = $visibleSubmissions->count();
    $pendingSubmissionCount = $cup->submissions->whereIn('status', ['pending', 'review_required'])->count();
    $approvedSubmissionCount = $cup->submissions->whereIn('status', ['processed', 'approved', 'approved_manual'])->count();
    $invalidSubmissionCount = $cup->submissions->whereIn('status', ['invalid', 'rejected', 'rejected_manual'])->count();
    $cupStart = $cup->starts_at ? $cup->starts_at->translatedFormat('d.m.Y H:i') : __('ui.cup_open');
    $cupEnd = $cup->ends_at ? $cup->ends_at->translatedFormat('d.m.Y H:i') : __('ui.cup_open');
    $registrationOpen = $cup->isRegistrationOpen();
    $viewerTeamActive = $viewerTeam && $viewerTeam->status === 'active';
    $summary = $cup->displaySummary();
    $description = $cup->displayDescription();
    $rules = $cup->displayRules();
    $scoringRules = $cup->displayScoringRules();
    $prizeRows = $cup->prizeRows();
    $prizeNotes = $cup->prizeNotes();
    $cupUploadLimitMb = max(1, (int) ceil(((int) config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)) / 1024));
    $cupCooldownMinutes = max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30));
    $rulesSummaryRows = $cup->rulesSummary();
    $viewerEligibility = auth()->check() ? $cup->participationEligibility(auth()->user()) : ['eligible' => false, 'messages' => [__('ui.cup_requirement_login')]];
    $maxSubmissionsPerParticipant = $cup->maxSubmissionsPerParticipant();
    $maxScoredSubmissionsPerParticipant = $cup->maxScoredSubmissionsPerParticipant();
    $usedViewerUploads = $viewerTeam ? $cup->submissions->where('cup_team_id', $viewerTeam->id)->count() : 0;
    $coverUrl = $cup->coverUrl();
    $dateSource = $cup->starts_at ?: $cup->registration_closes_at ?: $cup->created_at;
    $dateDay = $dateSource ? $dateSource->format('d') : now()->format('d');
    $dateMonth = $dateSource ? Str::upper($dateSource->translatedFormat('M')) : Str::upper(now()->translatedFormat('M'));
    $dateLine = $cup->starts_at
        ? $cup->starts_at->translatedFormat('D d.m.Y · H:i')
        : ($cup->registration_closes_at ? __('ui.cup_registration_closes_at_short', ['date' => $cup->registration_closes_at->translatedFormat('d.m.Y')]) : $cup->statusLabel());
    $locationLine = collect([$cup->platform ?: __('ui.cup_platform_open'), $cup->region ?: __('ui.cup_region_open')])->filter()->implode(' · ');
    $countdownTarget = null;
    $countdownTitle = null;
    if ($cup->starts_at && $cup->starts_at->isFuture()) {
        $countdownTarget = $cup->starts_at;
        $countdownTitle = __('ui.cup_start');
    } elseif ($cup->ends_at && $cup->ends_at->isFuture()) {
        $countdownTarget = $cup->ends_at;
        $countdownTitle = __('ui.cup_end');
    }
    $owner = $cup->owner;
    $ownerUrl = $owner ? route('profile.public', $owner) : route('cups.index');
    $ownerAvatar = $owner?->avatarUrl() ?: asset('assets/socialite/images/avatars/avatar-4.jpg');
    $topParticipants = $activeTeams->take(5);
    $activeSection = in_array($activeSection ?? 'overview', ['overview', 'rules', 'prizes', 'leaderboard', 'participants', 'submit', 'submissions'], true) ? $activeSection : 'overview';
    $cupSectionUrl = fn (string $section): string => $section === 'overview'
        ? route('cups.show', $cup)
        : route('cups.show.section', [$cup, $section]);
    $navItems = [
        ['href' => $cupSectionUrl('overview'), 'label' => __('ui.cup_tab_overview'), 'active' => $activeSection === 'overview'],
        ['href' => $cupSectionUrl('rules'), 'label' => __('ui.cup_tab_rules'), 'active' => $activeSection === 'rules'],
        ['href' => $cupSectionUrl('leaderboard'), 'label' => __('ui.cup_leaderboard'), 'active' => $activeSection === 'leaderboard'],
        ['href' => $cupSectionUrl('participants'), 'label' => $entryLabel, 'active' => $activeSection === 'participants'],
        ['href' => $cupSectionUrl('prizes'), 'label' => __('ui.cup_tab_prizes'), 'active' => $activeSection === 'prizes'],
        ['href' => $cupSectionUrl('submit'), 'label' => __('ui.cup_tab_submit'), 'active' => $activeSection === 'submit'],
        ['href' => $cupSectionUrl('submissions'), 'label' => __('ui.cup_tab_submissions'), 'active' => $activeSection === 'submissions'],
    ];
    $profileUrl = function ($user) {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };
    $statusClass = match ($cup->status) {
        'active' => 'text-teal-600',
        'planned' => 'text-blue-600',
        'finished' => 'text-rose-600',
        default => 'text-gray-500',
    };
    $inputClass = 'w-full rounded-xl bg-secondery !border-0 !text-sm !text-black dark:!bg-white/5 dark:!text-white';
    $cupChatMessages = $cupChatMessages ?? collect();
    $cupChatMessagesCount = $cupChatMessagesCount ?? $cupChatMessages->count();
    $cupSubmitExampleImage = asset('assets/hnt/cups/cup-submission-example.webp');
@endphp

@push('head')
    @php
        $cupStructuredData = [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                array_filter([
                    '@type' => 'Event',
                    '@id' => route('cups.show', $cup) . '#event',
                    'url' => route('cups.show', $cup),
                    'name' => $cup->title,
                    'description' => \Illuminate\Support\Str::limit(strip_tags($cup->displaySummary()), 300, ''),
                    'image' => $coverUrl,
                    'eventStatus' => match ($cup->status) {
                        'finished', 'archived' => 'https://schema.org/EventCompleted',
                        'active' => 'https://schema.org/EventScheduled',
                        default => 'https://schema.org/EventScheduled',
                    },
                    'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
                    'startDate' => $cup->starts_at?->toIso8601String(),
                    'endDate' => $cup->ends_at?->toIso8601String(),
                    'mainEntityOfPage' => route('cups.show', $cup),
                    'about' => ['@type' => 'VideoGame', 'name' => 'Hunt: Showdown'],
                    'organizer' => [
                        '@type' => 'Organization',
                        'name' => 'HNT.rocks',
                        'url' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/',
                    ],
                    'location' => [
                        '@type' => 'VirtualLocation',
                        'url' => route('cups.show', $cup),
                    ],
                ], static fn ($value) => $value !== null && $value !== ''),
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => route('cups.show', $cup) . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'HNT.rocks', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.cups'), 'item' => route('cups.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $cup->title, 'item' => route('cups.show', $cup)],
                    ],
                ],
            ])),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($cupStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
@php
    $cupNoticeModal = null;

    if ($errors->any()) {
        $cupNoticeModal = [
            'type' => 'danger',
            'title' => __('ui.please_check'),
            'message' => collect($errors->all())->first(),
            'errors' => $errors->all(),
        ];
    } elseif (session('cup_submission_result')) {
        $cupSubmissionResult = session('cup_submission_result');
        $cupNoticeModal = [
            'type' => $cupSubmissionResult['type'] ?? 'info',
            'title' => $cupSubmissionResult['title'] ?? __('ui.cup_submission_processed'),
            'message' => $cupSubmissionResult['message'] ?? __('ui.cup_submission_result_text'),
            'meta' => array_values(array_filter([
                $cupSubmissionResult['status'] ?? null,
                $cupSubmissionResult['score'] ?? null,
            ])),
        ];
    } elseif (session('status')) {
        $cupNoticeModal = [
            'type' => 'success',
            'title' => __('ui.note'),
            'message' => session('status'),
        ];
    }

    $cupNoticeType = $cupNoticeModal['type'] ?? 'info';
    $cupNoticeAccent = match ($cupNoticeType) {
        'success' => 'bg-emerald-500',
        'danger' => 'bg-rose-500',
        'warning' => 'bg-amber-500',
        default => 'bg-primary',
    };
    $cupNoticeIcon = match ($cupNoticeType) {
        'success' => 'checkmark-circle-outline',
        'danger' => 'alert-circle-outline',
        'warning' => 'warning-outline',
        default => 'information-circle-outline',
    };
@endphp

<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-12">

    <section class="bg-white shadow lg:rounded-b-2xl lg:-mt-10 dark:bg-dark2 overflow-hidden">
        <div class="relative overflow-hidden lg:h-72 h-36 w-full bg-slate-200 dark:bg-dark3">
            <img src="{{ $coverUrl }}" alt="{{ __('ui.cup_cover_alt', ['title' => $cup->title]) }}" class="h-full w-full object-cover inset-0">
            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-black/60 to-transparent z-10"></div>

            <div class="absolute bottom-0 right-0 m-4 z-20">
                <div class="flex items-center gap-3">
                    <a href="{{ route('cups.index') }}" class="button bg-white/20 text-white flex items-center gap-2 backdrop-blur-small">
                        <ion-icon name="calendar-outline" class="text-lg"></ion-icon>
                        <span>{{ __('ui.cups') }}</span>
                    </a>
                    @if ($canManage)
                        <a href="{{ route('cups.edit', $cup) }}" class="button bg-black/20 text-white flex items-center gap-2 backdrop-blur-small">
                            <ion-icon name="create-outline" class="text-lg"></ion-icon>
                            <span>{{ __('ui.edit') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:px-10 md:p-5 p-3">
            <div class="flex flex-col justify-center md:-mt-20 -mt-12">
                <div class="md:w-20 md:h-20 w-12 h-12 overflow-hidden bg-white shadow-md rounded-md z-10 mb-5 dark:bg-dark3">
                    <div class="w-full md:h-5 bg-rose-500 h-3"></div>
                    <div class="grid place-items-center text-black font-semibold md:text-3xl text-lg h-full md:pb-5 pb-3 dark:text-white">{{ $dateDay }}</div>
                </div>

                <div class="flex lg:items-center justify-between max-lg:flex-col max-lg:gap-5">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-rose-600 mb-1.5">{{ $dateMonth }} · {{ $dateLine }}</p>
                        <h1 class="md:text-2xl text-base font-bold text-black dark:text-white">{{ $cup->title }}</h1>
                        <p class="font-normal text-gray-500 mt-2 flex gap-2 flex-wrap dark:text-white/80">
                            <span class="{{ $statusClass }} font-semibold">{{ $cup->statusLabel() }}</span>
                            <span>•</span>
                            <span>{{ $cup->modeLabel() }}</span>
                            <span>•</span>
                            <span>{{ $locationLine }}</span>
                        </p>
                    </div>

                    <div>
                        @if ($countdownTarget)
                            <p class="mb-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-white/60">{{ $countdownTitle }}</p>
                            <div uk-countdown="date: {{ $countdownTarget->toIso8601String() }}" class="flex gap-3 text-2xl font-semibold text-primary dark:text-white max-lg:justify-center">
                                <div class="bg-primary-soft/40 flex flex-col items-center justify-center rounded-lg w-16 h-16 lg:border-4 border-white md:shadow dark:border-slate-700"><span class="uk-countdown-days"></span><span class="inline-block text-xs">{{ __('ui.cup_countdown_days') }}</span></div>
                                <div class="bg-primary-soft/40 flex flex-col items-center justify-center rounded-lg w-16 h-16 lg:border-4 border-white md:shadow dark:border-slate-700"><span class="uk-countdown-hours"></span><span class="inline-block text-xs">{{ __('ui.cup_countdown_hours') }}</span></div>
                                <div class="bg-primary-soft/40 flex flex-col items-center justify-center rounded-lg w-16 h-16 lg:border-4 border-white md:shadow dark:border-slate-700"><span class="uk-countdown-minutes"></span><span class="inline-block text-xs">{{ __('ui.cup_countdown_minutes') }}</span></div>
                                <div class="bg-primary-soft/40 flex flex-col items-center justify-center rounded-lg w-16 h-16 lg:border-4 border-white md:shadow dark:border-slate-700"><span class="uk-countdown-seconds"></span><span class="inline-block text-xs">{{ __('ui.cup_countdown_seconds') }}</span></div>
                            </div>
                        @else
                            <div class="flex gap-3 text-2xl font-semibold text-primary dark:text-white max-lg:justify-center">
                                <div class="bg-primary-soft/40 flex flex-col items-center justify-center rounded-lg w-20 h-16 lg:border-4 border-white md:shadow dark:border-slate-700"><span>{{ $teamCount }}</span><span class="inline-block text-xs">{{ $entryLabel }}</span></div>
                                <div class="bg-primary-soft/40 flex flex-col items-center justify-center rounded-lg w-20 h-16 lg:border-4 border-white md:shadow dark:border-slate-700"><span>{{ $approvedSubmissionCount }}</span><span class="inline-block text-xs">{{ __('ui.cup_table_matches') }}</span></div>
                                <div class="bg-primary-soft/40 flex flex-col items-center justify-center rounded-lg w-20 h-16 lg:border-4 border-white md:shadow dark:border-slate-700"><span>{{ $leaderboard->max('points_total') ?? 0 }}</span><span class="inline-block text-xs">{{ __('ui.cup_table_points') }}</span></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between px-2 max-md:flex-col max-md:items-stretch max-md:gap-2 border-t border-gray-100 dark:border-slate-700">
            <div class="flex items-center gap-2 text-sm py-2 pr-1 lg:order-1 flex-wrap">
                @guest
                    @if ($registrationOpen)
                        <a href="{{ route('register') }}" class="button bg-secondery flex items-center gap-2 py-2 px-3.5 dark:bg-dark3">
                            <ion-icon name="person-add-outline" class="text-xl"></ion-icon>
                            <span class="text-sm">{{ __('ui.cup_join_login_required_action') }}</span>
                        </a>
                    @else
                        <span class="button bg-secondery flex items-center gap-2 py-2 px-3.5 dark:bg-dark3">{{ __('ui.cup_registration_closed') }}</span>
                    @endif
                @else
                    @if (! $viewerTeam && $registrationOpen && $soloCup)
                        <form method="post" action="{{ route('cups.teams.store', $cup) }}">
                            @csrf
                            <button type="submit" class="button bg-primary text-white flex items-center gap-2 py-2 px-3.5">
                                <ion-icon name="checkmark-circle-outline" class="text-xl"></ion-icon>
                                <span class="text-sm">{{ __('ui.cup_register_solo') }}</span>
                            </button>
                        </form>
                    @elseif (! $viewerTeam && $registrationOpen)
                        <a href="#cup-register" class="button bg-primary text-white flex items-center gap-2 py-2 px-3.5">
                            <ion-icon name="checkmark-circle-outline" class="text-xl"></ion-icon>
                            <span class="text-sm">{{ __('ui.cup_register_team') }}</span>
                        </a>
                    @elseif ($viewerTeam)
                        <a href="{{ route('cups.show.section', [$cup, 'submit']) }}" class="button bg-secondery flex items-center gap-2 py-2 px-3.5 dark:bg-dark3">
                            <ion-icon name="trophy-outline" class="text-xl"></ion-icon>
                            <span class="text-sm">{{ $soloCup ? __('ui.cup_your_participation') : $viewerTeam->name }}</span>
                        </a>
                    @else
                        <span class="button bg-secondery flex items-center gap-2 py-2 px-3.5 dark:bg-dark3">{{ __('ui.cup_registration_closed') }}</span>
                    @endif
                @endguest


                <a href="{{ route('hall-of-fame.index') }}" class="rounded-lg bg-secondery flex px-2.5 py-2 dark:bg-dark3" aria-label="{{ __('ui.hall_of_fame') }}">
                    <ion-icon name="ribbon-outline" class="text-xl"></ion-icon>
                </a>

                <div>
                    <button type="button" class="rounded-lg bg-secondery flex px-2.5 py-2 dark:bg-dark3" aria-label="{{ __('ui.more') }}">
                        <ion-icon name="ellipsis-horizontal" class="text-xl"></ion-icon>
                    </button>
                    <div class="w-[240px]" uk-dropdown="pos: bottom-right; animation: uk-animation-scale-up uk-transform-origin-top-right; animate-out: true; mode: click;offset:10">
                        <nav>
                            <a href="{{ route('cups.index') }}"><ion-icon class="text-xl" name="calendar-outline"></ion-icon>{{ __('ui.cups') }}</a>
                            <a href="{{ route('hall-of-fame.index') }}"><ion-icon class="text-xl" name="ribbon-outline"></ion-icon>{{ __('ui.hall_of_fame') }}</a>
                            @if ($canManage)
                                <a href="{{ route('cups.edit', $cup) }}"><ion-icon class="text-xl" name="create-outline"></ion-icon>{{ __('ui.cup_edit_action') }}</a>
                            @endif
                            @auth
                                @if ((int) $cup->owner_id !== (int) auth()->id())
                                    <button type="button" class="w-full text-left text-red-400 hover:!bg-red-50 dark:hover:!bg-red-500/50" data-hh-report-open data-hh-report-type="cup" data-hh-report-id="{{ $cup->id }}" data-hh-report-label="{{ __('ui.cup_report_label', ['title' => $cup->title]) }}">
                                        <ion-icon class="text-xl" name="flag-outline"></ion-icon>{{ __('ui.cup_report') }}
                                    </button>
                                @endif
                            @endauth
                        </nav>
                    </div>
                </div>
            </div>

            <nav class="flex w-full max-w-full min-w-0 gap-0.5 -mb-px overflow-x-auto overflow-y-hidden whitespace-nowrap text-gray-500 font-medium text-sm dark:text-white/80" style="-webkit-overflow-scrolling: touch;">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="shrink-0 inline-block py-3 leading-8 px-3.5 border-b-2 {{ $item['active'] ? 'border-blue-600 text-blue-600' : 'border-transparent hover:text-blue-600' }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </section>

    <div class="flex 2xl:gap-12 gap-10 mt-8 max-lg:flex-col" id="js-oversized">
        <div class="flex-1 space-y-4">
            @if ($activeSection === 'overview')
            <section class="box p-5 px-6 relative" id="cup-about">
                <h2 class="font-semibold text-lg text-black dark:text-white">{{ __('ui.cup_tab_overview') }}</h2>
                <div class="space-y-4 leading-7 tracking-wide mt-4 text-black text-sm dark:text-white">
                    <p class="font-semibold text-base">{{ $summary }}</p>
                    @if ($description !== '')
                        <p>{!! nl2br(e($description)) !!}</p>
                    @else
                        <p>{{ __('ui.cup_description_empty') }}</p>
                    @endif
                </div>
                <div class="grid sm:grid-cols-3 gap-3 mt-5 text-sm">
                    <div class="rounded-xl bg-secondery p-4 dark:bg-white/5"><div class="text-gray-500 dark:text-white/70">{{ __('ui.cup_info_period') }}</div><div class="mt-1 font-semibold text-black dark:text-white">{{ __('ui.cup_info_period_line', ['start' => $cupStart, 'end' => $cupEnd]) }}</div></div>
                    <div class="rounded-xl bg-secondery p-4 dark:bg-white/5"><div class="text-gray-500 dark:text-white/70">{{ __('ui.cup_info_setup') }}</div><div class="mt-1 font-semibold text-black dark:text-white">{{ $soloCup ? __('ui.cup_info_setup_solo_line', ['platform' => $cup->platform ?: __('ui.cup_platform_open'), 'language' => $cup->language ?: __('ui.cup_language_open')]) : __('ui.cup_info_setup_line', ['size' => $cup->team_size, 'platform' => $cup->platform ?: __('ui.cup_platform_open'), 'language' => $cup->language ?: __('ui.cup_language_open')]) }}</div></div>
                    <div class="rounded-xl bg-secondery p-4 dark:bg-white/5"><div class="text-gray-500 dark:text-white/70">{{ __('ui.cup_mode') }}</div><div class="mt-1 font-semibold text-black dark:text-white">{{ $cup->modeLabel() }}</div></div>
                </div>
                @if ($rulesSummaryRows !== [])
                    <div class="mt-5 rounded-xl bg-primary-soft/40 p-4 text-sm dark:bg-white/5">
                        <div class="font-semibold text-black dark:text-white">{{ __('ui.cup_rules_summary_title') }}</div>
                        <ul class="mt-3 list-disc space-y-1 pl-5 text-gray-600 dark:text-white/80">
                            @foreach ($rulesSummaryRows as $rulesSummaryRow)
                                <li>{{ $rulesSummaryRow }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section class="box p-5 px-6 relative" id="cup-chat">
                <div class="flex items-baseline justify-between gap-4 text-black dark:text-white">
                    <h2 class="font-semibold text-lg">{{ __('ui.cup_chat_title') }}</h2>
                    <span class="text-sm text-blue-500" data-cup-chat-count>{{ $cupChatMessagesCount }}</span>
                </div>

                <div class="mt-4 space-y-4" data-cup-chat-list>
                    @forelse ($cupChatMessages as $chatMessage)
                        @include('themes.socialite.cups.partials.chat-message', ['chatMessage' => $chatMessage])
                    @empty
                        <p class="text-sm text-gray-500 dark:text-white/70" data-cup-chat-empty>{{ __('ui.cup_chat_empty') }}</p>
                    @endforelse
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-700">
                    @auth
                        <form method="post" action="{{ route('cups.chat.store', $cup) }}" class="flex items-center gap-3 max-sm:flex-col max-sm:items-stretch" data-cup-chat-form data-error-text="{{ __('ui.cup_chat_send_failed') }}">
                            @csrf
                            <img src="{{ auth()->user()?->avatarUrl() ?: asset('assets/socialite/images/avatars/avatar-3.jpg') }}" alt="" class="rounded-full object-cover bg-white dark:bg-slate-800 max-sm:hidden" style="width:34px;height:34px;min-width:34px;">
                            <input class="{{ $inputClass }}" style="height:42px;" name="body" type="text" maxlength="1200" required placeholder="{{ __('ui.cup_chat_placeholder') }}" autocomplete="off">
                            <button class="button bg-secondery dark:bg-dark3" style="height:42px;padding-inline:18px;white-space:nowrap;" type="submit">{{ __('ui.cup_chat_send') }}</button>
                        </form>
                        <p class="mt-2 hidden text-sm font-medium" data-cup-chat-status></p>
                    @else
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-secondery p-4 text-sm dark:bg-white/5 max-sm:flex-col max-sm:items-start">
                            <span class="text-gray-600 dark:text-white/80">{{ __('ui.cup_chat_login_text') }}</span>
                            <a href="{{ route('login') }}" class="button bg-primary text-white">{{ __('ui.cup_chat_login_action') }}</a>
                        </div>
                    @endauth
                </div>
            </section>
            @elseif ($activeSection === 'rules')
            <section class="box p-5 px-6 relative" id="cup-rules">
                <h2 class="font-semibold text-lg text-black dark:text-white">{{ __('ui.cup_tab_rules') }}</h2>
                <div class="space-y-4 leading-7 tracking-wide mt-4 text-black text-sm dark:text-white">
                    @if ($rules !== '')
                        <p>{!! nl2br(e($rules)) !!}</p>
                    @else
                        <p>{{ __('ui.cup_rules_empty') }}</p>
                    @endif
                    <div class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                        <div class="font-semibold text-black dark:text-white">{{ __('ui.cup_info_scoring') }}</div>
                        <div class="mt-2 text-gray-600 dark:text-white/80">{!! nl2br(e($scoringRules)) !!}</div>
                    </div>
                    @if ($rulesSummaryRows !== [])
                        <div class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                            <div class="font-semibold text-black dark:text-white">{{ __('ui.cup_rules_summary_title') }}</div>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-gray-600 dark:text-white/80">
                                @foreach ($rulesSummaryRows as $rulesSummaryRow)
                                    <li>{{ $rulesSummaryRow }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>
            @elseif ($activeSection === 'prizes')
            <section class="box p-5 px-6 relative" id="cup-prizes">
                <h2 class="font-semibold text-lg text-black dark:text-white">{{ __('ui.cup_tab_prizes') }}</h2>
                <div class="grid md:grid-cols-3 gap-3 mt-4">
                    @forelse ($prizeRows as $prizeRow)
                        <div class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                            <div class="text-sm font-semibold text-black dark:text-white">{{ $prizeRow['label'] }}</div>
                            <div class="mt-2 text-sm leading-6 text-gray-600 dark:text-white/80">{!! nl2br(e($prizeRow['text'])) !!}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.cup_prizes_empty_text') }}</p>
                    @endforelse
                </div>
                @if (count($prizeNotes) > 0)
                    <div class="mt-4 space-y-3">
                        @foreach ($prizeNotes as $prizeNote)
                            <div class="rounded-xl bg-secondery p-4 text-sm dark:bg-white/5">
                                <div class="font-semibold text-black dark:text-white">{{ $prizeNote['label'] }}</div>
                                <div class="mt-2 leading-6 text-gray-600 dark:text-white/80">{!! nl2br(e($prizeNote['text'])) !!}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
            @elseif ($activeSection === 'leaderboard')
            <section class="box p-5 px-6 relative" id="cup-leaderboard">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h2 class="font-semibold text-lg">{{ __('ui.cup_leaderboard') }}</h2>
                    <span class="text-sm text-blue-500">{{ $leaderboard->count() }} {{ $entryLabel }}</span>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 dark:text-white/60">
                            <tr>
                                <th class="py-3 pr-4">#</th>
                                <th class="py-3 pr-4">{{ $entryTableLabel }}</th>
                                <th class="py-3 px-4 text-center">{{ __('ui.cup_table_points') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('ui.cup_table_token') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('ui.cup_table_kills') }}</th>
                                <th class="py-3 pl-4 text-center">{{ __('ui.cup_table_matches') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse ($leaderboard as $index => $team)
                                <tr>
                                    <td class="py-3 pr-4 font-bold text-primary">{{ $index + 1 }}</td>
                                    <td class="py-3 pr-4 min-w-[220px]">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $team->owner?->avatarUrl() ?: asset('assets/socialite/images/avatars/avatar-3.jpg') }}" alt="" class="w-10 h-10 rounded-full object-cover">
                                            <div>
                                                <div class="font-semibold text-black dark:text-white">{{ $soloCup ? $team->displayName() : $team->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-white/70">{{ $soloCup ? __('ui.cup_player') : __('ui.cup_captain') }}: {{ $team->owner?->name ?? __('ui.cup_unknown') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-semibold text-black dark:text-white">{{ $team->points_total }}</td>
                                    <td class="py-3 px-4 text-center">{{ $team->bounty_tokens_total }}</td>
                                    <td class="py-3 px-4 text-center">{{ $team->kills_total }}</td>
                                    <td class="py-3 pl-4 text-center">{{ $team->submissions_approved_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-gray-500 dark:text-white/70">{{ $soloCup ? __('ui.cup_no_participants_registered') : __('ui.cup_no_teams_registered') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            @elseif ($activeSection === 'participants')
            <section class="box p-5 px-6 relative" id="cup-participants">
                <h2 class="font-semibold text-lg text-black dark:text-white">{{ $entryLabel }}</h2>
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse (($canManage ? $cup->teams : $activeTeams) as $team)
                        <article class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-center justify-between gap-4 max-sm:flex-col max-sm:items-start">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <img src="{{ $team->owner?->avatarUrl() ?: asset('assets/socialite/images/avatars/avatar-3.jpg') }}" alt="" class="rounded-full object-cover bg-white dark:bg-slate-800" style="width:44px;height:44px;min-width:44px;">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-black dark:text-white truncate">{{ $soloCup ? $team->displayName() : $team->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-white/70 truncate">{{ $soloCup ? __('ui.cup_registered_player') : $team->statusLabel().' · '.__('ui.cup_captain').': '.($team->owner?->name ?? __('ui.cup_unknown')) }}</div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-4 text-center text-xs text-gray-500 dark:text-white/70 max-sm:w-full max-sm:justify-start">
                                    <div style="min-width:46px;"><strong class="block text-sm text-black dark:text-white">{{ $team->points_total }}</strong>{{ __('ui.cup_table_points') }}</div>
                                    <div style="min-width:46px;"><strong class="block text-sm text-black dark:text-white">{{ $team->bounty_tokens_total }}</strong>{{ __('ui.cup_table_token') }}</div>
                                    <div style="min-width:46px;"><strong class="block text-sm text-black dark:text-white">{{ $team->kills_total }}</strong>{{ __('ui.cup_table_kills') }}</div>
                                    <div style="min-width:54px;"><strong class="block text-sm text-black dark:text-white">{{ $soloCup ? $team->statusLabel() : (($team->members ?? collect())->where('status', 'active')->count()).'/'.$cup->team_size }}</strong>{{ $soloCup ? __('ui.status') : __('ui.cup_table_players') }}</div>
                                </div>
                            </div>
                            @if ($canManage)
                                <div class="mt-3 border-t border-white/60 pt-3 dark:border-slate-700">
                                    @if ($team->status === 'disqualified')
                                        <div class="flex items-center justify-between gap-3 max-sm:flex-col max-sm:items-start">
                                            <p class="text-xs font-medium text-rose-500">{{ __('ui.cup_disqualified_note', ['reason' => $team->disqualification_reason ?: __('ui.cup_no_reason_given')]) }}</p>
                                            <form method="post" action="{{ route('cups.teams.reinstate', [$cup, $team]) }}" onsubmit="return confirm('{{ __('ui.cup_reinstate_confirm') }}')">
                                                @csrf
                                                <button class="button bg-primary text-white" style="height:36px;padding-inline:16px;" type="submit">{{ __('ui.cup_reinstate') }}</button>
                                            </form>
                                        </div>
                                    @else
                                        <form method="post" action="{{ route('cups.teams.disqualify', [$cup, $team]) }}" class="flex items-center gap-2 max-sm:flex-col max-sm:items-stretch" onsubmit="return confirm('{{ __('ui.cup_disqualify_confirm') }}')">
                                            @csrf
                                            <input class="{{ $inputClass }} text-sm" style="height:38px;" name="reason" type="text" maxlength="1200" placeholder="{{ __('ui.cup_disqualify_reason_placeholder') }}">
                                            <button class="button bg-secondery dark:bg-dark3" style="height:38px;padding-inline:16px;white-space:nowrap;" type="submit">{{ __('ui.cup_disqualify') }}</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-white/70">{{ $soloCup ? __('ui.cup_no_participants') : __('ui.cup_no_teams') }}</p>
                    @endforelse
                </div>
            </section>
            @elseif ($activeSection === 'submit')
            <section class="box p-5 px-6 relative" id="cup-submit">
                <h2 class="font-semibold text-lg text-black dark:text-white">{{ __('ui.cup_submit_result_title') }}</h2>
                <div class="mt-4">
                    @if ($viewerTeamActive && $cup->status === 'active')
                        <p class="mb-4 text-sm leading-6 text-gray-600 dark:text-white/80">{{ __('ui.cup_submit_intro') }}</p>
                        <div class="mb-4 grid gap-3 md:grid-cols-2">
                            <div class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                                <div class="font-semibold text-black dark:text-white">{{ __('ui.cup_submit_ai_title') }}</div>
                                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-white/80">{{ __('ui.cup_submit_ai_text') }}</p>
                            </div>
                            <div class="rounded-xl bg-secondery p-4 dark:bg-white/5">
                                <div class="font-semibold text-black dark:text-white">{{ __('ui.cup_submit_checklist_title') }}</div>
                                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-white/80">{{ __('ui.cup_submit_checklist_text', ['limit' => $cupUploadLimitMb, 'cooldown' => $cupCooldownMinutes]) }}</p>
                            </div>
                        </div>
                        @if ($maxSubmissionsPerParticipant !== null || $maxScoredSubmissionsPerParticipant !== null)
                            <div class="mb-4 rounded-xl bg-secondery p-4 text-sm dark:bg-white/5">
                                <div class="font-semibold text-black dark:text-white">{{ __('ui.cup_submission_limits_title') }}</div>
                                <p class="mt-2 text-gray-600 dark:text-white/80">{{ __('ui.cup_submission_limits_text', ['used' => $usedViewerUploads, 'max' => $maxSubmissionsPerParticipant ?: '∞', 'scored' => $maxScoredSubmissionsPerParticipant ?: '∞']) }}</p>
                            </div>
                        @endif

                        <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-secondery shadow-sm dark:border-white/10 dark:bg-white/5">
                            <button type="button" class="group block w-full text-left" data-hh-cup-submit-example-open>
                                <div class="relative aspect-[16/9] overflow-hidden bg-black">
                                    <img src="{{ $cupSubmitExampleImage }}" alt="{{ __('ui.cup_submit_example_alt') }}" class="h-full w-full object-cover opacity-90 transition duration-300 group-hover:scale-[1.02] group-hover:opacity-100" loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-transparent"></div>
                                    <div class="absolute bottom-3 left-3 right-3 flex flex-wrap items-end justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-bold text-white">{{ __('ui.cup_submit_example_title') }}</div>
                                            <div class="mt-1 text-xs text-white/70">{{ __('ui.cup_submit_example_open') }}</div>
                                        </div>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-primary px-3 py-2 text-xs font-bold text-white shadow-lg">
                                            <ion-icon name="expand-outline"></ion-icon>
                                            {{ __('ui.cup_submit_example_open') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <p class="text-sm leading-6 text-gray-600 dark:text-white/80">{{ __('ui.cup_submit_example_text') }}</p>
                                </div>
                            </button>
                        </div>

                        @if (! ($viewerEligibility['eligible'] ?? false))
                            <div class="mb-4 rounded-xl bg-rose-50 p-4 text-sm font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                                <div class="font-semibold">{{ __('ui.cup_requirements_not_met') }}</div>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach (($viewerEligibility['messages'] ?? []) as $eligibilityMessage)
                                        <li>{{ $eligibilityMessage }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form method="post" action="{{ route('cups.submissions.store', [$cup, $viewerTeam]) }}" enctype="multipart/form-data" class="space-y-4" data-cup-submission-form>
                            @csrf
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-black dark:text-white" for="screenshot">{{ __('ui.cup_submit_screenshot') }}</label>
                                <div class="rounded-2xl border border-slate-200 bg-secondery p-3 shadow-sm dark:border-white/10 dark:bg-white/5" data-hh-cup-file-upload>
                                    <input class="hh-cup-native-file-input" id="screenshot" name="screenshot" type="file" accept="image/*" required data-hh-cup-file-input style="position:absolute !important; width:1px !important; height:1px !important; padding:0 !important; margin:-1px !important; overflow:hidden !important; clip:rect(0,0,0,0) !important; white-space:nowrap !important; border:0 !important; opacity:0 !important; pointer-events:none !important;">
                                    <label for="screenshot" class="flex cursor-pointer flex-wrap items-center gap-3">
                                        <span class="button bg-primary px-5 text-white shadow-lg shadow-red-900/20 hover:brightness-110">
                                            <ion-icon name="cloud-upload-outline" class="text-lg"></ion-icon>
                                            {{ __('ui.cup_submit_choose_file') }}
                                        </span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-600 dark:text-white/75" data-hh-cup-file-name data-default-text="{{ __('ui.cup_submit_no_file_selected') }}">
                                            {{ __('ui.cup_submit_no_file_selected') }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-black dark:text-white" for="note">{{ __('ui.cup_submit_note') }}</label>
                                <textarea class="{{ $inputClass }} p-3" id="note" name="note" rows="4" maxlength="1200" placeholder="{{ __('ui.cup_submit_note_placeholder') }}"></textarea>
                            </div>
                            <button class="button bg-primary text-white w-full" type="submit" data-cup-submit-button @disabled(! ($viewerEligibility['eligible'] ?? false) || ($maxSubmissionsPerParticipant !== null && $usedViewerUploads >= $maxSubmissionsPerParticipant))>{{ __('ui.cup_submit_button') }}</button>
                        </form>
                    @elseif ($viewerTeam && $viewerTeam->status === 'disqualified')
                        <p class="rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ __('ui.cup_submission_error_disqualified') }}</p>
                    @elseif ($viewerTeam)
                        <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.cup_submit_only_active') }}</p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-white/70">{{ $soloCup ? __('ui.cup_submit_need_participant') : __('ui.cup_submit_need_team') }}</p>
                    @endif
                </div>
            </section>
            @elseif ($activeSection === 'submissions')
            <section class="box p-5 px-6 relative" id="cup-submissions">
                <div class="flex items-baseline justify-between text-black dark:text-white">
                    <h2 class="font-semibold text-lg">{{ __('ui.cup_tab_submissions') }}</h2>
                    <span class="text-sm text-blue-500">{{ $visibleSubmissionCount }}</span>
                </div>
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($visibleSubmissions as $submission)
                        <article class="py-4 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-4 max-sm:flex-col">
                                <div class="min-w-0">
                                    <div class="font-semibold text-black dark:text-white truncate">{{ $submission->team ? ($soloCup ? $submission->team->displayName() : $submission->team->name) : __('ui.cup_unknown') }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-white/70">{{ __('ui.cup_submitted_by', ['name' => $submission->submitter?->name ?? __('ui.cup_unknown')]) }} · {{ optional($submission->submitted_at ?: $submission->created_at)->diffForHumans() }}</div>
                                    <div class="mt-2 text-sm text-gray-600 dark:text-white/80">{{ $submission->resultSummary() }}</div>
                                </div>
                                <div class="text-right shrink-0 max-sm:text-left">
                                    <div class="text-sm font-semibold text-black dark:text-white">{{ $submission->statusLabel() }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-white/70">{{ __('ui.cup_submission_score_line', ['points' => $submission->points, 'tokens' => $submission->bounty_tokens, 'kills' => $submission->kills]) }}</div>
                                    @if ($submission->screenshot)
                                        <a href="{{ route('cups.submissions.screenshot', [$cup, $submission]) }}" target="_blank" class="mt-2 inline-flex text-sm font-semibold text-blue-600">{{ __('ui.cup_screenshot') }}</a>
                                    @endif
                                </div>
                            </div>
                            @if ($canManage)
                                <div class="mt-3 border-t border-white/60 pt-3 dark:border-slate-700">
                                    @if (in_array($submission->status, ['pending', 'review_required'], true))
                                        <div class="mb-3 flex flex-wrap items-center gap-2">
                                            <form method="post" action="{{ route('cups.submissions.approve', [$cup, $submission]) }}">
                                                @csrf
                                                <button class="button bg-primary text-white" style="height:36px;padding-inline:16px;" type="submit">{{ __('ui.cup_approve') }}</button>
                                            </form>
                                            <form method="post" action="{{ route('cups.submissions.reject', [$cup, $submission]) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <input class="{{ $inputClass }} text-sm" style="height:36px;width:220px;" name="review_note" type="text" maxlength="1200" placeholder="{{ __('ui.cup_review_reason_placeholder') }}">
                                                <button class="button bg-secondery dark:bg-dark3" style="height:36px;padding-inline:16px;" type="submit">{{ __('ui.cup_reject') }}</button>
                                            </form>
                                        </div>
                                    @endif
                                    <form method="post" action="{{ route('cups.submissions.manual-score', [$cup, $submission]) }}" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        <label class="text-xs text-gray-500 dark:text-white/70">
                                            <span class="mb-1 block">{{ __('ui.cup_table_kills') }}</span>
                                            <input class="{{ $inputClass }} text-sm" style="height:36px;width:72px;" name="kills" type="number" min="0" max="99" value="{{ old('kills', $submission->kills) }}" placeholder="{{ __('ui.cup_manual_kills_placeholder') }}">
                                        </label>
                                        <label class="text-xs text-gray-500 dark:text-white/70">
                                            <span class="mb-1 block">{{ __('ui.cup_table_token') }}</span>
                                            <input class="{{ $inputClass }} text-sm" style="height:36px;width:72px;" name="bounty_tokens" type="number" min="0" max="4" value="{{ old('bounty_tokens', $submission->bounty_tokens) }}" placeholder="{{ __('ui.cup_manual_bounty_placeholder') }}">
                                        </label>
                                        <label class="text-xs text-gray-500 dark:text-white/70">
                                            <span class="mb-1 block">{{ __('ui.cup_table_points') }}</span>
                                            <input class="{{ $inputClass }} text-sm" style="height:36px;width:86px;" name="points" type="number" min="0" max="999" value="{{ old('points', $submission->points) }}" placeholder="{{ __('ui.cup_manual_points_placeholder') }}">
                                        </label>
                                        <label class="text-xs text-gray-500 dark:text-white/70 flex-1" style="min-width:180px;">
                                            <span class="mb-1 block">{{ __('ui.cup_manual_note_placeholder') }}</span>
                                            <input class="{{ $inputClass }} text-sm" style="height:36px;" name="review_note" type="text" maxlength="1200" placeholder="{{ __('ui.cup_manual_note_placeholder') }}">
                                        </label>
                                        <button class="button bg-primary text-white" style="height:36px;padding-inline:18px;" type="submit">{{ __('ui.cup_manual_score_save') }}</button>
                                    </form>
                                    <form method="post" action="{{ route('cups.submissions.rescore', [$cup, $submission]) }}" class="mt-2">
                                        @csrf
                                        <button class="text-sm font-semibold text-blue-600 dark:text-blue-400" type="submit">{{ __('ui.cup_rescore') }}</button>
                                    </form>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.cup_no_submissions') }}</p>
                    @endforelse
                </div>
            </section>
            @endif
        </div>

        <aside class="lg:w-[400px]">
            <div class="lg:space-y-4 lg:pb-8 max-lg:grid sm:grid-cols-2 max-lg:gap-6" uk-sticky="media: 1024; end: #js-oversized; offset: 80">
            <div class="box p-5 px-6 space-y-4">
                <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.cup_data') }}</h3>
                <ul class="text-gray-600 space-y-4 text-sm dark:text-white/80">
                    <li class="flex items-center gap-3"><ion-icon name="radio-button-on-outline" class="text-xl"></ion-icon><div><span class="font-semibold text-black dark:text-white">{{ $cup->statusLabel() }}</span> · {{ $cup->visibilityLabel() }}</div></li>
                    <li class="flex items-center gap-3"><ion-icon name="people-outline" class="text-xl"></ion-icon><div><span class="font-semibold text-black dark:text-white">{{ $teamCount }}{{ $teamLimit ? '/'.$teamLimit : '' }}</span> {{ $entryLabel }}</div></li>
                    <li class="flex items-center gap-3"><ion-icon name="images-outline" class="text-xl"></ion-icon><div><span class="font-semibold text-black dark:text-white">{{ __('ui.cup_submission_stats', ['approved' => $approvedSubmissionCount, 'pending' => $pendingSubmissionCount, 'invalid' => $invalidSubmissionCount]) }}</span></div></li>
                    <li class="flex items-center gap-3"><ion-icon name="game-controller-outline" class="text-xl"></ion-icon><div>{{ $cup->platform ?: __('ui.cup_platform_open') }} · {{ $cup->region ?: __('ui.cup_region_open') }}</div></li>
                    <li class="flex items-center gap-3"><ion-icon name="language-outline" class="text-xl"></ion-icon><div>{{ $cup->language ?: __('ui.cup_language_open') }}</div></li>
                </ul>
            </div>

            <div class="box p-5 px-6 space-y-4" id="cup-register">
                <h3 class="font-bold text-base text-black dark:text-white">{{ $registrationOpen ? ($soloCup ? __('ui.cup_register_solo_title') : __('ui.cup_register_team_title')) : __('ui.cup_registration_closed') }}</h3>
                @guest
                    @if ($registrationOpen)
                        <p class="text-sm text-gray-600 dark:text-white/80">{{ __('ui.cup_join_login_required_text') }}</p>
                        <a class="button bg-primary text-white w-full" href="{{ route('register') }}">{{ __('ui.cup_join_login_required_action') }}</a>
                        <a class="button bg-secondery dark:bg-dark3 w-full" href="{{ route('login') }}">{{ __('ui.login') }}</a>
                    @else
                        <p class="text-sm text-gray-600 dark:text-white/80">{{ __('ui.cup_registration_closed') }}</p>
                    @endif
                @else
                    @if (! $viewerTeam && $registrationOpen)
                        <form method="post" action="{{ route('cups.teams.store', $cup) }}" class="space-y-3">
                            @csrf
                            @if ($soloCup)
                                @php($cupProfileUsername = auth()->user()?->username ?: auth()->user()?->name ?: __('ui.cup_player_fallback', ['id' => auth()->id()]))
                                <div class="rounded-xl bg-secondery p-4 text-sm dark:bg-white/5">
                                    <div class="text-gray-500 dark:text-white/70">{{ __('ui.cup_participant_name') }}</div>
                                    <div class="mt-1 font-semibold text-black dark:text-white">{{ '@'.$cupProfileUsername }}</div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-white/80">{{ __('ui.cup_solo_username_locked_text') }}</p>
                            @else
                                <input class="{{ $inputClass }}" id="cup_team_name" name="name" type="text" maxlength="100" required placeholder="{{ __('ui.cup_team_name_placeholder') }}">
                            @endif
                            <button class="button bg-primary text-white w-full" type="submit">{{ $soloCup ? __('ui.cup_register_solo') : __('ui.cup_register_team') }}</button>
                        </form>
                    @elseif ($viewerTeam)
                        <p class="text-sm text-gray-600 dark:text-white/80">{{ $soloCup ? __('ui.cup_your_participation') : __('ui.cup_your_team') }}</p>
                    @else
                        <p class="text-sm text-gray-600 dark:text-white/80">{{ __('ui.cup_registration_closed') }}</p>
                    @endif
                @endguest
            </div>

            @if ($viewerTeam)
                <div class="box p-5 px-6 space-y-4">
                    <h3 class="font-bold text-base text-black dark:text-white">{{ $soloCup ? __('ui.cup_your_participation') : __('ui.cup_your_team') }}</h3>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5"><strong class="block text-black dark:text-white">{{ $viewerTeam->points_total }}</strong><span class="text-xs text-gray-500 dark:text-white/70">{{ __('ui.cup_table_points') }}</span></div>
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5"><strong class="block text-black dark:text-white">{{ $viewerTeam->bounty_tokens_total }}</strong><span class="text-xs text-gray-500 dark:text-white/70">{{ __('ui.cup_table_token') }}</span></div>
                        <div class="rounded-xl bg-secondery p-3 dark:bg-white/5"><strong class="block text-black dark:text-white">{{ $viewerTeam->kills_total }}</strong><span class="text-xs text-gray-500 dark:text-white/70">{{ __('ui.cup_table_kills') }}</span></div>
                    </div>
                    @unless ($soloCup)
                        <input class="{{ $inputClass }}" id="cup_join_link" type="text" readonly value="{{ route('cups.teams.join', [$cup, $viewerTeam->join_token]) }}">
                    @endunless
                    @if ($viewerTeam->status === 'disqualified')
                        <p class="text-sm text-rose-500">{{ __('ui.cup_submission_error_disqualified') }}</p>
                    @else
                        <form method="post" action="{{ route('cups.teams.leave', [$cup, $viewerTeam]) }}" onsubmit="return confirm('{{ $soloCup ? __('ui.cup_leave_participation_confirm') : __('ui.cup_leave_confirm') }}')">
                            @csrf
                            <button class="button bg-secondery dark:bg-dark3 w-full" type="submit">{{ $soloCup ? __('ui.cup_leave_participation') : __('ui.cup_leave_team') }}</button>
                        </form>
                    @endif
                </div>
            @endif

            @if ($topParticipants->isNotEmpty())
                <div class="box p-5 px-6">
                    <div class="flex items-baseline justify-between text-black dark:text-white">
                        <h3 class="font-bold text-base">{{ $entryLabel }}</h3>
                        <a href="{{ route('cups.show.section', [$cup, 'participants']) }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                    </div>
                    <div class="side-list mt-3">
                        @foreach ($topParticipants as $team)
                            <div class="side-list-item">
                                <a href="{{ $team->owner ? $profileUrl($team->owner) : route('members.index') }}">
                                    <img src="{{ $team->owner?->avatarUrl() ?: asset('assets/socialite/images/avatars/avatar-3.jpg') }}" alt="" class="side-list-image rounded-full">
                                </a>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $team->owner ? $profileUrl($team->owner) : route('members.index') }}"><h4 class="side-list-title truncate">{{ $soloCup ? $team->displayName() : $team->name }}</h4></a>
                                    <div class="side-list-info">{{ __('ui.cup_participant_points_line', ['points' => $team->points_total]) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="box p-5 px-6 space-y-4">
                <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.cup_owner') }}</h3>
                <div class="side-list-item">
                    <a href="{{ $ownerUrl }}"><img src="{{ $ownerAvatar }}" alt="" class="side-list-image rounded-full"></a>
                    <div class="flex-1 min-w-0">
                        <a href="{{ $ownerUrl }}"><h4 class="side-list-title truncate">{{ $owner?->name ?? 'hnt.rocks' }}</h4></a>
                        <div class="side-list-info">{{ $owner?->username ? '@'.$owner->username : __('ui.cup_organisation') }}</div>
                    </div>
                    <a href="{{ $ownerUrl }}" class="bg-secondery/60 button rounded-full">{{ __('ui.profile') }}</a>
                </div>
            </div>

            @if ($canManage)
                <div class="box p-5 px-6 space-y-3">
                    <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.cup_administration') }}</h3>
                    <a class="button bg-primary text-white w-full" href="{{ route('cups.edit', $cup) }}">{{ __('ui.cup_edit_action') }}</a>
                    <form method="post" action="{{ route('cups.destroy', $cup) }}" onsubmit="return confirm('{{ __('ui.cup_archive_confirm') }}')">
                        @csrf
                        @method('delete')
                        <button class="button bg-secondery dark:bg-dark3 w-full" type="submit">{{ __('ui.cup_archive') }}</button>
                    </form>
                </div>
            @endif
            </div>
        </aside>
    </div>
</div>

@if ($cupNoticeModal)
    <div id="hh-cup-notice-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" data-hh-cup-notice-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="hh-cup-notice-title">
        <button type="button" class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm" data-hh-cup-notice-close aria-label="{{ __('ui.close') }}"></button>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-dark2">
            <div class="h-1 {{ $cupNoticeAccent }}"></div>
            <div class="p-5">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-secondery text-primary dark:bg-white/5">
                        <ion-icon name="{{ $cupNoticeIcon }}" class="text-3xl"></ion-icon>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 id="hh-cup-notice-title" class="text-lg font-bold text-black dark:text-white">{{ $cupNoticeModal['title'] }}</h2>
                        @if (! empty($cupNoticeModal['message']))
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-white/80">{{ $cupNoticeModal['message'] }}</p>
                        @endif

                        @if (! empty($cupNoticeModal['meta']))
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-gray-500 dark:text-white/70">
                                @foreach ($cupNoticeModal['meta'] as $metaLine)
                                    <span class="rounded-xl bg-secondery px-3 py-2 dark:bg-white/5">{{ $metaLine }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if (! empty($cupNoticeModal['errors']) && count($cupNoticeModal['errors']) > 1)
                            <ul class="mt-4 list-disc space-y-1 pl-5 text-sm text-rose-600 dark:text-rose-300">
                                @foreach ($cupNoticeModal['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" class="button bg-primary text-white !w-auto px-6" data-hh-cup-notice-close>{{ __('ui.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endif

<div id="hh-cup-submit-example-modal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/85 p-4 backdrop-blur-sm" data-hh-cup-submit-example-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="hh-cup-submit-example-title">
    <button type="button" class="absolute inset-0" data-hh-cup-submit-example-close aria-label="{{ __('ui.close') }}"></button>
    <div class="relative w-full max-w-6xl overflow-hidden rounded-2xl border border-white/10 bg-black shadow-2xl">
        <button type="button" class="absolute right-3 top-3 z-10 grid h-10 w-10 place-items-center rounded-full bg-black/70 text-white transition hover:bg-primary" data-hh-cup-submit-example-close aria-label="{{ __('ui.close') }}">
            <ion-icon name="close-outline" class="text-2xl"></ion-icon>
        </button>
        <img src="{{ $cupSubmitExampleImage }}" alt="{{ __('ui.cup_submit_example_alt') }}" class="max-h-[82vh] w-full object-contain">
        <div class="border-t border-white/10 bg-black/80 p-4">
            <h2 id="hh-cup-submit-example-title" class="font-bold text-white">{{ __('ui.cup_submit_example_title') }}</h2>
            <p class="mt-1 text-sm leading-6 text-white/70">{{ __('ui.cup_submit_example_text') }}</p>
        </div>
    </div>
</div>

<div id="hh-cup-submission-overlay" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 p-5" role="alertdialog" aria-modal="true" aria-live="assertive" aria-labelledby="hh-cup-submission-overlay-title">
    <div class="max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-dark2">
        <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-primary-soft/40 text-primary">
            <ion-icon name="sparkles-outline" class="text-3xl"></ion-icon>
        </div>
        <p class="text-sm font-semibold uppercase tracking-wide text-gray-400">{{ __('ui.cup_submission_kicker') }}</p>
        <h3 class="mt-2 text-xl font-bold text-black dark:text-white" id="hh-cup-submission-overlay-title">{{ __('ui.cup_submission_overlay_title') }}</h3>
        <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-white/80">{{ __('ui.cup_submission_overlay_text') }}</p>
        <div class="mt-5 grid grid-cols-3 gap-2 text-xs font-semibold text-gray-500 dark:text-white/70">
            <span class="rounded-xl bg-secondery p-2 dark:bg-white/5">{{ __('ui.cup_submission_overlay_upload') }}</span>
            <span class="rounded-xl bg-secondery p-2 dark:bg-white/5">{{ __('ui.cup_submission_overlay_ai') }}</span>
            <span class="rounded-xl bg-secondery p-2 dark:bg-white/5">{{ __('ui.cup_submission_overlay_scoring') }}</span>
        </div>
    </div>
</div>

@endsection

@if ($cupNoticeModal)
    @push('scripts')
        <script>
        (function () {
            function showCupNotice() {
                var modal = document.querySelector('[data-hh-cup-notice-modal]');
                if (!modal) {
                    return;
                }

                function openModal() {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    modal.setAttribute('aria-hidden', 'false');
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    modal.setAttribute('aria-hidden', 'true');
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                modal.querySelectorAll('[data-hh-cup-notice-close]').forEach(function (button) {
                    button.addEventListener('click', closeModal);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                        closeModal();
                    }
                });

                window.setTimeout(openModal, 80);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showCupNotice);
            } else {
                showCupNotice();
            }
        })();
        </script>
    @endpush
@endif

@push('scripts')
<script>
(function () {
    var modal = document.querySelector('[data-hh-cup-submit-example-modal]');
    var openers = document.querySelectorAll('[data-hh-cup-submit-example-open]');

    if (!modal || !openers.length) {
        return;
    }

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
    }

    openers.forEach(function (opener) {
        opener.addEventListener('click', openModal);
    });

    modal.querySelectorAll('[data-hh-cup-submit-example-close]').forEach(function (closer) {
        closer.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeModal();
        }
    });
})();
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('[data-cup-chat-form]');

    if (!form) {
        return;
    }

    event.preventDefault();

    const input = form.querySelector('[name="body"]');
    const button = form.querySelector('[type="submit"]');
    const list = document.querySelector('[data-cup-chat-list]');
    const empty = document.querySelector('[data-cup-chat-empty]');
    const counter = document.querySelector('[data-cup-chat-count]');
    const status = document.querySelector('[data-cup-chat-status]');
    const originalButtonText = button ? button.textContent : '';

    if (!input || !input.value.trim()) {
        return;
    }

    if (button) {
        button.disabled = true;
        button.textContent = '...';
    }

    if (status) {
        status.classList.add('hidden');
        status.textContent = '';
        status.classList.remove('text-rose-600', 'text-emerald-600');
    }

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

        const data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(data.message || form.dataset.errorText || 'Senden fehlgeschlagen.');
        }

        if (data.html && list) {
            if (empty) {
                empty.remove();
            }

            list.insertAdjacentHTML('afterbegin', data.html);
        }

        if (counter && typeof data.count !== 'undefined') {
            counter.textContent = data.count;
        }

        form.reset();

        if (status && data.message) {
            status.textContent = data.message;
            status.classList.remove('hidden');
            status.classList.add('text-emerald-600');
            window.setTimeout(function () {
                status.classList.add('hidden');
            }, 2200);
        }
    } catch (error) {
        if (status) {
            status.textContent = error.message || form.dataset.errorText || 'Senden fehlgeschlagen.';
            status.classList.remove('hidden');
            status.classList.add('text-rose-600');
        }
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = originalButtonText;
        }
    }
});
</script>
@endpush


@push('scripts')
<script>
(function () {
    var input = document.querySelector('[data-hh-cup-file-input]');
    var name = document.querySelector('[data-hh-cup-file-name]');

    if (!input || !name) {
        return;
    }

    var defaultText = name.getAttribute('data-default-text') || name.textContent || '';

    input.addEventListener('change', function () {
        var fileName = input.files && input.files.length ? input.files[0].name : defaultText;
        name.textContent = fileName;
        name.classList.toggle('text-white', Boolean(input.files && input.files.length));
    });
})();
</script>
@endpush

@push('scripts')
<script>
(function(){
  var form = document.querySelector('[data-cup-submission-form]');
  var overlay = document.getElementById('hh-cup-submission-overlay');
  if (!form || !overlay) return;

  form.addEventListener('submit', function(){
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
      return;
    }

    var button = form.querySelector('[data-cup-submit-button]');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');

    if (button) {
      button.disabled = true;
      button.textContent = {!! json_encode(__('ui.cup_submission_checking'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    }
  });
})();
</script>
@endpush
