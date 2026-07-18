@php
    $profileFriendsLive = collect($profileFriendsPreview ?? []);
@endphp
<section class="profile-tab-panel" data-profile-panel="friends" hidden id="profileTabFriends" role="tabpanel">
<div class="profile-panel-toolbar">
<div><span>{{ $profileFormatCount($profileFriendsCount ?? $profileFriendsLive->count()) }} FREUNDE</span><strong>Freundesliste</strong></div>
<div class="profile-panel-filters" aria-label="Freunde filtern">
<button class="active" data-profile-friend-filter="all" type="button">Alle</button>
<button data-profile-friend-filter="online" type="button">Online</button>
<button data-profile-friend-filter="ready" type="button">Ready</button>
</div>
</div>
<div class="profile-friends-grid" data-profile-friends-grid>
@forelse($profileFriendsLive as $friend)
@php
    $friendProfile = $friend->profile;
    $friendName = trim((string) ($friend->name ?: $friend->username ?: 'HNT Hunter'));
    $friendHandle = $friend->username ? '@'.$friend->username : '@hunter';
    $friendAvatar = $friend->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $friendOnline = $friend->allowsOnlineStatusVisibility($viewer) && $friend->isOnline();
    $friendReady = (bool) ($friendProfile?->is_lfg_available ?? false);
    $friendState = $friendReady ? 'ready' : ($friendOnline ? 'online' : 'offline');
    $friendStateLabel = $friendReady ? 'Ready' : ($friendOnline ? 'Online' : 'Offline');
    $friendMeta = collect([$friendProfile?->region, $friendProfile?->platform, $friendProfile?->playstyle])->filter()->join(' · ');
    $friendProfileUrl = $viewer?->is($friend) ? route('profile.show') : route('profile.public', $friend);
    $friendMessageUrl = $viewer && ! $viewer->is($friend) && $friend->can_message_from_viewer && \Illuminate\Support\Facades\Route::has('messages.with-user')
        ? route('messages.with-user', $friend)
        : null;
@endphp
<article class="profile-friend-card" data-profile-friend-card data-friend-online="{{ $friendOnline ? '1' : '0' }}" data-friend-ready="{{ $friendReady ? '1' : '0' }}">
<a class="friend-avatar" href="{{ $friendProfileUrl }}"><img alt="{{ $friendName }}" src="{{ $friendAvatar }}"/>@if($friendOnline)<i></i>@endif</a>
<div><strong>{{ $friendName }}</strong><span>{{ $friendHandle }} · {{ $friendOnline ? 'Online' : 'Offline' }}</span><small>{{ $friendMeta !== '' ? $friendMeta : 'HNT.ROCKS Hunter' }}</small></div>
<span class="friend-state {{ $friendState }}">{{ $friendStateLabel }}</span>
<div class="friend-actions">
@if($friendMessageUrl)
<a href="{{ $friendMessageUrl }}"><svg><use href="#i-comment"></use></svg> Nachricht</a>
@endif
<a aria-label="Profil von {{ $friendName }} öffnen" href="{{ $friendProfileUrl }}"><svg><use href="#i-arrow"></use></svg></a>
</div>
</article>
@empty
<div class="profile-tab-empty profile-friends-empty">
<strong>Noch keine Freunde</strong>
<p>Angenommene Freundschaften erscheinen hier.</p>
<a href="{{ route('members.index') }}">Hunter entdecken <svg><use href="#i-arrow"></use></svg></a>
</div>
@endforelse
</div>
<div class="profile-filter-empty" data-profile-friends-filter-empty hidden>Für diesen Filter wurden keine Freunde gefunden.</div>
</section>
