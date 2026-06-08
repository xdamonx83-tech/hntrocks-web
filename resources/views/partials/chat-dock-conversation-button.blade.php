@php
    $hhChatPartner = $hhChatConversation->otherParticipant($hhChatUser);
    $hhChatPartnerName = $hhChatPartner?->name ?: ($hhChatPartner?->username ?: __('ui.messages'));
    $hhChatPartnerAvatar = $hhChatPartner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $hhChatPartnerLevel = max(1, (int) ($hhChatPartner?->level ?: 1));
    $hhChatLatest = $hhChatConversation->latestMessage;
    $hhChatUnread = $hhChatConversation->unreadCountFor($hhChatUser);
    $hhChatStatusClass = $hhChatUnread > 0 ? 'offline' : 'online';
@endphp

<button class="hh-chat-widget-message {{ $hhChatUnread > 0 ? 'is-unread' : '' }}" type="button" data-hh-chat-conversation-trigger="{{ $hhChatConversation->id }}" data-read-url="{{ route('messages.read', $hhChatConversation) }}" data-hh-chat-search-item="{{ \Illuminate\Support\Str::lower($hhChatPartnerName) }}" aria-label="{{ $hhChatPartnerName }}">
    <span class="hh-chat-avatar-hex" aria-hidden="true">
        <span class="user-avatar small no-outline {{ $hhChatStatusClass }}">
            <span class="user-avatar-content">
                <span class="hexagon-image-30-32" data-src="{{ $hhChatPartnerAvatar }}" style="background-image:url('{{ $hhChatPartnerAvatar }}');"></span>
            </span>
            <span class="user-avatar-progress"><span class="hexagon-progress-40-44"></span></span>
            <span class="user-avatar-progress-border"><span class="hexagon-border-40-44"></span></span>
            <span class="user-avatar-badge">
                <span class="user-avatar-badge-border"><span class="hexagon-22-24"></span></span>
                <span class="user-avatar-badge-content"><span class="hexagon-dark-16-18"></span></span>
                <span class="user-avatar-badge-text">{{ $hhChatPartnerLevel }}</span>
            </span>
        </span>
    </span>
    <span class="hh-chat-message-meta">
        <strong>{{ $hhChatPartnerName }}</strong>
        <span>{{ $hhChatLatest ? \Illuminate\Support\Str::limit($hhChatLatest->body, 52) : __('ui.chat_offline_hint') }}</span>
    </span>
    <span class="hh-chat-message-time">
        <span>{{ $hhChatLatest?->created_at?->diffForHumans() }}</span>
        @if ($hhChatUnread > 0)
            <em data-hh-chat-unread="{{ $hhChatConversation->id }}">{{ $hhChatUnread }}</em>
        @endif
    </span>
</button>
