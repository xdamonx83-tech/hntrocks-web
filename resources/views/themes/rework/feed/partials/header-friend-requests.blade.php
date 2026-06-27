@php
    $defaultAvatar = $defaultAvatar ?? asset('assets/vikinger/img/default-avatar.svg');
@endphp

@forelse($headerFriendRequests as $friendRequest)
    @php
        $requester = $friendRequest->requester;
        $requesterName = $requester?->name ?: ($requester?->username ?: 'hnt.rocks');
        $requesterAvatar = $requester?->avatarUrl() ?: $defaultAvatar;
        $requesterUrl = $requester ? route('profile.public', $requester) : route('members.index');
        $requesterHandle = $requester?->username ? '@'.$requester->username : __('ui.members');
    @endphp
    <article class="dropdown-item request-item unread" data-rework-friend-request-item>
        <a class="request-avatar" href="{{ $requesterUrl }}"><img alt="{{ $requesterName }}" src="{{ $requesterAvatar }}"/></a>
        <div class="request-copy"><strong>{{ $requesterName }}</strong><small>{{ $requesterHandle }} &middot; {{ $friendRequest->created_at?->diffForHumans() }}</small><div class="request-actions">
            <form method="post" action="{{ route('friends.accept', $friendRequest) }}" data-rework-friend-request-action>
                @csrf
                <button type="submit">{{ __('ui.accept_friend_request') }}</button>
            </form>
            <form method="post" action="{{ route('friends.decline', $friendRequest) }}" data-rework-friend-request-action>
                @csrf
                <button type="submit">{{ __('ui.decline_friend_request') }}</button>
            </form>
        </div><small data-rework-friend-request-status hidden></small></div>
    </article>
@empty
    <div class="dropdown-empty"><i aria-hidden="true" class="ph ph-users-three ph-icon"></i><span>{{ __('ui.friend_requests_empty') }}</span></div>
@endforelse
