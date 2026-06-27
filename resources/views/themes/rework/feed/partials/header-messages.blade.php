@php
    $defaultAvatar = $defaultAvatar ?? asset('assets/vikinger/img/default-avatar.svg');
@endphp

@forelse($headerMessageConversations as $conversation)
    @php
        $other = $conversation->otherParticipant($viewer);
        $latestBody = $conversation->latestMessage?->body;
        $latestAt = $conversation->latestMessage?->created_at ?? $conversation->updated_at;
        $unread = $conversation->unreadCountFor($viewer);
    @endphp
    <a class="dropdown-item {{ $unread > 0 ? 'unread' : '' }}" href="{{ route('messages.show', $conversation) }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ route('messages.chat-tab', $conversation) }}" data-hnt-chat-conversation-id="{{ $conversation->id }}">
        <img alt="" src="{{ $other?->avatarUrl() ?: $defaultAvatar }}"/>
        <span><strong>{{ $conversation->displayTitleFor($viewer) }}</strong><small>{{ $latestBody ? \Illuminate\Support\Str::limit($latestBody, 82) : __('ui.message_no_messages_yet') }}</small></span>
        <em>{{ $latestAt?->diffForHumans() }}</em>
    </a>
@empty
    <div class="dropdown-empty"><i aria-hidden="true" class="ph ph-chat-circle-text ph-icon"></i><span>{{ __('ui.message_no_conversations') }}</span></div>
@endforelse
