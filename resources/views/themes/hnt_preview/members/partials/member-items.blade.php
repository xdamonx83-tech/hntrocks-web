@forelse($members as $member)
@php
    $profile = $member->profile;
    $friendship = $friendshipMap->get($member->id);
    $isOwnCard = (int) auth()->id() === (int) $member->id;
    $memberUrl = $isOwnCard ? route('profile.show') : route('profile.public', $member);
    $memberFriendCount = (int) ($friendCounts[$member->id] ?? 0);
    $memberLevel = app(\App\Services\GamificationService::class)->levelForXp((int) ($member->xp_total ?? 0));
    $memberHeadline = trim((string) ($profile?->headline ?: $profile?->bio ?: __('ui.members_no_headline')));
    $memberOnline = $member->allowsOnlineStatusVisibility(auth()->user()) && $member->isOnline();
    $messageUrl = route('messages.with-user', $member);
@endphp
<article class="members-live-card" data-member-card>
    <a class="members-live-cover" href="{{ $memberUrl }}" aria-label="{{ $member->name }}">
        <img src="{{ $member->coverUrl() }}" alt="">
        <span class="members-live-level">Level {{ $memberLevel }}</span>
    </a>

    <div class="members-live-card-body">
        <div class="members-live-person-row">
            <a class="members-live-avatar" href="{{ $memberUrl }}">
                <img src="{{ $member->avatarUrl() }}" alt="{{ $member->name }}">
                <i class="{{ $memberOnline ? 'is-online' : '' }}"></i>
            </a>
            <div class="members-live-identity">
                <a href="{{ $memberUrl }}">{{ $member->name }}</a>
                <span>{{ '@'.$member->username }} · {{ $memberOnline ? __('ui.online') : __('ui.offline') }}</span>
            </div>
            @if($profile?->is_lfg_available)
                <span class="members-live-lfg">LFG</span>
            @endif
        </div>

        <p class="members-live-headline">{{ $memberHeadline }}</p>

        <div class="members-live-tags">
            @if($profile?->platform)<span class="yellow">{{ $profile->platform }}</span>@endif
            @if($profile?->region)<span class="blue">{{ $profile->region }}</span>@endif
            @if($profile?->language)<span class="purple">{{ $profile->language }}</span>@endif
            @if($profile?->playstyle)<span class="green">{{ $profile->playstyle }}</span>@endif
        </div>

        <div class="members-live-stats">
            <span><strong>{{ (int) ($member->visible_feed_posts_count ?? 0) }}</strong><small>{{ __('ui.profile_posts_stat') }}</small></span>
            <span><strong>{{ $memberFriendCount }}</strong><small>{{ __('ui.friends') }}</small></span>
            <span><strong>{{ (int) ($member->visible_moments_count ?? 0) }}</strong><small>{{ __('ui.moments') }}</small></span>
            <span><strong>{{ (int) ($member->badges_count ?? 0) }}</strong><small>{{ __('ui.badges') }}</small></span>
        </div>

        <div class="members-live-actions">
            @if($isOwnCard)
                <a class="members-action-secondary" href="{{ route('profile.edit') }}">{{ __('ui.members_edit') }}</a>
                <a class="members-action-primary" href="{{ route('profile.show') }}">{{ __('ui.members_my_profile') }}</a>
            @elseif(! $friendship || $friendship->isDeclined())
                <form method="post" action="{{ route('friends.store', $member) }}">
                    @csrf
                    <button class="members-action-secondary" type="submit">{{ __('ui.profile_add_friend') }}</button>
                </form>
                <a class="members-action-primary" href="{{ $messageUrl }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ $messageUrl }}">{{ __('ui.profile_message_singular') }}</a>
            @elseif($friendship->isPending() && $friendship->isRequester(auth()->user()))
                <form method="post" action="{{ route('friends.destroy', $friendship) }}">
                    @csrf
                    @method('DELETE')
                    <button class="members-action-secondary" type="submit">{{ __('ui.members_friend_request_sent') }}</button>
                </form>
                <a class="members-action-primary" href="{{ $messageUrl }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ $messageUrl }}">{{ __('ui.profile_message_singular') }}</a>
            @elseif($friendship->isPending() && $friendship->isRecipient(auth()->user()))
                <form method="post" action="{{ route('friends.decline', $friendship) }}">
                    @csrf
                    <button class="members-action-secondary" type="submit">{{ __('ui.profile_decline') }}</button>
                </form>
                <form method="post" action="{{ route('friends.accept', $friendship) }}">
                    @csrf
                    <button class="members-action-primary" type="submit">{{ __('ui.profile_accept') }}</button>
                </form>
            @elseif($friendship->isAccepted())
                <form method="post" action="{{ route('friends.destroy', $friendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_remove_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button class="members-action-secondary" type="submit">{{ __('ui.members_friends_active') }}</button>
                </form>
                <a class="members-action-primary" href="{{ $messageUrl }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ $messageUrl }}">{{ __('ui.profile_message_singular') }}</a>
            @endif
        </div>
    </div>
</article>
@empty
<article class="members-live-empty">
    <span>HNT.ROCKS</span>
    <h2>{{ __('ui.members_empty_title') }}</h2>
    <p>{{ __('ui.members_empty_text') }}</p>
</article>
@endforelse
