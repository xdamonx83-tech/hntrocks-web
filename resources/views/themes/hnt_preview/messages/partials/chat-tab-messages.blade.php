@forelse ($messages as $message)
    @php
        $isOwn = (int) $message->user_id === (int) $viewer->id;
        $messageAvatar = $message->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    @endphp
    <div class="hnt-chat-tab__message {{ $isOwn ? 'is-own' : 'is-other' }}" data-hnt-chat-message-id="{{ $message->id }}">
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
        <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
        <p>{{ __('ui.message_thread_empty_text') }}</p>
    </div>
@endforelse
