@php
    $hhChatPartner = $hhChatPartner ?? $hhChatConversation->otherParticipant($hhChatUser);
    $hhChatPartnerName = $hhChatPartner?->name ?: ($hhChatPartner?->username ?: __('ui.messages'));
    $hhChatPartnerAvatar = $hhChatPartner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $hhChatPartnerLevel = max(1, (int) ($hhChatPartner?->level ?: 1));
    $hhChatMessages = $hhChatMessages ?? $hhChatConversation->messages()->with('user.profile')->latest()->limit(30)->get()->reverse();
@endphp

<section class="hh-chat-widget hh-chat-conversation-panel is-hidden" data-hh-chat-panel="conversation-{{ $hhChatConversation->id }}" aria-hidden="true">
    <div class="hh-chat-widget-header">
        <button class="hh-chat-back" type="button" data-hh-chat-back aria-label="{{ __('ui.back_to_messages') }}">
            <svg><use xlink:href="#svg-back-arrow"></use></svg>
        </button>

        <div class="hh-chat-header-user">
            <span class="hh-chat-avatar-hex" aria-hidden="true">
                <span class="user-avatar small no-outline online">
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
            <span class="hh-chat-header-user-meta">
                <strong>{{ $hhChatPartnerName }}</strong>
                <em>{{ __('ui.online') }}</em>
            </span>
        </div>
    </div>

    <div class="hh-chat-widget-conversation" data-hh-chat-messages="{{ $hhChatConversation->id }}">
        @forelse ($hhChatMessages as $hhChatMessage)
            @php
                $hhIsOwnMessage = (int) $hhChatMessage->user_id === (int) $hhChatUser->id;
                $hhMessageAvatar = $hhChatMessage->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
            @endphp
            <div class="hh-chat-speaker {{ $hhIsOwnMessage ? 'right' : 'left' }}">
                @unless ($hhIsOwnMessage)
                    <span class="hh-chat-speaker-avatar" aria-hidden="true">
                        <span class="user-avatar tiny no-border">
                            <span class="user-avatar-content">
                                <span class="hexagon-image-24-26" data-src="{{ $hhMessageAvatar }}" style="background-image:url('{{ $hhMessageAvatar }}');"></span>
                            </span>
                        </span>
                    </span>
                @endunless
                <p class="hh-chat-speaker-message">{{ $hhChatMessage->body }}</p>
                <p class="hh-chat-speaker-timestamp">{{ $hhChatMessage->created_at?->format('H:i') }}</p>
            </div>
        @empty
            <div class="hh-chat-speaker left hh-chat-empty-thread-note">
                <p class="hh-chat-speaker-message">{{ __('ui.message_thread_empty_text') }}</p>
            </div>
        @endforelse
    </div>

    <form class="hh-chat-compose hh-chat-widget-form" method="POST" action="{{ route('messages.store', $hhChatConversation) }}" data-hh-chat-send-form data-conversation-id="{{ $hhChatConversation->id }}">
        @csrf
        <div class="hh-chat-interactive-input">
            <input type="text" name="body" maxlength="3000" autocomplete="off" placeholder="{{ __('ui.write_message') }}">
            <button type="submit" aria-label="{{ __('ui.messages') }}">
                <svg><use xlink:href="#svg-send-message"></use></svg>
            </button>
        </div>
    </form>

    <button class="hh-chat-widget-button" type="button" data-hh-chat-back aria-label="{{ __('ui.back_to_messages') }}">
        <span class="hh-chat-widget-button-icon" aria-hidden="true">
            <span class="burger-icon inverted">
                <span class="burger-icon-bar"></span>
                <span class="burger-icon-bar"></span>
                <span class="burger-icon-bar"></span>
            </span>
        </span>
        <span class="hh-chat-widget-button-text">{{ __('ui.messages_chat') }}</span>
    </button>
</section>
