@php
    $previewFriendRequestUser = auth()->user();
    $previewFriendRequests = collect();
    $previewFriendRequestsTotal = 0;

    if ($previewFriendRequestUser) {
        $previewFriendRequestsTotal = \App\Models\Friendship::query()
            ->where('recipient_id', $previewFriendRequestUser->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->count();

        $previewFriendRequests = \App\Models\Friendship::query()
            ->where('recipient_id', $previewFriendRequestUser->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->with('requester.profile')
            ->latest()
            ->limit(40)
            ->get();
    }
@endphp

<aside class="hnt-notification-shell hnt-friend-request-shell" id="hntFriendRequestShell" aria-label="{{ __('ui.friend_requests') }}" aria-hidden="true">
    <div class="hnt-notification-shell-head">
        <div>
            <span class="hnt-notification-shell-kicker">{{ __('ui.account') }}</span>
            <h2>{{ __('ui.friend_requests') }}</h2>
        </div>
        <div class="hnt-notification-shell-actions">
            <a class="hnt-notification-shell-icon" href="{{ route('members.index', ['relationship' => 'pending']) }}" aria-label="{{ __('ui.view_all_requests') }}">
                <i class="ph ph-users-three" aria-hidden="true"></i>
            </a>
            <button class="hnt-notification-shell-icon" type="button" data-hnt-friend-requests-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="hnt-notification-shell-meta">
        <span><strong data-hnt-friend-requests-count>{{ $previewFriendRequestsTotal }}</strong> {{ __('ui.friend_requests_open_label') }}</span>
        <span><strong data-hnt-friend-requests-visible-count>{{ $previewFriendRequests->count() }}</strong> {{ __('ui.notifications_total') }}</span>
    </div>

    <div class="hnt-notification-shell-list" data-hnt-friend-requests-list>
        @include('themes.hnt_preview.partials.friend-request-shell-items', [
            'previewFriendRequests' => $previewFriendRequests,
        ])
    </div>

    <div class="hnt-notification-shell-foot">
        <a href="{{ route('members.index', ['relationship' => 'pending']) }}">{{ __('ui.view_all_requests') }}</a>
        <a href="{{ route('members.index') }}">{{ __('ui.find_friends') }}</a>
    </div>
</aside>
