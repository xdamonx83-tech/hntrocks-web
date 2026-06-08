@extends('layouts.app')

@section('title', __('ui.messages'))

@section('content')
@php
    $viewer = auth()->user();
    $selectedOther = $selectedConversation?->otherParticipant($viewer);
    $activeMessageType = $activeMessageType ?? 'private';
    $messageTypeCounts = $messageTypeCounts ?? ['private' => 0, 'lfg' => 0];
    $isLfgMessageTab = $activeMessageType === 'lfg';
    $conversationTotal = method_exists($conversations, 'total') ? $conversations->total() : $conversations->count();
    $unreadTotal = method_exists($viewer, 'unreadMessagesCount') ? $viewer->unreadMessagesCount() : 0;
@endphp

<div class="hh-profile-messages-page">
    <div class="section-banner hh-account-hub-banner">
        <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="" aria-hidden="true">
        <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
        <p class="section-banner-text">{{ __('ui.messages_banner_text') }}</p>
    </div>

    <div class="grid grid-3-9 medium-space hh-account-hub-grid hh-profile-messages-grid">
        @include('teams.partials.account-sidebar', [
            'activeSection' => 'profile',
            'activeLink' => 'messages',
        ])

        <div class="account-hub-content">
            <div class="section-header">
                <div class="section-header-info">
                    <p class="section-pretitle">{{ __('ui.my_profile') }}</p>
                    <h2 class="section-title">{{ __('ui.messages') }}</h2>
                </div>

                <div class="section-header-actions hh-profile-messages-header-actions">
                    <p class="section-header-action"><span class="highlighted">{{ $conversationTotal }}</span> {{ __('ui.message_conversations') }}</p>
                    <p class="section-header-action"><span class="highlighted">{{ $unreadTotal }}</span> {{ __('ui.message_unread') }}</p>
                </div>
            </div>

            @if (session('status'))
                <div class="hh-alert hh-alert-success hh-profile-messages-alert">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="hh-alert hh-alert-danger hh-profile-messages-alert">
                    <strong>{{ __('ui.please_check') }}</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="hh-profile-message-tabs" role="tablist" aria-label="{{ __('ui.messages') }}">
                <a class="hh-profile-message-tab {{ $activeMessageType === 'private' ? 'active' : '' }}" href="{{ route('messages.index', ['type' => 'private']) }}">
                    <span>{{ __('ui.message_tab_private') }}</span>
                    <em>{{ $messageTypeCounts['private'] ?? 0 }}</em>
                </a>
                <a class="hh-profile-message-tab {{ $activeMessageType === 'lfg' ? 'active' : '' }}" href="{{ route('messages.index', ['type' => 'lfg']) }}">
                    <span>{{ __('ui.message_tab_lfg') }}</span>
                    <em>{{ $messageTypeCounts['lfg'] ?? 0 }}</em>
                </a>
            </div>

            <div class="chat-widget-wrap hh-profile-messages-wrap">
                <div class="chat-widget static hh-profile-conversation-list-widget">
                    <div class="chat-widget-messages" data-simplebar>
                        @forelse ($conversations as $conversation)
                            @php
                                $other = $conversation->otherParticipant($viewer);
                                $unread = $conversation->unreadCountFor($viewer);
                                $latestBody = $conversation->latestMessage?->body;
                                $latestAt = $conversation->latestMessage?->created_at ?? $conversation->updated_at;
                            @endphp

                            <a class="chat-widget-message hh-profile-conversation-link {{ $selectedConversation?->id === $conversation->id ? 'active' : '' }} {{ $unread > 0 ? 'is-unread' : '' }}" href="{{ route('messages.show', $conversation) }}">
                                <div class="user-status">
                                    <div class="user-status-avatar">
                                        <div class="user-avatar small no-outline">
                                            <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $other?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                                            <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                            <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                            <div class="user-avatar-badge">
                                                <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                                <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                                <p class="user-avatar-badge-text">{{ $other?->level ?? 1 }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="user-status-title"><span class="bold">
                                        @if ($conversation->isLfgConversation())
                                            <span class="hh-profile-message-type-badge">{{ $conversation->contextBadgeLabel() ?? __('ui.message_lfg_badge') }}</span>
                                        @endif
                                        {{ $conversation->displayTitleFor($viewer) }}
                                    </span></p>
                                    <p class="user-status-text">{{ $latestBody ? Str::limit($latestBody, 76) : __('ui.message_no_messages_yet') }}</p>
                                    <p class="user-status-timestamp floaty">
                                        {{ $latestAt?->diffForHumans() }}
                                        @if ($unread > 0)
                                            <span class="hh-profile-message-unread">{{ $unread }}</span>
                                        @endif
                                    </p>
                                </div>
                            </a>
                        @empty
                            <div class="hh-profile-message-empty-list">
                                <p class="hh-profile-message-empty-title">{{ $isLfgMessageTab ? __('ui.message_no_lfg_conversations') : __('ui.message_no_conversations') }}</p>
                                <p class="hh-profile-message-empty-text">{{ $isLfgMessageTab ? __('ui.message_no_lfg_conversations_text') : __('ui.message_no_conversations_text') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($conversations->hasPages())
                        <div class="chat-widget-form hh-profile-message-pagination">
                            {{ $conversations->links() }}
                        </div>
                    @endif
                </div>

                <div class="chat-widget hh-profile-message-thread-widget {{ $selectedConversation ? '' : 'is-empty' }}">
                    @if ($selectedConversation)
                        <div class="chat-widget-header">
                            <div class="user-status">
                                @if ($selectedOther)
                                    <div class="user-status-avatar">
                                        <a class="user-avatar small no-outline" href="{{ route('profile.public', $selectedOther) }}">
                                            <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $selectedOther->avatarUrl() }}"></div></div>
                                            <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                            <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                            <div class="user-avatar-badge">
                                                <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                                <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                                <p class="user-avatar-badge-text">{{ $selectedOther->level ?? 1 }}</p>
                                            </div>
                                        </a>
                                    </div>
                                @endif

                                <p class="user-status-title"><span class="bold">
                                    @if ($selectedConversation->isLfgConversation())
                                        <span class="hh-profile-message-type-badge">{{ $selectedConversation->contextBadgeLabel() ?? __('ui.message_lfg_badge') }}</span>
                                    @endif
                                    {{ $selectedConversation->displayTitleFor($viewer) }}
                                </span></p>
                                <p class="user-status-text">
                                    @if ($selectedConversation->isLfgConversation())
                                        {{ __('ui.message_lfg_context') }}{{ $selectedOther ? ' · @'.$selectedOther->username : '' }}
                                    @else
                                        {{ $selectedOther ? '@'.$selectedOther->username : __('ui.private_conversation') }}
                                    @endif
                                </p>
                                @if ($selectedOther)
                                    <p class="user-status-tag online">{{ __('ui.active_chat') }}</p>
                                @endif
                            </div>

                            @if ($selectedOther)
                                <a class="hh-profile-message-profile-link" href="{{ route('profile.public', $selectedOther) }}">{{ __('ui.view_profile') }}</a>
                            @endif
                        </div>
                    @endif

                    @if ($selectedConversation?->isLfgConversation())
                        <div class="hh-profile-message-context-card">
                            <div>
                                <p class="hh-profile-message-context-label">{{ __('ui.message_lfg_context') }}</p>
                                <p class="hh-profile-message-context-title">{{ $selectedConversation->context_label ?? $selectedConversation->displayTitleFor($viewer) }}</p>
                            </div>
                            @if ($selectedConversation->context_url)
                                <a class="hh-profile-message-context-link" href="{{ $selectedConversation->context_url }}">{{ __('ui.message_lfg_context_open') }}</a>
                            @endif
                        </div>
                    @endif

                    <div class="chat-widget-conversation" data-simplebar data-hh-profile-message-thread>
                        @if ($selectedConversation)
                            @forelse ($messages as $message)
                                @if ($message->isSystemMessage())
                                    <div class="chat-widget-speaker hh-profile-message-system">
                                        <p class="chat-widget-speaker-message">{{ $message->body }}</p>
                                        <p class="chat-widget-speaker-timestamp">{{ $message->created_at->diffForHumans() }}</p>
                                    </div>
                                @else
                                    @php($isOwnMessage = (int) $message->user_id === (int) $viewer->id)
                                    <div class="chat-widget-speaker {{ $isOwnMessage ? 'right' : 'left' }}">
                                        @unless ($isOwnMessage)
                                            <div class="chat-widget-speaker-avatar">
                                                <a class="user-avatar tiny no-border" href="{{ route('profile.public', $message->user) }}">
                                                    <div class="user-avatar-content"><div class="hexagon-image-24-26" data-src="{{ $message->user->avatarUrl() }}"></div></div>
                                                </a>
                                            </div>
                                        @endunless

                                        <p class="chat-widget-speaker-message">{{ $message->body }}</p>
                                        <p class="chat-widget-speaker-timestamp">{{ $message->created_at->diffForHumans() }}</p>
                                    </div>
                                @endif
                            @empty
                                <div class="hh-profile-message-thread-empty">
                                    <p class="hh-profile-message-empty-title">{{ __('ui.message_thread_empty') }}</p>
                                    <p class="hh-profile-message-empty-text">{{ __('ui.message_thread_empty_text') }}</p>
                                </div>
                            @endforelse
                        @else
                            <div class="hh-profile-message-thread-empty">
                                <p class="hh-profile-message-empty-title">{{ __('ui.message_choose_conversation') }}</p>
                                <p class="hh-profile-message-empty-text">{{ $isLfgMessageTab ? __('ui.message_lfg_choose_conversation_text') : __('ui.message_choose_conversation_text') }}</p>
                            </div>
                        @endif
                    </div>

                    @if ($selectedConversation)
                        <form class="chat-widget-form hh-profile-message-reply-form" method="post" action="{{ route('messages.store', $selectedConversation) }}" data-hh-profile-message-reply-form data-conversation-id="{{ $selectedConversation->id }}" data-empty-message="{{ __('ui.message_empty_error') }}" data-send-error="{{ __('ui.message_send_failed') }}" novalidate>
                            @csrf
                            <div class="interactive-input small">
                                <input type="text" id="message_body" name="body" value="{{ old('body') }}" placeholder="{{ __('ui.message_write_placeholder') }}" autocomplete="off" maxlength="3000" data-hh-profile-message-input aria-describedby="message_body_error">
                                <button class="hh-profile-message-submit" type="submit" aria-label="{{ __('ui.send') }}" data-hh-profile-message-send>
                                    <i class="interactive-input-action-icon hh-ph-action-icon ph ph-paper-plane-tilt" aria-hidden="true"></i>
                                </button>
                            </div>
                            <p class="hh-profile-message-inline-error" id="message_body_error" data-hh-profile-message-error hidden></p>
                        </form>
                    @else
                        <div class="chat-widget-form hh-profile-message-reply-form hh-profile-message-reply-disabled">
                            <p>{{ __('ui.message_select_to_reply') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if ($activeMessageType === 'private')
            <div class="widget-box hh-profile-message-start-widget" id="new-message">
                <p class="widget-box-title">{{ __('ui.message_start_new') }}</p>
                <div class="widget-box-content">
                    <form class="form" method="post" action="{{ route('messages.start') }}">
                        @csrf
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select">
                                    <label for="recipient_id">{{ __('ui.message_recipient') }}</label>
                                    <select id="recipient_id" name="recipient_id" required>
                                        <option value="">{{ __('ui.message_choose_player') }}</option>
                                        @foreach ($recipients as $recipient)
                                            <option value="{{ $recipient->id }}" @selected((string) old('recipient_id') === (string) $recipient->id)>{{ $recipient->name }} ({{ '@'.$recipient->username }})</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                            </div>

                            <div class="form-item">
                                <div class="form-input active">
                                    <label for="new_message_body">{{ __('ui.message') }}</label>
                                    <input id="new_message_body" name="body" type="text" value="{{ old('body') }}" placeholder="{{ __('ui.message_start_placeholder') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="hh-profile-message-start-actions">
                            <button class="button primary" type="submit">{{ __('ui.message_start_button') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
