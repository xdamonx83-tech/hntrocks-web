@php
    $viewer = $viewer ?? auth()->user();
    $partner = $recipient;
    $conversationTitle = $partner->name ?: ($partner->username ?: 'Hunter');
    $partnerAvatar = $partner->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $partnerHandle = $partner->username ? '@'.$partner->username : __('hnt_preview.comms.new_conversation');
@endphp

<section class="hnt-chat-tab"
         data-hnt-chat-tab="draft-user-{{ $partner->id }}"
         data-conversation-id="draft-user-{{ $partner->id }}"
         data-hnt-chat-draft
         data-hnt-comms-name="{{ $conversationTitle }}"
         aria-label="{{ $conversationTitle }}">
    <header class="hnt-chat-tab__header" data-hnt-chat-tab-toggle>
        <div class="hnt-chat-tab__person">
            <span class="hnt-chat-tab__avatar-wrap">
                <img src="{{ $partnerAvatar }}" alt="{{ $conversationTitle }}" class="hnt-chat-tab__avatar">
                @include('partials.presence-indicator', ['user' => $partner, 'viewer' => $viewer, 'size' => 'xs', 'class' => 'hnt-chat-tab__presence'])
            </span>
            <div class="hnt-chat-tab__meta">
                <span class="hnt-chat-tab__channel">{{ __('hnt_preview.comms.private_draft') }}</span>
                <strong>{{ $conversationTitle }}</strong>
                <span>{{ $partnerHandle }}</span>
            </div>
        </div>
        <div class="hnt-chat-tab__actions">
            <a href="{{ route('messages.index', ['recipient_id' => $partner->id]) }}" class="hnt-chat-tab__icon" aria-label="{{ __('hnt_preview.comms.open_messages') }}" data-hnt-chat-tab-full>
                <ion-icon name="expand-outline"></ion-icon>
            </a>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ __('hnt_preview.comms.minimize') }}" data-hnt-chat-tab-minimize>
                <ion-icon name="remove-outline"></ion-icon>
            </button>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ __('hnt_preview.comms.close') }}" data-hnt-chat-tab-close>
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
    </header>

    <div class="hnt-chat-tab__body" data-hnt-chat-tab-body>
        <div class="hnt-chat-tab__messages" data-hnt-chat-tab-messages="draft-user-{{ $partner->id }}">
            <div class="hnt-chat-tab__empty" data-hnt-chat-tab-empty>
                <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                <p>{{ __('hnt_preview.comms.draft_empty') }}</p>
            </div>
        </div>

        <form method="post" action="{{ route('messages.start') }}" class="hnt-chat-tab__form" data-hnt-chat-tab-form>
            @csrf
            <input type="hidden" name="recipient_id" value="{{ $partner->id }}">
            <input type="text" name="body" maxlength="3000" autocomplete="off" placeholder="{{ __('hnt_preview.comms.write_message') }}" required>
            <button type="submit" aria-label="{{ __('hnt_preview.comms.send') }}">
                <ion-icon name="send-outline"></ion-icon>
            </button>
        </form>
    </div>
</section>