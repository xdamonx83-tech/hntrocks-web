@php
    $messageUser = $chatMessage->user;
    $messageProfileUrl = route('members.index');

    if ($messageUser?->username) {
        $messageProfileUrl = (int) ($messageUser->id ?? 0) === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $messageUser);
    }

    $messageAvatar = $messageUser?->avatarUrl() ?: asset('assets/socialite/images/avatars/avatar-3.jpg');
    $messageName = $messageUser?->name ?: ($messageUser?->username ?: 'Unbekannter Spieler');
    $isOwnMessage = auth()->check() && (int) ($messageUser?->id ?? 0) === (int) auth()->id();
@endphp

<article class="cup-chat-message {{ $isOwnMessage ? 'is-own' : '' }}" data-cup-chat-message="{{ $chatMessage->id }}">
    <a class="cup-chat-avatar" href="{{ $messageProfileUrl }}">
        <img src="{{ $messageAvatar }}" alt="">
    </a>

    <div class="cup-chat-message-body">
        <div class="cup-chat-message-head">
            <a href="{{ $messageProfileUrl }}">{{ $messageName }}</a>
            <span>{{ $chatMessage->created_at?->diffForHumans() }}</span>
        </div>

        <div class="cup-chat-bubble">
            <p>{!! nl2br(e($chatMessage->body)) !!}</p>
        </div>
    </div>
</article>
