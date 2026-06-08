@php
    $previewMessageUser = auth()->user();
    $previewMessageConversations = collect();
    $previewMessagesTotal = 0;
    $previewMessagesUnread = 0;

    if ($previewMessageUser) {
        $previewMessagesTotal = \App\Models\Conversation::query()
            ->forUser($previewMessageUser)
            ->where('type', 'private')
            ->count();

        $previewMessagesUnread = method_exists($previewMessageUser, 'unreadMessagesCount')
            ? $previewMessageUser->unreadMessagesCount()
            : 0;

        $previewMessageConversations = \App\Models\Conversation::query()
            ->forUser($previewMessageUser)
            ->where('type', 'private')
            ->with(['users.profile', 'latestMessage.user'])
            ->latest('updated_at')
            ->limit(40)
            ->get();
    }
@endphp

<aside class="hnt-message-shell" id="hntMessageShell" aria-label="{{ __('ui.messages') }}" aria-hidden="true">
    <div class="hnt-message-shell-head">
        <div>
            <span class="hnt-message-shell-kicker">{{ __('ui.account') }}</span>
            <h2>{{ __('ui.messages') }}</h2>
        </div>
        <div class="hnt-message-shell-actions">
            <a class="hnt-message-shell-icon" href="{{ route('account.settings.edit') }}" aria-label="{{ __('ui.settings') }}">
                <i class="ph ph-gear-six" aria-hidden="true"></i>
            </a>
            <button class="hnt-message-shell-icon" type="button" data-hnt-messages-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="hnt-message-shell-meta">
        <span><strong data-hnt-messages-unread-count>{{ $previewMessagesUnread }}</strong> {{ __('ui.message_unread') }}</span>
        <span><strong data-hnt-messages-total-count>{{ $previewMessagesTotal }}</strong> {{ __('ui.message_conversations') }}</span>
    </div>

    <div class="hnt-message-shell-tabs" role="tablist" aria-label="{{ __('ui.messages') }}">
        <a class="active" href="{{ route('messages.index', ['type' => 'private']) }}">{{ __('ui.message_tab_private') }}</a>
        <a href="{{ route('messages.index', ['type' => 'lfg']) }}">{{ __('ui.message_tab_lfg') }}</a>
    </div>

    <div class="hnt-message-shell-list" data-hnt-message-shell-scroll>
        @include('themes.hnt_preview.partials.message-shell-items', [
            'previewMessageConversations' => $previewMessageConversations,
            'previewMessageUser' => $previewMessageUser,
        ])
    </div>

    <div class="hnt-message-shell-foot">
        <a href="{{ route('messages.index') }}">{{ __('ui.view_all_messages') }}</a>
        <a href="{{ route('account.settings.edit') }}">{{ __('ui.settings') }}</a>
    </div>
</aside>
