@forelse($members as $member)
    @php
        $profile = $member->profile;
        $friendship = $friendshipMap->get($member->id);
        $isOwnCard = (int) auth()->id() === (int) $member->id;
        $memberUrl = $isOwnCard ? route('profile.show') : route('profile.public', $member);
        $memberFriendCount = (int) ($friendCounts[$member->id] ?? 0);
        $memberLevel = app(\App\Services\GamificationService::class)->levelForXp((int) ($member->xp_total ?? 0));
        $memberHeadline = trim((string) ($profile?->headline ?: $profile?->bio ?: __('ui.members_no_headline')));
        $memberLanguage = trim((string) ($profile?->language ?: '—'));
        $memberPlatform = trim((string) ($profile?->platform ?: '—'));
        $memberRegion = trim((string) ($profile?->region ?: '—'));
        $memberPlaystyle = trim((string) ($profile?->playstyle ?: '—'));
        $memberReady = (bool) $profile?->is_lfg_available;
        $memberOnline = $member->allowsOnlineStatusVisibility(auth()->user()) && $member->isOnline();
        $memberPending = $friendship?->isPending() ?? false;
        $statusClass = $memberReady ? 'ready' : ($memberPending ? 'pending' : ($memberOnline ? 'online' : 'offline'));
        $statusLabel = $memberReady
            ? 'Ready'
            : ($memberPending
                ? (app()->getLocale() === 'en' ? 'Request open' : 'Anfrage offen')
                : ($memberOnline ? 'Online' : 'Offline'));
    @endphp

    <article class="members-table-row {{ $memberReady ? 'is-highlighted' : '' }}" role="row">
        <div class="member-identity">
            <a class="member-avatar" href="{{ $memberUrl }}">
                <img alt="{{ $member->name }}" src="{{ $member->avatarUrl() }}">
                <span>{{ $memberLevel }}</span>
            </a>
            <div>
                <strong><a href="{{ $memberUrl }}">{{ $member->name }}</a></strong>
                <small>{{ '@'.$member->username }}</small>
            </div>
        </div>
        <div class="member-headline">
            <strong>{{ $memberHeadline }}</strong>
            <small>{{ $memberLanguage }}</small>
        </div>
        <span class="member-chip">{{ $memberPlatform }}</span>
        <span class="member-cell">{{ $memberRegion }}</span>
        <span class="member-cell">{{ $memberPlaystyle }}</span>
        <span class="member-number"><strong>{{ (int) ($member->visible_feed_posts_count ?? 0) }}</strong><small>Posts</small></span>
        <span class="member-number"><strong>{{ $memberFriendCount }}</strong><small>{{ __('ui.friends') }}</small></span>
        <span class="member-number"><strong>{{ (int) ($member->visible_moments_count ?? 0) }}</strong><small>Moments</small></span>
        <span class="member-status {{ $statusClass }}"><i></i>{{ $statusLabel }}</span>
        <div class="member-row-actions">
            @if($isOwnCard)
                <a aria-label="{{ app()->getLocale() === 'en' ? 'Edit profile' : 'Profil bearbeiten' }}" href="{{ route('profile.edit') }}" title="{{ app()->getLocale() === 'en' ? 'Edit profile' : 'Profil bearbeiten' }}"><svg><use href="#i-settings"></use></svg></a>
                <a aria-label="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}" href="{{ $memberUrl }}" title="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}"><svg><use href="#i-arrow"></use></svg></a>
            @elseif(!$friendship || $friendship->isDeclined())
                <form method="post" action="{{ route('friends.store', $member) }}">@csrf<button aria-label="{{ __('ui.profile_add_friend') }}" title="{{ __('ui.profile_add_friend') }}" type="submit"><svg><use href="#i-plus"></use></svg></button></form>
                <a aria-label="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}" href="{{ $memberUrl }}" title="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}"><svg><use href="#i-arrow"></use></svg></a>
            @elseif($friendship->isPending() && $friendship->isRequester(auth()->user()))
                <form method="post" action="{{ route('friends.destroy', $friendship) }}">@csrf @method('DELETE')<button aria-label="{{ app()->getLocale() === 'en' ? 'Cancel request' : 'Anfrage zurückziehen' }}" title="{{ app()->getLocale() === 'en' ? 'Cancel request' : 'Anfrage zurückziehen' }}" type="submit"><svg><use href="#i-x"></use></svg></button></form>
                <a aria-label="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}" href="{{ $memberUrl }}" title="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}"><svg><use href="#i-arrow"></use></svg></a>
            @elseif($friendship->isPending() && $friendship->isRecipient(auth()->user()))
                <form method="post" action="{{ route('friends.accept', $friendship) }}">@csrf<button aria-label="{{ __('ui.profile_accept') }}" title="{{ __('ui.profile_accept') }}" type="submit"><svg><use href="#i-check"></use></svg></button></form>
                <form method="post" action="{{ route('friends.decline', $friendship) }}">@csrf<button aria-label="{{ __('ui.profile_decline') }}" title="{{ __('ui.profile_decline') }}" type="submit"><svg><use href="#i-x"></use></svg></button></form>
            @else
                <a aria-label="{{ app()->getLocale() === 'en' ? 'Message '.$member->name : 'Nachricht an '.$member->name }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ route('messages.with-user', $member) }}" href="{{ route('messages.with-user', $member) }}" title="{{ app()->getLocale() === 'en' ? 'Message' : 'Nachricht' }}"><svg><use href="#i-comment"></use></svg></a>
                <a aria-label="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}" href="{{ $memberUrl }}" title="{{ app()->getLocale() === 'en' ? 'Open profile' : 'Profil öffnen' }}"><svg><use href="#i-arrow"></use></svg></a>
            @endif
        </div>
    </article>
@empty
    <div class="members-empty-state">
        <strong>{{ __('ui.members_empty_title') }}</strong>
        <span>{{ __('ui.members_empty_text') }}</span>
    </div>
@endforelse
