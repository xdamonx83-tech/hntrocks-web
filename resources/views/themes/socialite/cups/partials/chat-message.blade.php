@php
    $messageUser = $chatMessage->user;
    $messageProfileUrl = route('members.index');

    if ($messageUser?->username) {
        $messageProfileUrl = (int) $messageUser->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $messageUser);
    }

    $messageAvatar = $messageUser?->avatarUrl() ?: asset('assets/socialite/images/avatars/avatar-3.jpg');
@endphp

<article class="flex gap-3" data-cup-chat-message="{{ $chatMessage->id }}">
    <a href="{{ $messageProfileUrl }}" class="shrink-0">
        <img src="{{ $messageAvatar }}" alt="" class="rounded-full object-cover bg-white dark:bg-slate-800" style="width:36px;height:36px;min-width:36px;">
    </a>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <a href="{{ $messageProfileUrl }}" class="font-semibold text-black hover:text-blue-600 dark:text-white">{{ $messageUser?->name ?? __('ui.cup_unknown') }}</a>
            <span class="text-xs text-gray-500 dark:text-white/60">{{ $chatMessage->created_at?->diffForHumans() }}</span>
        </div>
        <p class="mt-0.5 text-sm leading-6 text-gray-600 dark:text-white/80">{!! nl2br(e($chatMessage->body)) !!}</p>
    </div>
</article>
