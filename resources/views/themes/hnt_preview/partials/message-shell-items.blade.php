@forelse ($previewMessageConversations as $conversation)
    @php
        $other = $conversation->otherParticipant($previewMessageUser);
        $conversationTitle = $conversation->displayTitleFor($previewMessageUser);
        $latestBody = $conversation->latestMessage?->body;
        $latestAt = $conversation->latestMessage?->created_at ?? $conversation->updated_at;
        $unread = $conversation->unreadCountFor($previewMessageUser);
        $avatarUrl = $other?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
        $handle = $other?->username ? '@'.$other->username : __('ui.private_conversation');
    @endphp
    <a class="hnt-message-shell-item {{ $unread > 0 ? 'is-unread' : '' }}"
       href="{{ route('messages.show', $conversation) }}"
       data-hnt-chat-tab-open
       data-hnt-chat-tab-url="{{ route('messages.chat-tab', $conversation) }}"
       data-hnt-chat-conversation-id="{{ $conversation->id }}">
        <span class="hnt-message-shell-avatar">
            <img src="{{ $avatarUrl }}" alt="">
            @include('partials.presence-indicator', ['user' => $other, 'viewer' => $previewMessageUser, 'size' => 'xs', 'class' => 'hnt-message-shell-presence'])
        </span>
        <span class="hnt-message-shell-copy">
            <span class="hnt-message-shell-title-row">
                <strong>{{ $conversationTitle }}</strong>
                @if ($unread > 0)
                    <em>{{ $unread }}</em>
                @endif
            </span>
            <span class="hnt-message-shell-preview">{{ $latestBody ? \Illuminate\Support\Str::limit($latestBody, 82) : __('ui.message_no_messages_yet') }}</span>
            <small>{{ $handle }} · {{ $latestAt?->diffForHumans() }}</small>
        </span>
        <span class="hnt-message-shell-arrow" aria-hidden="true">
            <i class="ph ph-caret-right" aria-hidden="true"></i>
        </span>
    </a>
@empty
    <div class="hnt-message-shell-empty">
        <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
        <h3>{{ __('ui.message_no_conversations') }}</h3>
        <p>{{ __('ui.message_no_conversations_text') }}</p>
    </div>
@endforelse
