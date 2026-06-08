@php
    $viewer = $viewer ?? auth()->user();
    $partner = $conversation->otherParticipant($viewer);
    $conversationTitle = $conversation->displayTitleFor($viewer);
    $partnerAvatar = $partner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $partnerHandle = $partner?->username ? '@'.$partner->username : __('ui.private_conversation');
@endphp

<section class="hnt-chat-tab" data-hnt-chat-tab="{{ $conversation->id }}" data-conversation-id="{{ $conversation->id }}" aria-label="{{ $conversationTitle }}">
    <header class="hnt-chat-tab__header" data-hnt-chat-tab-toggle>
        <div class="hnt-chat-tab__person">
            <img src="{{ $partnerAvatar }}" alt="{{ $conversationTitle }}" class="hnt-chat-tab__avatar">
            <div class="hnt-chat-tab__meta">
                <strong>{{ $conversationTitle }}</strong>
                <span>{{ $partnerHandle }}</span>
            </div>
        </div>
        <div class="hnt-chat-tab__actions">
            <a href="{{ route('messages.show', $conversation) }}" class="hnt-chat-tab__icon" aria-label="{{ __('ui.messages_open_full') }}" data-hnt-chat-tab-full>
                <ion-icon name="expand-outline"></ion-icon>
            </a>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ __('ui.messages_minimize') }}" data-hnt-chat-tab-minimize>
                <ion-icon name="remove-outline"></ion-icon>
            </button>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ __('ui.messages_close_tab') }}" data-hnt-chat-tab-close>
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
    </header>

    <div class="hnt-chat-tab__body" data-hnt-chat-tab-body>
        <div class="hnt-chat-tab__messages" data-hnt-chat-tab-messages="{{ $conversation->id }}">
            @forelse ($messages as $message)
                @php
                    $isOwn = (int) $message->user_id === (int) $viewer->id;
                    $messageAvatar = $message->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                @endphp
                <div class="hnt-chat-tab__message {{ $isOwn ? 'is-own' : 'is-other' }}">
                    @unless ($isOwn)
                        <img src="{{ $messageAvatar }}" alt="{{ $message->user?->name ?: $conversationTitle }}" class="hnt-chat-tab__message-avatar">
                    @endunless
                    <div class="hnt-chat-tab__bubble-wrap">
                        <p class="hnt-chat-tab__bubble">{{ $message->body }}</p>
                        <span class="hnt-chat-tab__time">{{ $message->created_at?->format('H:i') }}</span>
                    </div>
                </div>
            @empty
                <div class="hnt-chat-tab__empty" data-hnt-chat-tab-empty>
                    <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                    <p>{{ __('ui.message_thread_empty_text') }}</p>
                </div>
            @endforelse
        </div>

        <form method="post" action="{{ route('messages.store', $conversation) }}" class="hnt-chat-tab__form" data-hnt-chat-tab-form>
            @csrf
            <input type="text" name="body" maxlength="3000" autocomplete="off" placeholder="{{ __('ui.write_message') }}" required>
            <button type="submit" aria-label="{{ __('ui.send') }}">
                <ion-icon name="send-outline"></ion-icon>
            </button>
        </form>
    </div>
</section>
