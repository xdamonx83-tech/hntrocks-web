@foreach ($previewFriendRequests as $friendRequest)
    @php
        $requester = $friendRequest->requester;
        $requesterName = $requester?->name ?: ($requester?->username ?: 'hnt.rocks');
        $requesterAvatar = $requester?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
        $requesterUrl = $requester ? route('profile.public', $requester) : route('members.index');
        $requesterHandle = $requester?->username ? '@'.$requester->username : __('ui.members');
    @endphp
    <article class="hnt-notification-shell-item hnt-friend-request-shell-item is-unread" data-hnt-friend-request-item="{{ $friendRequest->id }}">
        <a class="hnt-notification-shell-avatar" href="{{ $requesterUrl }}" aria-label="{{ $requesterName }}">
            <img src="{{ $requesterAvatar }}" alt="">
        </a>
        <div class="hnt-notification-shell-copy">
            <div class="hnt-notification-shell-title-row">
                <h3>{{ $requesterName }}</h3>
                <span>{{ __('ui.notification_unread') }}</span>
            </div>
            <p>{{ __('ui.friend_request_wants_connect') }}</p>
            <small>{{ $requesterHandle }} · <time datetime="{{ $friendRequest->created_at?->toIso8601String() }}">{{ $friendRequest->created_at?->diffForHumans() }}</time></small>
        </div>
        <div class="hnt-notification-shell-item-action hnt-friend-request-shell-actions">
            <form method="post" action="{{ route('friends.accept', $friendRequest) }}" data-hnt-friend-request-action data-hnt-friend-request-id="{{ $friendRequest->id }}">
                @csrf
                <button class="is-accept" type="submit" title="{{ __('ui.accept_friend_request') }}" aria-label="{{ __('ui.accept_friend_request') }}">
                    <i class="ph ph-check" aria-hidden="true"></i>
                </button>
            </form>
            <form method="post" action="{{ route('friends.decline', $friendRequest) }}" data-hnt-friend-request-action data-hnt-friend-request-id="{{ $friendRequest->id }}">
                @csrf
                <button class="is-decline" type="submit" title="{{ __('ui.decline_friend_request') }}" aria-label="{{ __('ui.decline_friend_request') }}">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </article>
@endforeach

<div class="hnt-notification-shell-empty" data-hnt-friend-requests-empty @if($previewFriendRequests->isNotEmpty()) hidden @endif>
    <i class="ph ph-users-three" aria-hidden="true"></i>
    <h3>{{ __('ui.no_friend_requests') }}</h3>
    <p>{{ __('ui.more_friend_requests') }}</p>
</div>
