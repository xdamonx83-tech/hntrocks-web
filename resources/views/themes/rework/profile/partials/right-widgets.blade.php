@php
    $viewer = auth()->user();
    $reworkAsset = fn (string $path): string => \App\Support\HntTheme::asset($path, 'rework');
    $formatCount = fn (int $count): string => number_format($count);
    $crownsSummary = $socialiteCrownsSummary ?? ['balance' => 0, 'enabled' => false];
    $profileStats = $socialiteProfileStats ?? [];
    $marksBalance = (int) ($crownsSummary['balance'] ?? 0);
    $shopUrl = \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : null;
    $membersUrl = \Illuminate\Support\Facades\Route::has('members.index') ? route('members.index') : null;
    $memberProfileUrl = function ($member): string {
        if (! $member?->username) {
            return '#';
        }

        return (int) $member->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $member);
    };
    $highlightScore = function ($post): int {
        return (int) ($post?->reactions_count ?? 0)
            + (int) ($post?->comments_count ?? 0)
            + (int) ($post?->shares_count ?? 0);
    };
@endphp

<aside class="right-col">
    <section class="profile-card card">
        <div class="profile-top"><strong data-rework-profile-name>{{ $viewer?->name ?: __('ui.my_profile') }}</strong></div>
        <div class="profile-main">
            <img alt="{{ __('ui.crowns_label') }}" class="mark" src="{{ $reworkAsset('images/bounty-marks.png') }}"/>
            <div class="levels">
                <span class="level-badge">{{ __('ui.level') }} {{ $viewer?->level ?? 1 }}</span>
                <span class="level-badge">{{ $formatCount((int) ($profileStats['xp'] ?? ($viewer?->xp_total ?? 0))) }} XP</span>
            </div>
        </div>
        <div class="balance">
            <div><strong>{{ $formatCount($marksBalance) }}</strong><span>{{ __('ui.crowns_label') }}</span></div>
            <div class="profile-buttons">
                @if($shopUrl)
                    <a class="btn light" href="{{ $shopUrl }}">{{ __('ui.crowns_shop_kicker') }}</a>
                @endif
            </div>
        </div>
        <div class="profile-stat-grid">
            <span><strong>{{ $formatCount((int) ($profileStats['posts'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_posts') }}</em></span>
            <span><strong>{{ $formatCount((int) ($profileStats['reactions'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_reactions') }}</em></span>
            <span><strong>{{ $formatCount((int) ($profileStats['comments'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_comments') }}</em></span>
            <span><strong>{{ $formatCount((int) ($profileStats['moments'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_moments') }}</em></span>
            <span><strong>{{ $formatCount((int) ($profileStats['friends'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_friends') }}</em></span>
            <span><strong>{{ $formatCount((int) ($profileStats['lfg'] ?? 0)) }}</strong><em>{{ __('ui.rework_profile_lfg') }}</em></span>
        </div>
    </section>

    <section class="side-card suggested">
        <div class="side-head"><h2>{{ __('ui.rework_suggested_for_you') }}</h2>@if($membersUrl)<a href="{{ $membersUrl }}">{{ __('ui.see_all') }}</a>@endif</div>
        <div class="suggestion-list">
            @forelse(($socialiteMembers ?? collect())->take(3) as $member)
                <div class="suggestion">
                    <a class="suggestion-avatar" href="{{ $memberProfileUrl($member) }}"><img alt="{{ $member->name }}" src="{{ $member->avatarUrl() }}"/></a>
                    <div class="suggestion-info"><a href="{{ $memberProfileUrl($member) }}"><strong>{{ $member->name }}</strong></a><span>{{ $member->username ? '@'.$member->username : 'HNT Hunter' }}</span></div>
                    @auth
                        <form action="{{ route('friends.store', $member) }}" class="rework-friend-request-form" data-rework-friend-request-form method="post" data-requested-label="{{ __('ui.requested') }}" data-failed-label="{{ __('ui.rework_friend_request_failed') }}">
                            @csrf
                            <button class="btn light" type="submit">{{ __('ui.profile_add_friend_clean') }}</button>
                        </form>
                    @endauth
                </div>
            @empty
                <div class="side-empty">{{ __('ui.rework_no_suggestions') }}</div>
            @endforelse
        </div>
    </section>

    <section class="side-card highlights">
        <div class="side-head"><h2>{{ __('ui.rework_current_highlights') }}</h2></div>
        <div class="highlight-list">
            @if($socialiteHighlightTopPost)
                <a class="highlight-card" href="{{ $socialiteHighlightTopPost->permalink() }}">
                    <span class="highlight-badge">{{ __('ui.rework_top_post') }}</span>
                    <strong>{{ $socialiteHighlightTopPost->excerpt(90) ?: __('ui.rework_top_post_empty') }}</strong>
                    <small>{{ __('ui.rework_feed_author', ['author' => $socialiteHighlightTopPost->user?->name ?: ($socialiteHighlightTopPost->user?->username ?: 'HNT Hunter')]) }}</small>
                    <span class="highlight-meta"><i aria-hidden="true" class="ph ph-heart ph-icon"></i>{{ $formatCount((int) ($socialiteHighlightTopPost->reactions_count ?? 0)) }} <i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>{{ $formatCount((int) ($socialiteHighlightTopPost->comments_count ?? 0)) }} <i aria-hidden="true" class="ph ph-share-network ph-icon"></i>{{ $formatCount((int) ($socialiteHighlightTopPost->shares_count ?? 0)) }} <em>{{ trans_choice('ui.rework_interactions', $highlightScore($socialiteHighlightTopPost), ['count' => $highlightScore($socialiteHighlightTopPost)]) }}</em></span>
                </a>
            @endif
            @if($socialiteHighlightLfg)
                <a class="highlight-card" href="{{ route('lfg.show', $socialiteHighlightLfg) }}">
                    <span class="highlight-badge">{{ __('ui.rework_new_lfg') }}</span>
                    <strong>{{ $socialiteHighlightLfg->title }}</strong>
                    <small>{{ $socialiteHighlightLfg->user?->name ?: ($socialiteHighlightLfg->user?->username ?: 'HNT Hunter') }}</small>
                    <span class="highlight-pills">
                        @foreach(array_slice($socialiteHighlightLfg->displayTags(), 0, 3) as $tag)
                            <em>{{ $tag }}</em>
                        @endforeach
                        <em>{{ $socialiteHighlightLfg->statusLabel() }}</em>
                    </span>
                </a>
            @endif
            @if($socialiteHighlightCup)
                <a class="highlight-card" href="{{ route('cups.show', $socialiteHighlightCup) }}">
                    <span class="highlight-badge">{{ __('ui.rework_active_cup') }}</span>
                    <strong>{{ $socialiteHighlightCup->title }}</strong>
                    <small>{{ $socialiteHighlightCup->displaySummary() }}</small>
                    <span class="highlight-pills"><em>{{ $socialiteHighlightCup->statusLabel() }}</em><em>{{ trans_choice('ui.rework_cup_team_count', (int) ($socialiteHighlightCup->active_teams_count ?? 0), ['count' => (int) ($socialiteHighlightCup->active_teams_count ?? 0)]) }}</em></span>
                </a>
            @endif
            @if(! $socialiteHighlightTopPost && ! $socialiteHighlightLfg && ! $socialiteHighlightCup)
                <div class="side-empty">{{ __('ui.rework_no_highlights') }}</div>
            @endif
        </div>
    </section>
</aside>
