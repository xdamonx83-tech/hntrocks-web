@auth
    @php
        $hhChatUser = auth()->user();
        $hhChatConversations = \App\Models\Conversation::query()
            ->forUser($hhChatUser)
            ->where('type', 'private')
            ->with(['users.profile', 'latestMessage.user'])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    @endphp

    <aside class="hh-chat-dock" data-hh-chat-dock aria-label="{{ __('ui.messages_chat') }}">
        <section class="hh-chat-widget hh-chat-list-panel is-closed" data-hh-chat-list-panel data-hh-chat-panel="list" aria-hidden="false">
            <div class="hh-chat-widget-messages" data-hh-chat-list>
                @forelse ($hhChatConversations as $hhChatConversation)
                    @include('partials.chat-dock-conversation-button', [
                        'hhChatConversation' => $hhChatConversation,
                        'hhChatUser' => $hhChatUser,
                    ])
                @empty
                    <a class="hh-chat-widget-message hh-chat-empty-link" href="{{ route('members.index') }}">
                        <span class="hh-chat-avatar-hex" aria-hidden="true">
                            <span class="user-avatar small no-outline online">
                                <span class="user-avatar-content">
                                    <span class="hexagon-image-30-32" data-src="{{ asset('assets/vikinger/img/default-avatar.svg') }}" style="background-image:url('{{ asset('assets/vikinger/img/default-avatar.svg') }}');"></span>
                                </span>
                                <span class="user-avatar-progress"><span class="hexagon-progress-40-44"></span></span>
                                <span class="user-avatar-progress-border"><span class="hexagon-border-40-44"></span></span>
                            </span>
                        </span>
                        <span class="hh-chat-message-meta">
                            <strong>{{ __('ui.messages') }}</strong>
                            <span>{{ __('ui.no_conversations') }}</span>
                        </span>
                    </a>
                @endforelse
            </div>

            <div class="hh-chat-widget-form" role="search">
                <div class="hh-chat-interactive-input">
                    <input type="search" data-hh-chat-search placeholder="{{ __('ui.search_messages') }}" autocomplete="off">
                    <svg><use xlink:href="#svg-magnifying-glass"></use></svg>
                </div>
            </div>

            <button class="hh-chat-widget-button" type="button" data-hh-chat-dock-open="list" aria-label="{{ __('ui.open_messages') }}" style="border-radius: 0px !important;">
                <span class="hh-chat-widget-button-icon" aria-hidden="true">
                    <span class="burger-icon inverted">
                        <span class="burger-icon-bar"></span>
                        <span class="burger-icon-bar"></span>
                        <span class="burger-icon-bar"></span>
                    </span>
                </span>
                <span class="hh-chat-widget-button-text">{{ __('ui.messages_chat') }}</span>
            </button>
        </section>

        @foreach ($hhChatConversations as $hhChatConversation)
            @include('partials.chat-dock-conversation-panel', [
                'hhChatConversation' => $hhChatConversation,
                'hhChatUser' => $hhChatUser,
            ])
        @endforeach
    </aside>
@endauth
