@php
    $profileFriendshipAccepted = ! $isOwnProfile && ($friendship?->isAccepted() ?? false);
    $profileFriendRequestSent = ! $isOwnProfile
        && $viewer
        && ($friendship?->isPending() ?? false)
        && $friendship->isRequester($viewer);
    $profileLobbyInviteLabel = app()->getLocale() === 'en' ? 'Invite to lobby' : 'Zur Lobby einladen';
    $profileLobbyInvitePlanned = app()->getLocale() === 'en'
        ? 'Lobby invitations will be activated with the Ready Lobby system.'
        : 'Lobby-Einladungen werden mit dem Ready-Lobby-System aktiviert.';
@endphp
<section class="profile-page-heading">
<div>
<span>HNT.ROCKS</span>
<h1>Profil</h1>
</div>
<div class="profile-page-tools">
@if($isOwnProfile)
<a aria-label="Profil bearbeiten" class="profile-edit-main" href="{{ route('profile.edit') }}">
<svg><use href="#i-user"></use></svg>
<span>Profil bearbeiten</span>
</a>
@else
@if($profileCanRequestFriend)
<form action="{{ route('friends.store', $profileUser) }}" method="post">
@csrf
<button aria-label="{{ __('ui.profile_add_friend') }}" class="profile-edit-main" type="submit">
<svg><use href="#i-users"></use></svg>
<span>{{ __('ui.profile_add_friend') }}</span>
</button>
</form>
@elseif($profileFriendRequestSent)
<button aria-disabled="true" class="profile-edit-main" disabled type="button">
<svg><use href="#i-users"></use></svg>
<span>{{ __('ui.profile_friend_request_sent') }}</span>
</button>
@endif
@if($profileMessageUrl)
<a aria-label="Nachricht an {{ $profileDisplayName }}" class="profile-edit-main" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ $profileMessageUrl }}" href="{{ $profileMessageUrl }}">
<svg><use href="#i-comment"></use></svg>
<span>{{ __('ui.rework_profile_message') }}</span>
</a>
@endif
@if($profileFriendshipAccepted)
<button aria-label="{{ $profileLobbyInviteLabel }}" class="profile-edit-main" data-toast="{{ $profileLobbyInvitePlanned }}" type="button">
<svg><use href="#i-users"></use></svg>
<span>{{ $profileLobbyInviteLabel }}</span>
</button>
@endif
@endif
<button aria-label="Profil teilen" class="circle-button" data-profile-share type="button">
<svg><use href="#i-share"></use></svg>
</button>
<button aria-label="Weitere Optionen" class="circle-button" data-toast="Weitere Profiloptionen" type="button">
<svg><use href="#i-more"></use></svg>
</button>
</div>
</section>
