<aside aria-label="{{ $t('Teamchat', 'Team chat') }}" class="team-fixed-column team-fixed-right" id="teamFixedRight">
<article class="team-chat-card">
<header>
<div><span>TEAMCHAT</span><h2>{{ $team->displayName() }}</h2></div>
<strong id="teamChatCount">{{ $chatMessagesCount }}</strong>
</header>
<div class="team-chat-list" id="teamChatList">
@forelse($chatMessages as $message)
@php
    $messageUser = $message->user;
    $isOwnMessage = (int) $message->user_id === (int) $viewer->id;
    $messageAuthor = $isOwnMessage
        ? $t('Du', 'You')
        : ($messageUser?->username ?: $messageUser?->name ?: 'Hunter');
    $messageAvatar = $messageUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
@endphp
<article class="{{ $isOwnMessage ? 'own' : 'other' }}">
@if(! $isOwnMessage)
<img alt="{{ $messageAuthor }}" src="{{ $messageAvatar }}"/>
@endif
<div>
<strong>{{ $messageAuthor }}</strong>
<p>{{ $message->body }}</p>
<time datetime="{{ $message->created_at?->toIso8601String() }}">{{ $message->created_at?->format('H:i') }}</time>
</div>
</article>
@empty
<article class="other">
<div>
<strong>{{ $t('Noch keine Nachrichten', 'No messages yet') }}</strong>
<p>{{ $t('Schreibt die erste Nachricht in euren Teamchat.', 'Send the first message to your team chat.') }}</p>
</div>
</article>
@endforelse
</div>
<form class="team-chat-compose" id="teamChatForm">
<img alt="{{ $viewer->username ?: $viewer->name ?: 'Hunter' }}" src="{{ $viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<input autocomplete="off" id="teamChatInput" maxlength="1200" placeholder="{{ $t('Nachricht schreiben …', 'Write a message …') }}"/>
<button aria-label="{{ $t('Nachricht senden', 'Send message') }}" type="submit">→</button>
</form>
</article>
</aside>