@auth
    <div class="hnt-chat-tabs-shell" data-hnt-chat-tabs-shell aria-live="polite" data-hnt-i18n-open-error="{{ __('ui.message_chat_open_failed') }}" data-hnt-i18n-send-error="{{ __('ui.message_send_failed') }}"></div>
    @once
        @php($hntChatTabsStyleVersion = @filemtime(public_path('assets/socialite/css/hnt-socialite-chat-tabs.css')) ?: time())
        <link rel="stylesheet" href="{{ asset('assets/socialite/css/hnt-socialite-chat-tabs.css') }}?v={{ $hntChatTabsStyleVersion }}">
    @endonce
@endauth
