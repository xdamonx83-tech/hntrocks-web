<header class="header">
    <div class="header-actions">
        <a class="header-brand" href="{{ route('home') }}" aria-label="hnt.rocks">
            <div class="logo hh-brand-logo-wrap">
                <img class="hh-brand-logo" src="{{ asset('assets/vikinger/img/brand/hunthub-logo.svg') }}" alt="hnt.rocks">
            </div>
            <h1 class="header-brand-text">hnt.rocks</h1>
        </a>
    </div>

    @auth
        @php
            $hhUser = auth()->user();
            $hhUnreadMessages = $hhUser->unreadMessagesCount(['private']);
            $hhUnreadLfgMessages = $hhUser->unreadMessagesCount(['lfg', 'team_lfg']);
            $hhHeaderLfgConversations = \App\Models\Conversation::query()
                ->forUser($hhUser)
                ->whereIn('type', ['lfg', 'team_lfg'])
                ->with(['users.profile', 'latestMessage.user'])
                ->latest('updated_at')
                ->limit(6)
                ->get();
            $hhUnreadNotifications = $hhUser->notificationItems()->standard()->unread()->count();
            $hhHeaderNotifications = $hhUser->notificationItems()
                ->standard()
                ->with('actor.profile')
                ->latest()
                ->limit(6)
                ->get();
            $hhPendingFriendRequestCount = \App\Models\Friendship::query()
                ->where('recipient_id', $hhUser->id)
                ->where('status', \App\Models\Friendship::STATUS_PENDING)
                ->count();
            $hhHeaderFriendRequests = \App\Models\Friendship::query()
                ->where('recipient_id', $hhUser->id)
                ->where('status', \App\Models\Friendship::STATUS_PENDING)
                ->with('requester.profile')
                ->latest()
                ->limit(6)
                ->get();
            $hhCurrentLocale = app()->getLocale();
            $hhHeaderNavigationKeys = ['feed', 'members', 'teams', 'lfg', 'moments'];
            $hhHeaderNavigationItems = collect(app(\App\Services\Navigation\SidebarMenuService::class)->itemsForUser($hhUser))
                ->filter(fn (array $item): bool => in_array($item['key'] ?? '', $hhHeaderNavigationKeys, true))
                ->values();
            $hhLevel = max(1, (int) ($hhUser->level ?: 1));
            $hhXpTotal = (int) ($hhUser->xp_total ?: 0);
            $hhNextXp = max(250, $hhLevel * 250);
            $hhLevelProgress = min(100, (int) round(($hhXpTotal % $hhNextXp) / $hhNextXp * 100));
        @endphp

        <div class="header-actions hh-header-menu-actions">
            <button class="sidemenu-trigger navigation-widget-trigger hh-icon-reset" type="button" aria-controls="navigation-widget" aria-expanded="false" aria-label="{{ __('ui.open_menu') }}">
                <i class="hh-ph-header-icon ph ph-squares-four" aria-hidden="true"></i>
            </button>

            <button class="mobilemenu-trigger navigation-widget-mobile-trigger hh-icon-reset" type="button" aria-controls="navigation-widget-mobile" aria-expanded="false" aria-label="{{ __('ui.open_menu') }}">
                <span class="burger-icon inverted">
                    <span class="burger-icon-bar"></span>
                    <span class="burger-icon-bar"></span>
                    <span class="burger-icon-bar"></span>
                </span>
            </button>

            <nav class="navigation hh-header-navigation" aria-label="{{ __('ui.main_navigation') }}">
                <ul class="menu-main">
                    @foreach ($hhHeaderNavigationItems as $hhHeaderNavigationItem)
                        @php
                            $hhHeaderMatchPatterns = collect(explode(',', (string) ($hhHeaderNavigationItem['match'] ?? '')))
                                ->map(fn (string $pattern): string => trim($pattern))
                                ->filter()
                                ->all();
                            $hhHeaderNavigationActive = $hhHeaderMatchPatterns !== [] && request()->routeIs(...$hhHeaderMatchPatterns);
                        @endphp
                        <li class="menu-main-item">
                            <a class="menu-main-item-link {{ $hhHeaderNavigationActive ? 'active' : '' }}" href="{{ $hhHeaderNavigationItem['url'] }}" @if(! empty($hhHeaderNavigationItem['external'])) target="_blank" rel="noopener" @endif>
                                {{ $hhHeaderNavigationItem['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div class="header-actions search-bar hh-header-search">
            <form class="interactive-input dark" method="get" action="{{ route('members.index') }}">
                <input type="text" id="search-main" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.header_search_placeholder') }}">
                <button class="interactive-input-icon-wrap hh-search-submit" type="submit" aria-label="{{ __('ui.search') }}">
                    <i class="interactive-input-icon hh-ph-header-icon ph ph-magnifying-glass" aria-hidden="true"></i>
                </button>
            </form>
        </div>

        <div class="header-actions hh-header-progress-wrap">
            <div class="progress-stat">
                <div class="bar-progress-wrap">
                    <p class="bar-progress-info">Lv. {{ $hhLevel }} · <span>{{ $hhXpTotal }} XP</span></p>
                </div>
                <div class="progress-stat-bar hh-static-progress"><span style="width: {{ $hhLevelProgress }}%"></span></div>
            </div>
        </div>

        <div class="header-actions hh-mobile-alert-actions" aria-label="{{ __('ui.mobile_quick_notifications') }}">
            @php
                $hhMobileUnreadMessages = (int) $hhUnreadMessages + (int) $hhUnreadLfgMessages;
            @endphp
            <a class="hh-mobile-alert-link" href="{{ route('messages.index') }}" aria-label="{{ __('ui.messages') }}">
                <i class="ph ph-chats-circle" aria-hidden="true"></i>
                @if ($hhMobileUnreadMessages > 0)
                    <span class="hh-mobile-alert-badge">{{ $hhMobileUnreadMessages > 99 ? '99+' : $hhMobileUnreadMessages }}</span>
                @endif
            </a>
            <button class="hh-mobile-alert-link hh-header-dropdown-trigger" type="button" data-hh-header-dropdown-trigger="mobile-friend-requests" aria-label="{{ __('ui.friend_requests') }}" aria-haspopup="true" aria-expanded="false">
                <i class="ph ph-user-plus" aria-hidden="true"></i>
                @if ($hhPendingFriendRequestCount > 0)
                    <span class="hh-mobile-alert-badge" data-hh-friend-request-count>{{ $hhPendingFriendRequestCount > 99 ? '99+' : $hhPendingFriendRequestCount }}</span>
                @endif
            </button>
            <button class="hh-mobile-alert-link hh-header-dropdown-trigger" type="button" data-hh-header-dropdown-trigger="mobile-notifications" aria-label="{{ __('ui.notifications') }}" aria-haspopup="true" aria-expanded="false">
                <i class="ph ph-bell" aria-hidden="true"></i>
                @if ($hhUnreadNotifications > 0)
                    <span class="hh-mobile-alert-badge" data-hh-notification-count>{{ $hhUnreadNotifications > 99 ? '99+' : $hhUnreadNotifications }}</span>
                @endif
            </button>
        </div>

        <div class="hh-mobile-header-sheet" data-hh-header-dropdown="mobile-friend-requests" aria-label="{{ __('ui.friend_requests') }}" role="dialog" aria-modal="true">
            <div class="hh-mobile-header-sheet-head">
                <div>
                    <strong>{{ __('ui.friend_requests') }}</strong>
                    <span>{{ $hhPendingFriendRequestCount > 0 ? $hhPendingFriendRequestCount : __('ui.all') }}</span>
                </div>
                <div class="hh-mobile-header-sheet-head-actions">
                    <a href="{{ route('members.index', ['relationship' => 'pending']) }}">{{ __('ui.view_all') }}</a>
                    <button class="hh-mobile-header-sheet-close" type="button" data-hh-header-dropdown-close aria-label="{{ __('ui.close') }}">
                        <i class="ph ph-x" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="hh-mobile-header-sheet-list" data-hh-dropdown-list="mobile-friend-requests">
                @forelse ($hhHeaderFriendRequests as $hhFriendRequest)
                    @php
                        $hhRequester = $hhFriendRequest->requester;
                        $hhRequesterName = $hhRequester?->name ?: ($hhRequester?->username ?: 'hnt.rocks');
                        $hhRequesterAvatar = $hhRequester?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                        $hhRequesterLevel = max(1, (int) ($hhRequester?->level ?: 1));
                        $hhRequesterUrl = $hhRequester ? route('profile.public', $hhRequester) : route('members.index');
                    @endphp
                    <div class="hh-mobile-header-sheet-item" data-hh-dropdown-item data-hh-mobile-friend-request-item="{{ $hhFriendRequest->id }}">
                        <a class="hh-mobile-header-sheet-avatar" href="{{ $hhRequesterUrl }}" aria-label="{{ $hhRequesterName }}">
                            <img src="{{ $hhRequesterAvatar }}" alt="">
                            <em>{{ $hhRequesterLevel }}</em>
                        </a>
                        <div class="hh-mobile-header-sheet-body">
                            <a class="hh-mobile-header-sheet-title" href="{{ $hhRequesterUrl }}">{{ $hhRequesterName }}</a>
                            <p>{{ __('ui.friend_request_wants_connect') }}</p>
                            <div class="hh-mobile-header-sheet-actions">
                                <form class="hh-header-ajax-form" method="POST" action="{{ route('friends.accept', $hhFriendRequest) }}" data-hh-header-ajax-form data-remove-on-success="[data-hh-mobile-friend-request-item='{{ $hhFriendRequest->id }}'],[data-hh-friend-request-item='{{ $hhFriendRequest->id }}']" data-empty-list="mobile-friend-requests" data-empty-message="{{ __('ui.no_friend_requests') }}" data-more-message="{{ __('ui.more_friend_requests') }}">
                                    @csrf
                                    <button type="submit">{{ __('ui.accept_friend_request') }}</button>
                                </form>
                                <form class="hh-header-ajax-form" method="POST" action="{{ route('friends.decline', $hhFriendRequest) }}" data-hh-header-ajax-form data-remove-on-success="[data-hh-mobile-friend-request-item='{{ $hhFriendRequest->id }}'],[data-hh-friend-request-item='{{ $hhFriendRequest->id }}']" data-empty-list="mobile-friend-requests" data-empty-message="{{ __('ui.no_friend_requests') }}" data-more-message="{{ __('ui.more_friend_requests') }}">
                                    @csrf
                                    <button type="submit" class="secondary">{{ __('ui.decline_friend_request') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="hh-mobile-header-sheet-empty">{{ __('ui.no_friend_requests') }}</div>
                @endforelse
            </div>
        </div>

        <div class="hh-mobile-header-sheet" data-hh-header-dropdown="mobile-notifications" aria-label="{{ __('ui.notifications') }}" role="dialog" aria-modal="true">
            <div class="hh-mobile-header-sheet-head">
                <div>
                    <strong>{{ __('ui.notifications') }}</strong>
                    <span>{{ __('ui.all') }}</span>
                </div>
                <div class="hh-mobile-header-sheet-head-actions">
                    <a href="{{ route('notifications.index') }}">{{ __('ui.view_all') }}</a>
                    <button class="hh-mobile-header-sheet-close" type="button" data-hh-header-dropdown-close aria-label="{{ __('ui.close') }}">
                        <i class="ph ph-x" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            @if ($hhUnreadNotifications > 0)
                <form class="hh-mobile-header-sheet-markall hh-header-ajax-form" method="POST" action="{{ route('notifications.read-all') }}" data-hh-header-ajax-form data-clear-dropdown="mobile-notifications" data-empty-message="{{ __('ui.no_new_notifications') }}" data-remove-on-success="[data-hh-mobile-mark-all-notifications]" data-hh-mobile-mark-all-notifications>
                    @csrf
                    <button type="submit">{{ __('ui.mark_all_read') }}</button>
                </form>
            @endif
            <div class="hh-mobile-header-sheet-list" data-hh-dropdown-list="mobile-notifications">
                @forelse ($hhHeaderNotifications as $hhNotification)
                    @php
                        $hhActor = $hhNotification->actor;
                        $hhNotificationTitle = $hhNotification->displayTitle();
                        $hhNotificationBody = $hhNotification->displayBody();
                        $hhActorName = $hhActor?->name ?: 'hnt.rocks';
                        $hhActorAvatar = $hhActor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                        $hhNotificationType = (string) $hhNotification->type;
                        $hhNotificationPhosphorIcon = match (true) {
                            str_contains($hhNotificationType, 'comment') => 'chat-circle-text',
                            str_contains($hhNotificationType, 'like'), str_contains($hhNotificationType, 'reaction') => 'thumbs-up',
                            str_contains($hhNotificationType, 'message') => 'chats-circle',
                            str_contains($hhNotificationType, 'friend') => 'user-plus',
                            default => 'bell',
                        };
                    @endphp
                    <form class="hh-mobile-header-sheet-item hh-mobile-header-notification {{ $hhNotification->isUnread() ? 'is-unread' : '' }} hh-header-ajax-form" method="POST" action="{{ route('notifications.read', $hhNotification) }}" data-hh-header-ajax-form data-remove-on-success="[data-hh-mobile-notification-item='{{ $hhNotification->id }}'],[data-hh-notification-item='{{ $hhNotification->id }}']" data-empty-list="mobile-notifications" data-empty-message="{{ __('ui.no_notifications') }}" data-redirect-on-success data-hh-mobile-notification-item="{{ $hhNotification->id }}">
                        @csrf
                        <button type="submit">
                            <span class="hh-mobile-header-sheet-avatar">
                                <img src="{{ $hhActorAvatar }}" alt="">
                                <i class="ph ph-{{ $hhNotificationPhosphorIcon }}" aria-hidden="true"></i>
                            </span>
                            <span class="hh-mobile-header-sheet-body">
                                <span class="hh-mobile-header-sheet-title"><strong>{{ $hhActorName }}</strong> · {{ $hhNotificationTitle }}</span>
                                @if (filled($hhNotificationBody))
                                    <span>{{ \Illuminate\Support\Str::limit($hhNotificationBody, 96) }}</span>
                                @endif
                                <small>{{ $hhNotification->created_at?->diffForHumans() }}</small>
                            </span>
                            @if ($hhNotification->isUnread())
                                <em aria-hidden="true"></em>
                            @endif
                        </button>
                    </form>
                @empty
                    <div class="hh-mobile-header-sheet-empty">{{ __('ui.no_notifications') }}</div>
                @endforelse
            </div>
        </div>

        <div class="hh-mobile-header-sheet-backdrop" data-hh-header-dropdown-backdrop aria-hidden="true"></div>

        <div class="header-actions hh-header-notification-actions">
            <div class="action-list dark hh-header-dropdown-actions">
                <div class="action-list-item-wrap">
                    <button class="action-list-item header-dropdown-trigger hh-header-dropdown-trigger" type="button" data-hh-header-dropdown-trigger="friend-requests" aria-label="{{ __('ui.friend_requests') }}" aria-haspopup="true" aria-expanded="false">
                        <i class="action-list-item-icon hh-ph-header-icon ph ph-user-plus" aria-hidden="true"></i>
                        @if ($hhPendingFriendRequestCount > 0)
                            <span class="hh-action-badge" data-hh-friend-request-count>{{ $hhPendingFriendRequestCount }}</span>
                        @endif
                    </button>

                    <div class="dropdown-box header-dropdown hh-header-dropdown-box hh-header-dropdown-friends" data-hh-header-dropdown="friend-requests">
                        <div class="dropdown-box-header">
                            <p class="dropdown-box-header-title">{{ __('ui.friend_requests') }}</p>
                            <div class="dropdown-box-header-actions">
                                <a class="hh-header-link-button" href="{{ route('members.index') }}">{{ __('ui.find_friends') }}</a>
                                <a class="hh-header-link-button" href="{{ route('profile.edit') }}">{{ __('ui.settings') }}</a>
                            </div>
                        </div>

                        <div class="dropdown-box-list hh-header-dropdown-list no-hover" data-hh-dropdown-list="friend-requests">
                            @forelse ($hhHeaderFriendRequests as $hhFriendRequest)
                                @php
                                    $hhRequester = $hhFriendRequest->requester;
                                    $hhRequesterName = $hhRequester?->name ?: ($hhRequester?->username ?: 'hnt.rocks');
                                    $hhRequesterAvatar = $hhRequester?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                                    $hhRequesterLevel = max(1, (int) ($hhRequester?->level ?: 1));
                                    $hhRequesterUrl = $hhRequester ? route('profile.public', $hhRequester) : route('members.index');
                                @endphp
                                <div class="dropdown-box-list-item" data-hh-dropdown-item data-hh-friend-request-item="{{ $hhFriendRequest->id }}">
                                    <div class="user-status request hh-header-request-status">
                                        <a class="user-status-avatar" href="{{ $hhRequesterUrl }}" aria-label="{{ $hhRequesterName }}">
                                            <span class="user-avatar small no-outline">
                                                <span class="user-avatar-content">
                                                    <img class="hh-dropdown-avatar-img" src="{{ $hhRequesterAvatar }}" alt="">
                                                </span>
                                                <span class="user-avatar-badge"><span class="user-avatar-badge-text">{{ $hhRequesterLevel }}</span></span>
                                            </span>
                                        </a>
                                        <span class="user-status-title"><a class="bold" href="{{ $hhRequesterUrl }}">{{ $hhRequesterName }}</a></span>
                                        <span class="user-status-text">{{ __('ui.friend_request_wants_connect') }}</span>

                                        <div class="action-request-list">
                                            <form class="hh-header-ajax-form" method="POST" action="{{ route('friends.accept', $hhFriendRequest) }}" data-hh-header-ajax-form data-remove-on-success="[data-hh-friend-request-item='{{ $hhFriendRequest->id }}'],[data-hh-mobile-friend-request-item='{{ $hhFriendRequest->id }}']" data-empty-list="friend-requests" data-empty-message="{{ __('ui.no_friend_requests') }}" data-more-message="{{ __('ui.more_friend_requests') }}">
                                                @csrf
                                                <button class="action-request accept" type="submit" aria-label="{{ __('ui.accept_friend_request') }}" title="{{ __('ui.accept_friend_request') }}">
                                                    <i class="action-request-icon hh-ph-header-icon ph ph-check" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                            <form class="hh-header-ajax-form" method="POST" action="{{ route('friends.decline', $hhFriendRequest) }}" data-hh-header-ajax-form data-remove-on-success="[data-hh-friend-request-item='{{ $hhFriendRequest->id }}'],[data-hh-mobile-friend-request-item='{{ $hhFriendRequest->id }}']" data-empty-list="friend-requests" data-empty-message="{{ __('ui.no_friend_requests') }}" data-more-message="{{ __('ui.more_friend_requests') }}">
                                                @csrf
                                                <button class="action-request decline" type="submit" aria-label="{{ __('ui.decline_friend_request') }}" title="{{ __('ui.decline_friend_request') }}">
                                                    <i class="action-request-icon hh-ph-header-icon ph ph-x" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="dropdown-box-list-item hh-header-dropdown-empty">{{ __('ui.no_friend_requests') }}</div>
                            @endforelse
                        </div>

                        <a class="dropdown-box-button secondary" href="{{ route('members.index', ['relationship' => 'pending']) }}">{{ __('ui.view_all_requests') }}</a>
                    </div>
                </div>

                <a class="action-list-item" href="{{ route('messages.index') }}" data-hh-chat-dock-open="list" aria-label="{{ __('ui.messages') }}">
                    <i class="action-list-item-icon hh-ph-header-icon ph ph-chats-circle" aria-hidden="true"></i>
                    @if ($hhUnreadMessages > 0)
                        <span class="hh-action-badge" data-hh-message-count>{{ $hhUnreadMessages }}</span>
                    @endif
                </a>

                <div class="action-list-item-wrap">
                    <button class="action-list-item header-dropdown-trigger hh-header-dropdown-trigger" type="button" data-hh-header-dropdown-trigger="lfg-messages" aria-label="{{ __('ui.lfg_chats') }}" aria-haspopup="true" aria-expanded="false">
                        <i class="action-list-item-icon hh-ph-header-icon ph ph-crosshair" aria-hidden="true"></i>
                        @if ($hhUnreadLfgMessages > 0)
                            <span class="hh-action-badge" data-hh-lfg-message-count>{{ $hhUnreadLfgMessages }}</span>
                        @endif
                    </button>

                    <div class="dropdown-box header-dropdown hh-header-dropdown-box hh-header-dropdown-lfg-messages" data-hh-header-dropdown="lfg-messages">
                        <div class="dropdown-box-header">
                            <p class="dropdown-box-header-title">{{ __('ui.lfg_chats') }}</p>
                            <div class="dropdown-box-header-actions">
                                <a class="hh-header-link-button" href="{{ route('messages.index', ['type' => 'lfg']) }}">{{ __('ui.view_all_lfg_chats') }}</a>
                            </div>
                        </div>

                        <div class="dropdown-box-list hh-header-dropdown-list" data-hh-dropdown-list="lfg-messages">
                            @forelse ($hhHeaderLfgConversations as $hhLfgConversation)
                                @php
                                    $hhLfgOtherUser = $hhLfgConversation->otherParticipant($hhUser);
                                    $hhLfgOtherName = $hhLfgOtherUser?->name ?: ($hhLfgOtherUser?->username ?: 'hnt.rocks');
                                    $hhLfgOtherAvatar = $hhLfgOtherUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                                    $hhLfgOtherLevel = max(1, (int) ($hhLfgOtherUser?->level ?: 1));
                                    $hhLfgUnread = $hhLfgConversation->unreadCountFor($hhUser);
                                    $hhLfgLatestMessage = $hhLfgConversation->latestMessage;
                                    $hhLfgLatestText = $hhLfgLatestMessage?->body
                                        ? \Illuminate\Support\Str::limit($hhLfgLatestMessage->body, 72)
                                        : __('ui.message_no_messages_yet');
                                    $hhLfgTitle = \Illuminate\Support\Str::limit($hhLfgConversation->displayTitleFor($hhUser), 48);
                                @endphp
                                <a class="dropdown-box-list-item {{ $hhLfgUnread > 0 ? 'unread' : '' }}" href="{{ route('messages.show', $hhLfgConversation) }}">
                                    <div class="user-status hh-header-lfg-status">
                                        <span class="user-status-avatar" aria-hidden="true">
                                            <span class="user-avatar small no-outline">
                                                <span class="user-avatar-content">
                                                    <img class="hh-dropdown-avatar-img" src="{{ $hhLfgOtherAvatar }}" alt="">
                                                </span>
                                                <span class="user-avatar-badge"><span class="user-avatar-badge-text">{{ $hhLfgOtherLevel }}</span></span>
                                            </span>
                                        </span>
                                        <span class="user-status-title"><span class="bold">{{ $hhLfgTitle }}</span> <span class="hh-header-lfg-tag">{{ $hhLfgConversation->contextBadgeLabel() ?: __('ui.message_lfg_badge') }}</span></span>
                                        <span class="user-status-text">{{ $hhLfgLatestText }}</span>
                                        <span class="user-status-timestamp">{{ $hhLfgLatestMessage?->created_at?->diffForHumans() ?: $hhLfgConversation->updated_at?->diffForHumans() }}</span>
                                        @if ($hhLfgUnread > 0)
                                            <span class="hh-header-dropdown-pill">{{ $hhLfgUnread }}</span>
                                        @else
                                            <span class="user-status-icon"><i class="hh-ph-header-dropdown-icon ph ph-crosshair" aria-hidden="true"></i></span>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="dropdown-box-list-item hh-header-dropdown-empty">{{ __('ui.no_lfg_chats') }}</div>
                            @endforelse
                        </div>

                        <a class="dropdown-box-button secondary" href="{{ route('messages.index', ['type' => 'lfg']) }}">{{ __('ui.view_all_lfg_chats') }}</a>
                    </div>
                </div>

                <div class="action-list-item-wrap">
                    <button class="action-list-item header-dropdown-trigger hh-header-dropdown-trigger" type="button" data-hh-header-dropdown-trigger="notifications" aria-label="{{ __('ui.notifications') }}" aria-haspopup="true" aria-expanded="false">
                        <i class="action-list-item-icon hh-ph-header-icon ph ph-bell" aria-hidden="true"></i>
                        @if ($hhUnreadNotifications > 0)
                            <span class="hh-action-badge" data-hh-notification-count>{{ $hhUnreadNotifications }}</span>
                        @endif
                    </button>

                    <div class="dropdown-box header-dropdown hh-header-dropdown-box hh-header-dropdown-notifications" data-hh-header-dropdown="notifications">
                        <div class="dropdown-box-header">
                            <p class="dropdown-box-header-title">{{ __('ui.notifications') }}</p>
                            <div class="dropdown-box-header-actions">
                                @if ($hhUnreadNotifications > 0)
                                    <form class="hh-header-ajax-form" method="POST" action="{{ route('notifications.read-all') }}" data-hh-header-ajax-form data-hh-mark-all-notifications data-clear-dropdown="notifications" data-empty-message="{{ __('ui.no_new_notifications') }}" data-remove-on-success="[data-hh-mark-all-notifications]">
                                        @csrf
                                        <button class="dropdown-box-header-action" type="submit">{{ __('ui.mark_all_read') }}</button>
                                    </form>
                                @endif
                                <a class="hh-header-link-button" href="{{ route('notifications.index') }}">{{ __('ui.settings') }}</a>
                            </div>
                        </div>

                        <div class="dropdown-box-list hh-header-dropdown-list" data-hh-dropdown-list="notifications">
                            @forelse ($hhHeaderNotifications as $hhNotification)
                                @php
                                    $hhActor = $hhNotification->actor;
                                    $hhNotificationTitle = $hhNotification->displayTitle();
                                    $hhNotificationBody = $hhNotification->displayBody();
                                    $hhActorName = $hhActor?->name ?: 'hnt.rocks';
                                    $hhActorAvatar = $hhActor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                                    $hhNotificationType = (string) $hhNotification->type;
                                    $hhNotificationIcon = match (true) {
                                        str_contains($hhNotificationType, 'comment') => 'comment',
                                        str_contains($hhNotificationType, 'like'), str_contains($hhNotificationType, 'reaction') => 'thumbs-up',
                                        str_contains($hhNotificationType, 'message') => 'messages',
                                        str_contains($hhNotificationType, 'friend') => 'friend',
                                        default => 'notification',
                                    };
                                    $hhNotificationPhosphorIcon = match ($hhNotificationIcon) {
                                        'comment' => 'chat-circle-text',
                                        'thumbs-up' => 'thumbs-up',
                                        'messages' => 'chats-circle',
                                        'friend' => 'user-plus',
                                        default => 'bell',
                                    };
                                @endphp
                                <div class="dropdown-box-list-item {{ $hhNotification->isUnread() ? 'unread' : '' }}" data-hh-notification-item="{{ $hhNotification->id }}">
                                    <form class="hh-header-notification-form {{ $hhNotification->isUnread() ? 'is-unread' : '' }}" method="POST" action="{{ route('notifications.read', $hhNotification) }}" data-hh-header-ajax-form data-remove-on-success="[data-hh-notification-item='{{ $hhNotification->id }}'],[data-hh-mobile-notification-item='{{ $hhNotification->id }}']" data-redirect-on-success>
                                        @csrf
                                        <button class="hh-header-notification-status user-status notification" type="submit">
                                            <span class="user-status-avatar">
                                                <span class="user-avatar small no-outline">
                                                    <span class="user-avatar-content">
                                                        <img class="hh-dropdown-avatar-img" src="{{ $hhActorAvatar }}" alt="">
                                                    </span>
                                                    <span class="user-avatar-badge"><span class="user-avatar-badge-text">{{ max(1, (int) ($hhActor?->level ?: 1)) }}</span></span>
                                                </span>
                                            </span>
                                            <span class="user-status-title"><span class="bold">{{ $hhActorName }}</span> · {{ $hhNotificationTitle }}</span>
                                            @if (filled($hhNotificationBody))
                                                <span class="user-status-text">{{ \Illuminate\Support\Str::limit($hhNotificationBody, 92) }}</span>
                                            @endif
                                            <span class="user-status-timestamp">{{ $hhNotification->created_at?->diffForHumans() }}</span>
                                            <span class="user-status-icon"><i class="hh-ph-header-dropdown-icon ph ph-{{ $hhNotificationPhosphorIcon }}" aria-hidden="true"></i></span>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <div class="dropdown-box-list-item hh-header-dropdown-empty">{{ __('ui.no_notifications') }}</div>
                            @endforelse
                        </div>

                        <a class="dropdown-box-button secondary" href="{{ route('notifications.index') }}">{{ __('ui.view_all_notifications') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-actions hh-header-user-actions hh-header-settings-actions">
            <div class="action-item-wrap hh-header-settings-wrap">
                <button class="action-item dark hh-header-settings-trigger hh-header-dropdown-trigger" type="button" data-hh-header-dropdown-trigger="user-settings" aria-label="{{ __('ui.settings') }}" aria-haspopup="true" aria-expanded="false">
                    <i class="action-item-icon hh-ph-header-icon ph ph-gear-six" aria-hidden="true"></i>
                </button>

                <div class="dropdown-navigation header-settings-dropdown hh-header-settings-dropdown" data-hh-header-dropdown="user-settings">
                    <div class="dropdown-navigation-header">
                        <div class="user-status">
                            <a class="user-status-avatar" href="{{ route('profile.show') }}" aria-label="{{ $hhUser->username }}">
                                <span class="user-avatar small no-outline">
                                    <span class="user-avatar-content">
                                        <span class="hexagon-image-30-32" data-src="{{ $hhUser->avatarUrl() }}" style="background-image:url('{{ $hhUser->avatarUrl() }}');"></span>
                                    </span>
                                    <span class="user-avatar-progress"><span class="hexagon-progress-40-44"></span></span>
                                    <span class="user-avatar-progress-border"><span class="hexagon-border-40-44"></span></span>
                                    <span class="user-avatar-badge">
                                        <span class="user-avatar-badge-border"><span class="hexagon-22-24"></span></span>
                                        <span class="user-avatar-badge-content"><span class="hexagon-dark-16-18"></span></span>
                                        <span class="user-avatar-badge-text">{{ $hhLevel }}</span>
                                    </span>
                                </span>
                            </a>
                            <p class="user-status-title"><span class="bold">{{ $hhUser->username }}</span></p>
                            <p class="user-status-text small"><a href="{{ route('profile.show') }}">{{ '@' . $hhUser->username }}</a></p>
                        </div>
                    </div>

                    <p class="dropdown-navigation-category">{{ __('ui.my_profile') }}</p>
                    <a class="dropdown-navigation-link" href="{{ route('profile.edit') }}">{{ __('ui.edit_profile') }}</a>
                    <a class="dropdown-navigation-link" href="{{ route('notifications.index') }}">{{ __('ui.notifications') }}</a>
                    <a class="dropdown-navigation-link" href="{{ route('messages.index') }}">{{ __('ui.messages') }}</a>
                    <a class="dropdown-navigation-link" href="{{ route('messages.index', ['type' => 'lfg']) }}">{{ __('ui.lfg_chats') }}</a>
                    <a class="dropdown-navigation-link" href="{{ route('members.index', ['relationship' => 'pending']) }}">{{ __('ui.friend_requests') }}</a>

                    <p class="dropdown-navigation-category">{{ __('ui.account') }}</p>
                    <a class="dropdown-navigation-link" href="{{ route('settings.privacy.edit') }}">{{ __('ui.privacy') }}</a>
                    <a class="dropdown-navigation-link" href="{{ route('settings.security.index') }}">{{ __('ui.security') }}</a>

                    <p class="dropdown-navigation-category">{{ __('ui.teams') }}</p>
                    <a class="dropdown-navigation-link" href="{{ route('members.index') }}">{{ __('ui.members') }}</a>
                    <a class="dropdown-navigation-link" href="{{ route('teams.index') }}">{{ __('ui.teams') }}</a>

                    <p class="dropdown-navigation-category">{{ __('ui.language') }}</p>
                    <div class="hh-settings-language-row" aria-label="{{ __('ui.language') }}">
                        <a class="{{ $hhCurrentLocale === 'de' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'de') }}">DE</a>
                        <a class="{{ $hhCurrentLocale === 'en' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'en') }}">EN</a>
                    </div>

                    <form class="hh-settings-logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-navigation-button button small secondary" type="submit">{{ __('ui.logout') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        @php($hhCurrentLocale = app()->getLocale())
        <div class="header-actions hh-guest-actions">
            <nav class="navigation" aria-label="{{ __('ui.account_navigation') }}">
                <ul class="menu-main">
                    <li class="menu-main-item"><a class="menu-main-item-link {{ $hhCurrentLocale === 'de' ? 'active' : '' }}" href="{{ route('locale.switch', 'de') }}">DE</a></li>
                    <li class="menu-main-item"><a class="menu-main-item-link {{ $hhCurrentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">EN</a></li>
                    <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('login') }}">{{ __('ui.login') }}</a></li>
                    <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('register') }}">{{ __('ui.register') }}</a></li>
                </ul>
            </nav>
        </div>
    @endauth
</header>
