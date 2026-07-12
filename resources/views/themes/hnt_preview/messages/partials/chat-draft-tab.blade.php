@php
    $viewer = $viewer ?? auth()->user();
    $partner = $recipient;
    $conversationTitle = $partner->name ?: ($partner->username ?: (app()->getLocale() === 'en' ? 'Hunter' : 'Hunter'));
    $partnerAvatar = $partner->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $partnerHandle = $partner->username ? '@'.$partner->username : (app()->getLocale() === 'en' ? 'New conversation' : 'Neue Unterhaltung');
    $isEnglish = app()->getLocale() === 'en';
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
                <span class="hnt-chat-tab__channel">{{ $isEnglish ? 'PRIVATE DRAFT' : 'PRIVATER ENTWURF' }}</span>
                <strong>{{ $conversationTitle }}</strong>
                <span>{{ $partnerHandle }}</span>
            </div>
        </div>
        <div class="hnt-chat-tab__actions">
            <a href="{{ route('messages.index', ['recipient_id' => $partner->id]) }}" class="hnt-chat-tab__icon" aria-label="{{ $isEnglish ? 'Open messages' : 'Nachrichten öffnen' }}" data-hnt-chat-tab-full>
                <ion-icon name="expand-outline"></ion-icon>
            </a>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ $isEnglish ? 'Minimize' : 'Minimieren' }}" data-hnt-chat-tab-minimize>
                <ion-icon name="remove-outline"></ion-icon>
            </button>
            <button type="button" class="hnt-chat-tab__icon" aria-label="{{ $isEnglish ? 'Close' : 'Schließen' }}" data-hnt-chat-tab-close>
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
    </header>

    <div class="hnt-chat-tab__body" data-hnt-chat-tab-body>
        <div class="hnt-chat-tab__messages" data-hnt-chat-tab-messages="draft-user-{{ $partner->id }}">
            <div class="hnt-chat-tab__empty" data-hnt-chat-tab-empty>
                <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                <p>{{ $isEnglish ? 'No conversation exists yet. It is created only after you send the first message.' : 'Noch keine Unterhaltung. Sie wird erst erstellt, wenn du die erste Nachricht sendest.' }}</p>
            </div>
        </div>

        <form method="post" action="{{ route('messages.start') }}" class="hnt-chat-tab__form" data-hnt-chat-tab-form>
            @csrf
            <input type="hidden" name="recipient_id" value="{{ $partner->id }}">
            <input type="text" name="body" maxlength="3000" autocomplete="off" placeholder="{{ $isEnglish ? 'Write a message' : 'Nachricht schreiben' }}" required>
            <button type="submit" aria-label="{{ $isEnglish ? 'Send' : 'Senden' }}">
                <ion-icon name="send-outline"></ion-icon>
            </button>
        </form>
    </div>
</section>