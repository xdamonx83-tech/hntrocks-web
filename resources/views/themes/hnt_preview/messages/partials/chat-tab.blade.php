@php
    $viewer = $viewer ?? auth()->user();
    $partner = $conversation->otherParticipant($viewer);
    $conversationTitle = $conversation->displayTitleFor($viewer);
    $partnerAvatar = $partner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $partnerHandle = $partner?->username ? '@'.$partner->username : __('ui.private_conversation');
@endphp

<section class="hnt-chat-tab" data-hnt-chat-tab="{{ $conversation->id }}" data-conversation-id="{{ $conversation->id }}" data-hnt-chat-tab-messages-url="{{ route('messages.chat-tab.messages', $conversation) }}" aria-label="{{ $conversationTitle }}">
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
                <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
            </a>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ __('ui.messages_minimize') }}" data-hnt-chat-tab-minimize>
                <i class="ph ph-minus" aria-hidden="true"></i>
            </button>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ __('ui.messages_close_tab') }}" data-hnt-chat-tab-close>
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <div class="hnt-chat-tab__body" data-hnt-chat-tab-body>
        <div class="hnt-chat-tab__messages" data-hnt-chat-tab-messages="{{ $conversation->id }}">
            @include('themes.hnt_preview.messages.partials.chat-tab-messages', [
                'messages' => $messages,
                'viewer' => $viewer,
                'conversation' => $conversation,
                'conversationTitle' => $conversationTitle,
            ])
        </div>

        <form method="post" action="{{ route('messages.store', $conversation) }}" class="hnt-chat-tab__form" data-hnt-chat-tab-form
            @if ($conversation->type === 'private')
                data-hh-message-typing-form
                data-conversation-id="{{ $conversation->id }}"
                data-typing-url="{{ route('messages.typing', $conversation) }}"
                data-csrf-token="{{ csrf_token() }}"
            @endif>
            @csrf
            <input type="text" name="body" maxlength="3000" autocomplete="off" placeholder="{{ __('ui.write_message') }}" required @if ($conversation->type === 'private') data-hh-message-typing-input @endif>
            <button type="submit" aria-label="{{ __('ui.send') }}">
                <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</section>
