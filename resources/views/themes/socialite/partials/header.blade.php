@php
    $hhSocialiteUser = auth()->user();
    $hhSocialiteAvatar = $hhSocialiteUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $hhSocialiteName = $hhSocialiteUser?->name ?: 'HNT.rocks';
    $hhSocialiteHandle = $hhSocialiteUser?->username ? '@' . $hhSocialiteUser->username : '@hunters';
    $hhSocialiteProfileUrl = $hhSocialiteUser ? route('profile.show') : url('/login');
    $hhSocialiteUnreadNotifications = 0;
    $hhSocialiteUnreadMessages = 0;
    $hhSocialiteHeaderNotifications = collect();
    $hhSocialiteHeaderConversations = collect();

    if ($hhSocialiteUser) {
        $hhSocialiteUnreadNotifications = $hhSocialiteUser->notificationItems()->standard()->unread()->count();
        $hhSocialiteUnreadMessages = $hhSocialiteUser->unreadMessagesCount();
        $hhSocialiteHeaderNotifications = $hhSocialiteUser->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->latest()
            ->limit(8)
            ->get();
        $hhSocialiteHeaderConversations = \App\Models\Conversation::query()
            ->forUser($hhSocialiteUser)
            ->where('type', 'private')
            ->with(['users.profile', 'latestMessage.user.profile'])
            ->latest('updated_at')
            ->limit(8)
            ->get();
    }
@endphp
        <!-- header -->
        <header class="z-[100] h-[--m-top] fixed top-0 left-0 w-full flex items-center bg-white/80 sky-50 backdrop-blur-xl border-b border-slate-200 dark:bg-dark2 dark:border-slate-800">

            <div class="flex items-center w-full xl:px-6 px-2 max-lg:gap-10">

                <div class="2xl:w-[--w-side] lg:w-[--w-side-sm]">

                    <!-- left -->
                    <div class="flex items-center gap-1"> 

                        <!-- icon menu -->
                        <button uk-toggle="target: #site__sidebar ; cls :!-translate-x-0"
                                class="flex items-center justify-center w-8 h-8 text-xl rounded-full hover:bg-gray-100 xl:hidden dark:hover:bg-slate-600 group"> 
                                <ion-icon name="menu-outline" class="text-2xl group-aria-expanded:hidden"></ion-icon>
                                <ion-icon name="close-outline" class="hidden text-2xl group-aria-expanded:block"></ion-icon>
                        </button>
                        <div id="logo">
                            <a href="{{ route('feed.index') }}"> 
                                <img src="/assets/socialite/images/logo.png" alt="" class="w-28 md:block hidden dark:!hidden">
                                <img src="/assets/socialite/images/logo-light.png" alt="" class="dark:md:block hidden">
                                <img src="/assets/socialite/images/logo-mobile.png" class="hidden max-md:block w-20 dark:!hidden" alt="">
                                <img src="/assets/socialite/images/logo-mobile-light.png" class="hidden dark:max-md:block w-20" alt="">
                            </a>
                        </div>
                         
                    </div>

                </div>
                <div class="flex-1 relative">

                    <div class="max-w-[1220px] mx-auto flex items-center">
                        
                        <!-- search -->
                        <div id="search--box" class="xl:w-[680px] sm:w-96 sm:relative rounded-xl overflow-hidden z-20 bg-secondery max-md:hidden w-screen left-0 max-sm:fixed max-sm:top-2 dark:!bg-white/5">
                            <ion-icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2"></ion-icon>
                            <input
                                type="search"
                                id="hh-header-search-input"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="{{ __('ui.header_search_placeholder') }}"
                                data-search-url="{{ route('socialite.header.search') }}"
                                class="w-full !pl-10 !font-normal !bg-transparent h-12 !text-sm"
                            >
                        </div>
                        <!-- search dropdown-->
                        <div id="hh-header-search-dropdown" class="hidden z-10"
                                uk-drop="pos: bottom-center ; animation: uk-animation-slide-bottom-small; mode: click">

                                <div class="xl:w-[694px] sm:w-96 bg-white dark:bg-dark3 w-screen p-2 rounded-lg shadow-lg -mt-14 pt-14">
                                    <div class="flex justify-between px-2 py-2.5 text-sm font-medium">
                                        <div class="text-black dark:text-white">{{ __('ui.search') }}</div>
                                        <button type="button" id="hh-header-search-clear" class="text-amber-500">{{ __('ui.clear') }}</button>
                                    </div>
                                    <nav id="hh-header-search-results" class="text-sm font-medium text-black dark:text-white">
                                        <div class="px-3 py-3 text-sm text-gray-500 dark:text-gray-300">{{ __('ui.header_search_hint') }}</div>
                                    </nav>
                                </div>

                        </div>

                        <!-- header icons -->
                        <div class="flex items-center sm:gap-4 gap-2 absolute right-5 top-1/2 -translate-y-1/2 text-black">
                            <!-- create -->
                            <button type="button" class="sm:p-2 p-1 rounded-full relative sm:bg-secondery dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 max-sm:hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                  </svg>
                                <ion-icon name="add-circle-outline" class="sm:hidden text-2xl "></ion-icon>
                            </button>
                            <div    class="hidden bg-white p-4 rounded-lg overflow-hidden drop-shadow-xl dark:bg-slate-700 md:w-[324px] w-screen border2"
                                    uk-drop="offset:6;pos: bottom-right; mode: click; animate-out: true; animation: uk-animation-scale-up uk-transform-origin-top-right ">
                                
                                    <h3 class="font-bold text-md"> {{ __('ui.create') }}  </h3>

                                    <!-- slider -->
                                    <div class="mt-4" tabindex="-1" uk-slider="finite:true;sets: true">

                                        <div class="uk-slider-container pb-1">
                                        
                                            <ul class="uk-slider-items grid-small" uk-scrollspy="target: > li; cls: uk-animation-scale-up , uk-animation-slide-right-small; delay: 20 ;repeat: true">
                                                <li class="w-28" uk-scrollspy-class="uk-animation-fade">
                                                    <div class="p-3 px-4 rounded-lg bg-teal-100/60 text-teal-600 dark:text-white dark:bg-dark4">
                                                        <ion-icon name="book" class="text-2xl drop-shadow-md"></ion-icon>
                                                        <div class="mt-1.5 text-sm font-medium"> {{ __('ui.header_create_post_short') }} </div>
                                                    </div>
                                                </li>   
                                                <li class="w-28">
                                                    <div class="p-3 px-4 rounded-lg bg-sky-100/60 text-sky-600 dark:text-white dark:bg-dark4">
                                                        <ion-icon name="camera" class="text-2xl drop-shadow-md"></ion-icon>
                                                        <div class="mt-1.5 text-sm font-medium"> {{ __('ui.media') }} </div>
                                                    </div>
                                                </li> 
                                                <li class="w-28">
                                                    <div class="p-3 px-4 rounded-lg bg-purple-100/60 text-purple-600 dark:text-white dark:bg-dark4">
                                                        <ion-icon name="videocam" class="text-2xl drop-shadow-md"></ion-icon>
                                                        <div class="mt-1.5 text-sm font-medium"> {{ __('ui.moments') }} </div>
                                                    </div>
                                                </li> 
                                                <li class="w-28">
                                                    <div class="p-3 px-4 rounded-lg bg-pink-100/60 text-pink-600 dark:text-white dark:bg-dark4">
                                                        <ion-icon name="location" class="text-2xl drop-shadow-md"></ion-icon>
                                                        <div class="mt-1.5 text-sm font-medium"> LFG </div>
                                                    </div>
                                                </li> 
                                                <li class="w-28">
                                                    <div class="p-3 px-4 rounded-lg bg-sky-100/70 text-sky-600 dark:text-white dark:bg-dark4">
                                                        <ion-icon name="happy" class="text-2xl  drop-shadow-md"></ion-icon>
                                                        <div class="mt-1.5 text-sm font-medium"> Cup </div>
                                                    </div> 
                                                </li> 
                                            </ul>
                                    
                                        </div>
                                    
                                        <!-- slide nav icons -->
                                        <div class="dark:hidden">
                                            <a class="absolute -translate-y-1/2 top-1/2 -left-4 flex items-center w-8 h-full px-1.5 justify-start bg-gradient-to-r from-white via-white dark:from-slate-600 dark:via-slate-500 dark:from-transparent dark:via-transparent" href="#" uk-slider-item="previous"> <ion-icon name="chevron-back" class="text-xl dark:text-white"></ion-icon> </a>
                                            <a class="absolute -translate-y-1/2 top-1/2 -right-4 flex items-center w-8 h-full px-1.5 justify-end bg-gradient-to-l from-white via-white dark:from-transparent dark:via-transparent" href="#" uk-slider-item="next">  <ion-icon name="chevron-forward" class="text-xl dark:text-white"></ion-icon> </a>
                                        </div>
                

                                        <!-- slide nav -->
                                        <div class="justify-center mt-2 -mb-2 hidden dark:flex">
                                            <ul class="inline-flex flex-wrap justify-center gap-1 uk-dotnav uk-slider-nav"> </ul>
                                        </div>

                                    </div>
    
                                    <!-- list -->
                                    <ul class="-m-1 mt-4 pb-1 text-xs text-gray-500 dark:text-white" uk-scrollspy="target: > li; cls: uk-animation-scale-up , uk-animation-slide-bottom-small ;repeat: true">
                                        <li class="flex items-center gap-4 hover:bg-secondery rounded-md p-1.5 cursor-pointer dark:hover:bg-white/10">
                                            <img src="/assets/socialite/images/icons/group.png" alt="" class="w-7">
                                            <div class="flex-1">
                                                <a href="{{ route('teams.create') }}"><h4 class="font-medium text-sm text-black dark:text-white"> {{ __('ui.header_create_team') }} </h4></a>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-white"> {{ __('ui.header_create_team_text') }} </div>
                                            </div>
                                        </li>
                                        <li class="flex items-center gap-4 hover:bg-secondery rounded-md p-1.5 cursor-pointer dark:hover:bg-white/10">
                                            <img src="/assets/socialite/images/icons/page.png" alt="" class="w-7">
                                            <div class="flex-1">
                                                <a href="{{ route('feed.index') }}"><h4 class="font-medium text-sm text-black dark:text-white"> {{ __('ui.header_create_post') }} </h4></a>
                                                <div class="mt-1"> {{ __('ui.header_create_post_text') }}
                                            </div>
                                        </li>
                                        <li class="flex items-center gap-4 hover:bg-secondery rounded-md p-1.5 cursor-pointer dark:hover:bg-white/10">
                                            <img src="/assets/socialite/images/icons/event.png" class="w-7">
                                            <div class="flex-1">
                                                <a href="{{ route('cups.index') }}"><h4 class="font-medium text-sm text-black dark:text-white"> {{ __('ui.header_open_cups') }} </h4></a>
                                                <div class="mt-1">{{ __('ui.header_open_cups_text') }}</div>
                                            </div>
                                        </li>
                                        <li class="flex items-center gap-4 hover:bg-secondery rounded-md p-1.5 cursor-pointer dark:hover:bg-white/10">
                                            <img src="/assets/socialite/images/icons/market.png" class="w-8 -ml-1">
                                            <div class="flex-1">
                                                <a href="{{ route('lfg.create') }}"><h4 class="font-medium text-sm text-black dark:text-white"> {{ __('ui.header_create_lfg') }} </h4></a>
                                                <div class="mt-1">{{ __('ui.header_create_lfg_text') }}</div>
                                            </div>
                                        </li>
                                        <li class="flex items-center gap-4 hover:bg-secondery rounded-md p-1.5 cursor-pointer dark:hover:bg-white/10">
                                            <img src="/assets/socialite/images/icons/game.png" alt="" class="w-7">
                                            <div class="flex-1">
                                                <a href="{{ route('moments.create') }}"><h4 class="font-medium text-sm text-black dark:text-white"> {{ __('ui.header_create_moment') }} </h4></a>
                                                <div class="mt-1">{{ __('ui.header_create_moment_text') }}</div>
                                            </div>
                                        </li>
                                    </ul>


                            </div>
    
                            <!-- notification -->
                            <button type="button" class="sm:p-2 p-1 rounded-full relative sm:bg-secondery dark:text-white" uk-tooltip="title: {{ __('ui.notifications') }}; pos: bottom; offset:6" aria-label="{{ __('ui.notifications') }}" data-hh-live-badge-host="notifications">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 max-sm:hidden">
                                    <path d="M5.85 3.5a.75.75 0 00-1.117-1 9.719 9.719 0 00-2.348 4.876.75.75 0 001.479.248A8.219 8.219 0 015.85 3.5zM19.267 2.5a.75.75 0 10-1.118 1 8.22 8.22 0 011.987 4.124.75.75 0 001.48-.248A9.72 9.72 0 0019.266 2.5z" />
                                    <path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 005.25 9v.75a8.217 8.217 0 01-2.119 5.52.75.75 0 00.298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 107.48 0 24.583 24.583 0 004.83-1.244.75.75 0 00.298-1.205 8.217 8.217 0 01-2.118-5.52V9A6.75 6.75 0 0012 2.25zM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 004.496 0l.002.1a2.25 2.25 0 11-4.5 0z" clip-rule="evenodd" />
                                </svg>
                                <div class="absolute top-0 right-0 -m-1 bg-red-600 text-white text-xs px-1 rounded-full {{ $hhSocialiteUnreadNotifications > 0 ? '' : 'hidden' }}" data-hh-notification-count data-hh-live-badge="notifications" aria-live="polite">{{ $hhSocialiteUnreadNotifications > 99 ? '99+' : $hhSocialiteUnreadNotifications }}</div>
                                <ion-icon name="notifications-outline" class="sm:hidden text-2xl"></ion-icon>
                            </button>
                            <div class="hidden bg-white pr-1.5 rounded-lg drop-shadow-xl dark:bg-slate-700 md:w-[365px] w-screen border2"
                                uk-drop="offset:6;pos: bottom-right; mode: click; animate-out: true; animation: uk-animation-scale-up uk-transform-origin-top-right ">

                                <!-- heading -->
                                <div class="flex items-center justify-between gap-2 p-4 pb-2">
                                    <h3 class="font-bold text-xl">{{ __('ui.notifications') }}</h3>

                                    <div class="flex gap-2.5">
                                        <button type="button" class="p-1 flex rounded-full focus:bg-secondery dark:text-white" aria-label="{{ __('ui.notification_settings') }}"> <ion-icon class="text-xl" name="ellipsis-horizontal"></ion-icon> </button>
                                        <div class="w-[280px] group" uk-dropdown="pos: bottom-right; animation: uk-animation-scale-up uk-transform-origin-top-right; animate-out: true; mode: click; offset:5">
                                            <nav class="text-sm">
                                                @if ($hhSocialiteUnreadNotifications > 0)
                                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                                        @csrf
                                                        <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-md hover:bg-secondery dark:hover:bg-white/10">
                                                            <ion-icon class="text-xl shrink-0" name="checkmark-circle-outline"></ion-icon> {{ __('ui.mark_all_read') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('account.settings.edit') }}"> <ion-icon class="text-xl shrink-0" name="settings-outline"></ion-icon> {{ __('ui.notification_settings') }}</a>
                                            </nav>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-sm h-[400px] w-full overflow-y-auto pr-2">

                                    <!-- contents list -->
                                    <div class="pl-2 p-1 text-sm font-normal dark:text-white" data-hh-dropdown-list="notifications">
                                        @forelse ($hhSocialiteHeaderNotifications as $hhNotification)
                                            @php
                                                $hhNotificationActor = $hhNotification->actor;
                                                $hhNotificationActorName = method_exists($hhNotification, 'displayActorName')
                                                    ? $hhNotification->displayActorName()
                                                    : ($hhNotificationActor?->name ?: $hhNotificationActor?->username ?: 'hnt.rocks');
                                                $hhNotificationActorAvatar = method_exists($hhNotification, 'displayActorAvatarUrl')
                                                    ? $hhNotification->displayActorAvatarUrl()
                                                    : ($hhNotificationActor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'));
                                                $hhNotificationTitle = $hhNotification->displayTitle();
                                                $hhNotificationBody = $hhNotification->displayBody();
                                                $hhNotificationIsUnread = $hhNotification->isUnread();
                                            @endphp
                                            <form method="POST" action="{{ route('notifications.read', $hhNotification) }}" data-hh-notification-item="{{ $hhNotification->id }}" data-hh-dropdown-item>
                                                @csrf
                                                <button type="submit" class="hh-notification-row relative flex w-full items-center gap-3 p-2 duration-200 rounded-xl pr-10 text-left {{ $hhNotificationIsUnread ? 'is-unread' : '' }}">
                                                    <div class="relative w-12 h-12 shrink-0">
                                                        <img src="{{ $hhNotificationActorAvatar }}" alt="{{ $hhNotificationActorName }}" class="object-cover w-full h-full rounded-full">
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-black dark:text-white">
                                                            <b class="font-bold mr-1">{{ $hhNotificationActorName }}</b>{{ $hhNotificationTitle }}
                                                        </p>
                                                        @if (filled($hhNotificationBody))
                                                            <div class="mt-1 text-xs text-gray-600 dark:text-white/70 line-clamp-2">{{ \Illuminate\Support\Str::limit($hhNotificationBody, 96) }}</div>
                                                        @endif
                                                        <div class="text-xs text-gray-500 mt-1.5 dark:text-white/80">{{ $hhNotification->created_at?->diffForHumans() }}</div>
                                                        @if ($hhNotificationIsUnread)
                                                            <div class="hh-notification-dot w-2.5 h-2.5 rounded-full absolute right-3 top-5"></div>
                                                        @endif
                                                    </div>
                                                </button>
                                            </form>
                                        @empty
                                            <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-white/70">
                                                {{ __('ui.no_notifications') }}
                                            </div>
                                        @endforelse
                                    </div>

                                </div>

                                <!-- footer -->
                                <a href="{{ route('notifications.index') }}">
                                    <div class="text-center py-4 border-t border-slate-100 text-sm font-medium text-blue-600 dark:text-white dark:border-gray-600">{{ __('ui.view_all_notifications') }}</div>
                                </a>

                                <div class="w-3 h-3 absolute -top-1.5 right-3 bg-white border-l border-t rotate-45 max-md:hidden dark:bg-dark3 dark:border-transparent"></div>
                            </div>

                            <!-- messages -->
                            <button type="button" class="sm:p-2 p-1 rounded-full relative sm:bg-secondery dark:text-white" uk-tooltip="title: {{ __('ui.messages') }}; pos: bottom; offset:6" aria-label="{{ __('ui.messages') }}" data-hh-live-badge-host="messages">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 max-sm:hidden">
                                    <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0112 2.25c2.43 0 4.817.178 7.152.52 1.978.292 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.678-3.348 3.97a48.901 48.901 0 01-3.476.383.39.39 0 00-.297.17l-2.755 4.133a.75.75 0 01-1.248 0l-2.755-4.133a.39.39 0 00-.297-.17 48.9 48.9 0 01-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.68 3.348-3.97zM6.75 8.25a.75.75 0 01.75-.75h9a.75.75 0 010 1.5h-9a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H7.5z" clip-rule="evenodd"></path>
                                </svg>
                                <ion-icon name="chatbox-ellipses-outline" class="sm:hidden text-2xl"></ion-icon>
                                <div class="absolute top-0 right-0 -m-1 bg-red-600 text-white text-xs px-1 rounded-full {{ $hhSocialiteUnreadMessages > 0 ? '' : 'hidden' }}" data-hh-message-count data-hh-live-badge="messages" aria-live="polite">{{ $hhSocialiteUnreadMessages > 99 ? '99+' : $hhSocialiteUnreadMessages }}</div>
                            </button>
                            <div class="hidden bg-white pr-1.5 rounded-lg drop-shadow-xl dark:bg-slate-700 md:w-[360px] w-screen border2"
                                uk-drop="offset:6;pos: bottom-right; mode: click; animate-out: true; animation: uk-animation-scale-up uk-transform-origin-top-right ">

                                <!-- heading -->
                                <div class="flex items-center justify-between gap-2 p-4 pb-1">
                                    <h3 class="font-bold text-xl">{{ __('ui.messages_short') }}</h3>

                                    <div class="flex gap-2.5 text-lg text-slate-900 dark:text-white">
                                        <a href="{{ route('messages.index') }}" class="flex" aria-label="{{ __('ui.messages') }}">
                                            <ion-icon name="expand-outline"></ion-icon>
                                        </a>
                                        <a href="{{ route('messages.index') }}" class="flex" aria-label="{{ __('ui.message_start_new') }}">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </a>
                                    </div>
                                </div>

                                <div class="relative w-full p-2 px-3">
                                    <input type="text" class="w-full !pl-10 !rounded-lg dark:!bg-white/10" placeholder="{{ __('ui.search') }}">
                                    <ion-icon name="search-outline" class="dark:text-white absolute left-7 -translate-y-1/2 top-1/2"></ion-icon>
                                </div>

                                <div class="h-80 overflow-y-auto pr-2">
                                    <div class="p-2 pt-0 pr-1 dark:text-white/80">
                                        @forelse ($hhSocialiteHeaderConversations as $hhHeaderConversation)
                                            @php
                                                $hhHeaderConversation->loadMissing('users.profile');
                                                $hhHeaderPartner = $hhHeaderConversation->otherParticipant($hhSocialiteUser);
                                                $hhHeaderLatestMessage = $hhHeaderConversation->latestMessage;
                                                $hhHeaderConversationUnread = $hhHeaderConversation->unreadCountFor($hhSocialiteUser);
                                                $hhHeaderConversationName = $hhHeaderConversation->displayTitleFor($hhSocialiteUser);
                                                $hhHeaderConversationAvatar = $hhHeaderPartner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
                                                $hhHeaderMessagePreview = $hhHeaderLatestMessage?->body ?: __('ui.message_no_messages_yet');
                                                $hhHeaderMessageTime = $hhHeaderLatestMessage?->created_at
                                                    ? ($hhHeaderLatestMessage->created_at->isToday() ? $hhHeaderLatestMessage->created_at->format('H:i') : $hhHeaderLatestMessage->created_at->diffForHumans())
                                                    : '';
                                            @endphp
                                            <a href="{{ route('messages.show', $hhHeaderConversation) }}" class="relative flex items-center gap-4 p-2 py-3 duration-200 rounded-lg hover:bg-secondery dark:hover:bg-white/10" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ url('/messages/'.$hhHeaderConversation->id.'/chat-tab') }}" data-hnt-chat-conversation-id="{{ $hhHeaderConversation->id }}">
                                                <div class="relative w-10 h-10 shrink-0">
                                                    <img src="{{ $hhHeaderConversationAvatar }}" alt="{{ $hhHeaderConversationName }}" class="object-cover w-full h-full rounded-full">
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <div class="mr-auto text-sm text-black dark:text-white font-medium truncate">{{ $hhHeaderConversationName }}</div>
                                                        @if ($hhHeaderMessageTime !== '')
                                                            <div class="text-xs text-gray-500 dark:text-white/80 whitespace-nowrap">{{ $hhHeaderMessageTime }}</div>
                                                        @endif
                                                        @if ($hhHeaderConversationUnread > 0)
                                                            <div class="w-2.5 h-2.5 bg-blue-600 rounded-full dark:bg-slate-700 shrink-0"></div>
                                                        @endif
                                                    </div>
                                                    <div class="font-normal overflow-hidden text-ellipsis text-xs whitespace-nowrap">{{ \Illuminate\Support\Str::limit($hhHeaderMessagePreview, 90) }}</div>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="px-4 py-10 text-center text-sm text-gray-500 dark:text-white/70">
                                                {{ __('ui.message_no_conversations') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- footer -->
                                <a href="{{ route('messages.index') }}">
                                    <div class="text-center py-4 border-t border-slate-100 text-sm font-medium text-blue-600 dark:text-white dark:border-gray-600">{{ __('ui.messages') }}</div>
                                </a>

                                <div class="w-3 h-3 absolute -top-1.5 right-3 bg-white border-l border-t rotate-45 max-md:hidden dark:bg-dark3 dark:border-transparent"></div>
                            </div>
        
                            <!-- profile -->
                            <div  class="rounded-full relative bg-secondery cursor-pointer shrink-0">
                                <img src="{{ $hhSocialiteAvatar }}" alt="{{ $hhSocialiteName }}" class="sm:w-9 sm:h-9 w-7 h-7 rounded-full shadow shrink-0 object-cover"> 
                            </div>
                            <div  class="hidden bg-white rounded-lg drop-shadow-xl dark:bg-slate-700 w-64 border2"
                                uk-drop="offset:6;pos: bottom-right;animate-out: true; animation: uk-animation-scale-up uk-transform-origin-top-right ">
                                
                                <a href="{{ $hhSocialiteProfileUrl }}">
                                    <div class="p-4 py-5 flex items-center gap-4">
                                        <img src="{{ $hhSocialiteAvatar }}" alt="{{ $hhSocialiteName }}" class="w-10 h-10 rounded-full shadow object-cover">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-black dark:text-white truncate">{{ $hhSocialiteName }}</h4>
                                            <div class="text-sm mt-1 text-blue-600 font-light dark:text-white/70 truncate">{{ $hhSocialiteHandle }}</div>
                                        </div>
                                    </div>
                                </a>

                                <hr class="dark:border-gray-600/60">

                                <nav class="p-2 text-sm text-black font-normal dark:text-white">
                                    <a href="{{ route('profile.edit') }}">
                                        <div class="flex items-center gap-2.5 hover:bg-secondery p-2 px-2.5 rounded-md dark:hover:bg-white/10">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125L16.875 4.5M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            {{ __('ui.edit_profile') }}
                                        </div>
                                    </a>
                                    <a href="{{ route('gamification.index') }}">
                                        <div class="flex items-center gap-2.5 hover:bg-secondery p-2 px-2.5 rounded-md dark:hover:bg-white/10 text-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                            </svg>
                                            {{ __('ui.badges_quests') }} 
                                        </div>
                                    </a>  
                                    <a href="{{ route('settings.security.index') }}">
                                        <div class="flex items-center gap-2.5 hover:bg-secondery p-2 px-2.5 rounded-md dark:hover:bg-white/10"> 
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                            </svg>
                                            {{ __('ui.security') }} 
                                        </div>
                                    </a>
                                    <a href="{{ route('settings.privacy.edit') }}">
                                        <div class="flex items-center gap-2.5 hover:bg-secondery p-2 px-2.5 rounded-md dark:hover:bg-white/10"> 
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
                                            </svg>
                                            {{ __('ui.privacy') }}
                                        </div>
                                    </a>
                                    <a href="{{ route('settings.security.index') }}">
                                        <div class="flex items-center gap-2.5 hover:bg-secondery p-2 px-2.5 rounded-md dark:hover:bg-white/10"> 
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ __('ui.account') }}
                                        </div>
                                    </a>
                                    @php($hhCurrentLocale = app()->getLocale() === 'en' ? 'en' : 'de')
                                    <div class="p-2 px-2.5 rounded-md">
                                        <div class="flex items-center gap-2.5 mb-2 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3c2.25 2.45 3.38 5.45 3.38 9S14.25 18.55 12 21M12 3C9.75 5.45 8.62 8.45 8.62 12S9.75 18.55 12 21" />
                                            </svg>
                                            <span>{{ __('ui.language') }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
                                            <a href="{{ route('locale.switch', 'de') }}" class="hnt-header-locale-pill {{ $hhCurrentLocale === 'de' ? 'is-active' : '' }}">
                                                DE
                                            </a>
                                            <a href="{{ route('locale.switch', 'en') }}" class="hnt-header-locale-pill {{ $hhCurrentLocale === 'en' ? 'is-active' : '' }}">
                                                EN
                                            </a>
                                        </div>
                                    </div>
                                    <hr class="-mx-2 my-2 dark:border-gray-600/60">
                                    @auth
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="w-full text-left">
                                                <div class="flex items-center gap-2.5 hover:bg-secondery p-2 px-2.5 rounded-md dark:hover:bg-white/10"> 
                                                    <svg class="w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                    </svg>
                                                    {{ __('ui.logout') }} 
                                                </div>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ url('/login') }}">
                                            <div class="flex items-center gap-2.5 hover:bg-secondery p-2 px-2.5 rounded-md dark:hover:bg-white/10"> 
                                                <svg class="w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                </svg>
                                                {{ __('ui.login') }} 
                                            </div>
                                        </a>
                                    @endauth
    
                                </nav>

                            </div> 

                            <div class="flex items-center gap-2 hidden">
        
                                <img src="/assets/socialite/images/avatars/avatar-2.jpg" alt="" class="w-9 h-9 rounded-full shadow">
        
                                <div class="w-20 font-semibold text-gray-600"> Hamse </div>
        
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg> 
        
                            </div> 
        
                        </div>

                    </div> 

                </div>

            </div>

        </header>
    
<script>
(function () {
    const input = document.getElementById('hh-header-search-input');
    const results = document.getElementById('hh-header-search-results');
    const clearButton = document.getElementById('hh-header-search-clear');

    if (!input || !results) {
        return;
    }

    const endpoint = input.dataset.searchUrl;
    const labels = {
        hint: @json(__('ui.header_search_hint')),
        loading: @json(__('ui.loading')),
        empty: @json(__('ui.header_search_empty')),
        error: @json(__('ui.header_search_error')),
    };

    let timer = null;
    let controller = null;

    const escapeHtml = function (value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const state = function (message) {
        results.innerHTML = '<div class="px-3 py-3 text-sm text-gray-500 dark:text-gray-300">' + escapeHtml(message) + '</div>';
    };

    const render = function (items) {
        if (!Array.isArray(items) || items.length === 0) {
            state(labels.empty);
            return;
        }

        results.innerHTML = items.map(function (item) {
            const title = escapeHtml(item.title);
            const subtitle = escapeHtml(item.subtitle);
            const url = escapeHtml(item.url || '#');
            const avatar = escapeHtml(item.avatar || '/assets/vikinger/img/default-avatar.svg');
            const icon = item.type === 'team' ? 'people-circle-outline' : 'person-circle-outline';

            return '<a href="' + url + '" class="relative px-3 py-2 flex items-center gap-4 hover:bg-secondery rounded-lg dark:hover:bg-white/10">'
                + '<img src="' + avatar + '" alt="" class="w-9 h-9 rounded-full object-cover bg-slate-700">'
                + '<div class="min-w-0 flex-1">'
                + '<div class="truncate text-amber-400">' + title + '</div>'
                + '<div class="text-xs text-gray-500 dark:text-gray-300 font-medium mt-0.5">' + subtitle + '</div>'
                + '</div>'
                + '<ion-icon name="' + icon + '" class="text-xl text-amber-500"></ion-icon>'
                + '</a>';
        }).join('');
    };

    const search = function () {
        const query = input.value.trim();

        if (query.length < 2) {
            if (controller) {
                controller.abort();
                controller = null;
            }
            state(labels.hint);
            return;
        }

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        state(labels.loading);

        fetch(endpoint + '?q=' + encodeURIComponent(query), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Search request failed');
                }

                return response.json();
            })
            .then(function (payload) {
                render(payload.results || []);
            })
            .catch(function (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                state(labels.error);
            });
    };

    input.addEventListener('input', function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(search, 220);
    });

    input.addEventListener('focus', function () {
        if (input.value.trim().length < 2) {
            state(labels.hint);
            return;
        }

        search();
    });

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            input.value = '';
            state(labels.hint);
            input.focus();
        });
    }
})();
</script>

