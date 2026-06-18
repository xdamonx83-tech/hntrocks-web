@extends('themes.socialite.layouts.app')

@section('title', __('ui.messages') . ' · HNT.rocks')
@section('meta_description', __('ui.messages_banner_text'))

@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $selectedOther = $selectedConversation?->otherParticipant($viewer);
    $activeMessageType = $activeMessageType ?? 'private';
    $messageTypeCounts = $messageTypeCounts ?? ['private' => 0, 'lfg' => 0];
    $isLfgMessageTab = $activeMessageType === 'lfg';
    $conversationTotal = method_exists($conversations, 'total') ? $conversations->total() : $conversations->count();
    $unreadTotal = method_exists($viewer, 'unreadMessagesCount') ? $viewer->unreadMessagesCount() : 0;
    $formatMessageDay = static function ($date): string {
        if (! $date) {
            return '';
        }

        if ($date->isToday()) {
            return app()->getLocale() === 'de' ? 'Heute' : 'Today';
        }

        if ($date->isYesterday()) {
            return app()->getLocale() === 'de' ? 'Gestern' : 'Yesterday';
        }

        return $date->translatedFormat('d. F Y');
    };
@endphp

@section('content')
<div class="relative overflow-hidden border -m-2.5 dark:border-slate-700">
    <div class="flex bg-white dark:bg-dark2">
        <!-- sidebar -->
        <div class="md:w-[360px] relative border-r dark:border-slate-700">
            <div id="side-chat" class="top-0 left-0 max-md:fixed max-md:w-5/6 max-md:h-screen bg-white z-50 max-md:shadow max-md:-translate-x-full dark:bg-dark2">
                <!-- heading title -->
                <div class="p-4 border-b dark:border-slate-700">
                    <div class="flex mt-2 items-center justify-between">
                        <h2 class="text-2xl font-bold text-black ml-1 dark:text-white">{{ __('ui.messages') }}</h2>

                        <!-- right action buttons -->
                        <div class="flex items-center gap-2.5">
                            <button class="group" type="button" aria-label="{{ __('ui.messages') }}">
                                <ion-icon name="settings-outline" class="text-2xl flex group-aria-expanded:rotate-180"></ion-icon>
                            </button>
                            <div class="md:w-[270px] w-full" uk-dropdown="pos: bottom-left; offset:10; animation: uk-animation-slide-bottom-small">
                                <nav>
                                    <a href="{{ route('messages.index', ['type' => 'private']) }}">
                                        <ion-icon class="text-2xl shrink-0 -ml-1" name="chatbubble-ellipses-outline"></ion-icon>
                                        {{ __('ui.message_tab_private') }}
                                        <span class="ml-auto text-xs text-gray-400">{{ $messageTypeCounts['private'] ?? 0 }}</span>
                                    </a>
                                    <a href="{{ route('messages.index', ['type' => 'lfg']) }}">
                                        <ion-icon class="text-2xl shrink-0 -ml-1" name="people-outline"></ion-icon>
                                        {{ __('ui.message_tab_lfg') }}
                                        <span class="ml-auto text-xs text-gray-400">{{ $messageTypeCounts['lfg'] ?? 0 }}</span>
                                    </a>
                                    <a href="{{ route('account.settings.edit') }}">
                                        <ion-icon class="text-2xl shrink-0 -ml-1" name="notifications-outline"></ion-icon>
                                        {{ __('ui.notifications') }}
                                    </a>
                                </nav>
                            </div>

                            @if ($activeMessageType === 'private')
                                <button type="button" uk-toggle="target: #new-message-panel ; cls: hidden" aria-label="{{ __('ui.message_start_new') }}">
                                    <ion-icon name="create-outline" class="text-2xl flex"></ion-icon>
                                </button>
                            @endif

                            <!-- mobile toggle menu -->
                            <button type="button" class="md:hidden" uk-toggle="target: #side-chat ; cls: max-md:-translate-x-full" aria-label="{{ __('ui.messages') }}">
                                <ion-icon name="chevron-down-outline"></ion-icon>
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4 text-xs font-semibold">
                        <a href="{{ route('messages.index', ['type' => 'private']) }}" class="px-3 py-1.5 rounded-full {{ $activeMessageType === 'private' ? 'bg-black text-white dark:bg-white dark:text-black' : 'bg-secondery text-gray-600 dark:bg-white/10 dark:text-white/80' }}">
                            {{ __('ui.message_tab_private') }} · {{ $messageTypeCounts['private'] ?? 0 }}
                        </a>
                        <a href="{{ route('messages.index', ['type' => 'lfg']) }}" class="px-3 py-1.5 rounded-full {{ $activeMessageType === 'lfg' ? 'bg-black text-white dark:bg-white dark:text-black' : 'bg-secondery text-gray-600 dark:bg-white/10 dark:text-white/80' }}">
                            {{ __('ui.message_tab_lfg') }} · {{ $messageTypeCounts['lfg'] ?? 0 }}
                        </a>
                    </div>

                    <!-- search -->
                    <div class="relative mt-4">
                        <div class="absolute left-3 bottom-1/2 translate-y-1/2 flex"><ion-icon name="search" class="text-xl"></ion-icon></div>
                        <input type="text" placeholder="{{ __('ui.search') }}" class="w-full !pl-10 !py-2 !rounded-lg" data-socialite-message-search>
                    </div>

                    @if ($activeMessageType === 'private')
                        <div id="new-message-panel" class="hidden mt-4 rounded-xl bg-secondery p-3 dark:bg-white/5">
                            <form method="post" action="{{ route('messages.start') }}" class="space-y-3">
                                @csrf
                                <select name="recipient_id" class="w-full !rounded-lg !border-0 !bg-white !px-3 !py-2 !text-sm dark:!bg-dark3 dark:!text-white" required>
                                    <option value="">{{ __('ui.message_choose_player') }}</option>
                                    @foreach ($recipients as $recipient)
                                        <option value="{{ $recipient->id }}" @selected((string) old('recipient_id') === (string) $recipient->id)>{{ $recipient->name }} ({{ '@'.$recipient->username }})</option>
                                    @endforeach
                                </select>
                                <input name="body" type="text" value="{{ old('body') }}" placeholder="{{ __('ui.message_start_placeholder') }}" class="w-full !rounded-lg !border-0 !bg-white !px-3 !py-2 !text-sm dark:!bg-dark3 dark:!text-white" required maxlength="3000">
                                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">{{ __('ui.message_start_button') }}</button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- users list -->
                <div class="space-y-2 p-2 overflow-y-auto md:h-[calc(100vh-204px)] h-[calc(100vh-130px)]" data-socialite-message-list>
                    @forelse ($conversations as $conversation)
                        @php
                            $other = $conversation->otherParticipant($viewer);
                            $unread = $conversation->unreadCountFor($viewer);
                            $latestBody = $conversation->latestMessage?->body;
                            $latestAt = $conversation->latestMessage?->created_at ?? $conversation->updated_at;
                            $conversationTitle = $conversation->displayTitleFor($viewer);
                        @endphp

                        <a href="{{ route('messages.show', $conversation) }}" class="relative flex items-center gap-4 p-2 duration-200 rounded-xl hover:bg-secondery {{ $selectedConversation?->id === $conversation->id ? 'bg-secondery dark:bg-white/10' : '' }}" data-socialite-message-row data-search-text="{{ Str::lower($conversationTitle.' '.($other?->username ?? '').' '.($latestBody ?? '')) }}">
                            <div class="relative w-14 h-14 shrink-0">
                                <img src="{{ $other?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg') }}" alt="{{ $conversationTitle }}" class="object-cover w-full h-full rounded-full">
                                @if ($selectedConversation?->id === $conversation->id)
                                    <div class="w-4 h-4 absolute bottom-0 right-0 bg-green-500 rounded-full border border-white dark:border-slate-800"></div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <div class="mr-auto text-sm text-black dark:text-white font-medium truncate">
                                        @if ($conversation->isLfgConversation())
                                            <span class="text-[10px] mr-1 px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-300">{{ $conversation->contextBadgeLabel() ?? __('ui.message_lfg_badge') }}</span>
                                        @endif
                                        {{ $conversationTitle }}
                                    </div>
                                    <div class="text-xs font-light text-gray-500 dark:text-white/70 shrink-0">{{ $latestAt?->format('H:i') }}</div>
                                    @if ($unread > 0)
                                        <div class="w-2.5 h-2.5 bg-blue-600 rounded-full dark:bg-blue-500"></div>
                                    @endif
                                </div>
                                <div class="font-medium overflow-hidden text-ellipsis text-sm whitespace-nowrap {{ $unread > 0 ? 'text-black dark:text-white' : 'text-gray-600 dark:text-white/70' }}">
                                    {{ $latestBody ? Str::limit($latestBody, 76) : __('ui.message_no_messages_yet') }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-6 text-sm text-center text-gray-500 dark:text-white/70">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                                <ion-icon name="chatbubble-ellipses-outline" class="text-2xl"></ion-icon>
                            </div>
                            <p class="font-semibold text-black dark:text-white">{{ $isLfgMessageTab ? __('ui.message_no_lfg_conversations') : __('ui.message_no_conversations') }}</p>
                            <p class="mt-1 leading-5">{{ $isLfgMessageTab ? __('ui.message_no_lfg_conversations_text') : __('ui.message_no_conversations_text') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- overly -->
            <div id="side-chat" class="bg-slate-100/40 backdrop-blur w-full h-full dark:bg-slate-800/40 z-40 fixed inset-0 max-md:-translate-x-full md:hidden" uk-toggle="target: #side-chat ; cls: max-md:-translate-x-full"></div>
        </div>

        <!-- message center -->
        <div class="flex-1 min-w-0">
            @if ($selectedConversation)
                <!-- chat heading -->
                <div class="flex items-center justify-between gap-2 w- px-6 py-3.5 z-10 border-b dark:border-slate-700 uk-animation-slide-top-medium">
                    <div class="flex items-center sm:gap-4 gap-2">
                        <!-- toggle for mobile -->
                        <button type="button" class="md:hidden" uk-toggle="target: #side-chat ; cls: max-md:-translate-x-full" aria-label="{{ __('ui.messages') }}">
                            <ion-icon name="chevron-back-outline" class="text-2xl -ml-4"></ion-icon>
                        </button>

                        @if ($selectedOther)
                            <div class="relative cursor-pointer max-md:hidden" uk-toggle="target: .rightt ; cls: hidden">
                                <img src="{{ $selectedOther->avatarUrl() }}" alt="{{ $selectedOther->name }}" class="w-8 h-8 rounded-full shadow">
                                <div class="w-2 h-2 bg-teal-500 rounded-full absolute right-0 bottom-0 m-px"></div>
                            </div>
                        @endif
                        <div class="cursor-pointer" uk-toggle="target: .rightt ; cls: hidden">
                            <div class="text-base font-bold text-black dark:text-white">
                                @if ($selectedConversation->isLfgConversation())
                                    <span class="align-middle text-[10px] mr-1 px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-300">{{ $selectedConversation->contextBadgeLabel() ?? __('ui.message_lfg_badge') }}</span>
                                @endif
                                {{ $selectedConversation->displayTitleFor($viewer) }}
                            </div>
                            <div class="text-xs text-gray-500 font-semibold dark:text-white/70">
                                {{ $selectedOther ? '@'.$selectedOther->username : __('ui.private_conversation') }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($selectedOther)
                            <a href="{{ route('profile.public', $selectedOther) }}" class="hover:bg-slate-100 p-1.5 rounded-full dark:hover:bg-white/10" aria-label="{{ __('ui.view_profile') }}">
                                <ion-icon name="person-circle-outline" class="text-2xl flex"></ion-icon>
                            </a>
                        @endif
                        <button type="button" class="hover:bg-slate-100 p-1.5 rounded-full dark:hover:bg-white/10" uk-toggle="target: .rightt ; cls: hidden" aria-label="{{ __('ui.info') }}">
                            <ion-icon name="information-circle-outline" class="text-2xl flex"></ion-icon>
                        </button>
                    </div>
                </div>

                @if ($selectedConversation->isLfgConversation())
                    <div class="px-6 py-3 border-b bg-slate-50/70 dark:border-slate-700 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <div class="font-semibold text-black dark:text-white">{{ __('ui.message_lfg_context') }}</div>
                                <div class="truncate text-gray-500 dark:text-white/70">{{ $selectedConversation->context_label ?? $selectedConversation->displayTitleFor($viewer) }}</div>
                            </div>
                            @if ($selectedConversation->context_url)
                                <a href="{{ $selectedConversation->context_url }}" class="shrink-0 text-blue-500 font-semibold">{{ __('ui.message_lfg_context_open') }}</a>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- chats bubble -->
                <div class="w-full p-5 py-10 overflow-y-auto md:h-[calc(100vh-204px)] h-[calc(100vh-195px)]" data-socialite-message-thread>
                    @if ($selectedOther)
                        <div class="py-10 text-center text-sm lg:pt-8">
                            <img src="{{ $selectedOther->avatarUrl() }}" class="w-24 h-24 rounded-full mx-auto mb-3 object-cover" alt="{{ $selectedOther->name }}">
                            <div class="mt-8">
                                <div class="md:text-xl text-base font-medium text-black dark:text-white">{{ $selectedOther->name }}</div>
                                <div class="text-gray-500 text-sm dark:text-white/80">{{ '@'.$selectedOther->username }}</div>
                            </div>
                            <div class="mt-3.5">
                                <a href="{{ route('profile.public', $selectedOther) }}" class="inline-block rounded-lg px-4 py-1.5 text-sm font-semibold bg-secondery dark:bg-white/10">{{ __('ui.view_profile') }}</a>
                            </div>
                        </div>
                    @endif

                    <div class="text-sm font-medium space-y-6">
                        @php
                            $lastMessageDay = null;
                        @endphp
                        @forelse ($messages as $message)
                            @php
                                $messageDay = $message->created_at?->toDateString();
                                $isOwnMessage = (int) $message->user_id === (int) $viewer->id;
                                $messageAuthor = $message->user;
                            @endphp

                            @if ($messageDay && $messageDay !== $lastMessageDay)
                                <div class="flex justify-center">
                                    <div class="font-medium text-gray-500 text-sm dark:text-white/70">
                                        {{ $formatMessageDay($message->created_at) }}
                                    </div>
                                </div>
                                @php
                                    $lastMessageDay = $messageDay;
                                @endphp
                            @endif

                            @if ($message->isSystemMessage())
                                <div class="flex justify-center">
                                    <div class="px-4 py-2 rounded-[20px] max-w-sm bg-secondery text-gray-600 dark:bg-white/10 dark:text-white/70">{{ $message->body }}</div>
                                </div>
                            @elseif ($isOwnMessage)
                                <div class="flex gap-2 flex-row-reverse items-end">
                                    <img src="{{ $viewer->avatarUrl() }}" alt="{{ $viewer->name }}" class="w-5 h-5 rounded-full shadow object-cover">
                                    <div class="px-4 py-2 rounded-[20px] max-w-sm bg-gradient-to-tr from-sky-500 to-blue-500 text-white shadow whitespace-pre-wrap break-words">{{ $message->body }}</div>
                                </div>
                            @else
                                <div class="flex gap-3">
                                    <img src="{{ $messageAuthor?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg') }}" alt="{{ $messageAuthor?->name ?? '' }}" class="w-9 h-9 rounded-full shadow object-cover">
                                    <div class="px-4 py-2 rounded-[20px] max-w-sm bg-secondery whitespace-pre-wrap break-words dark:bg-white/10">{{ $message->body }}</div>
                                </div>
                            @endif
                        @empty
                            <div class="py-10 text-center text-sm text-gray-500 dark:text-white/70">
                                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                                    <ion-icon name="chatbubble-ellipses-outline" class="text-3xl"></ion-icon>
                                </div>
                                <div class="font-semibold text-black dark:text-white">{{ __('ui.message_thread_empty') }}</div>
                                <div class="mt-1">{{ __('ui.message_thread_empty_text') }}</div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- sending message area -->
                <form method="post" action="{{ route('messages.store', $selectedConversation) }}" class="flex items-center md:gap-4 gap-2 md:p-3 p-2 overflow-hidden border-t dark:border-slate-700"
                    @if (! $selectedConversation->isLfgConversation())
                        data-hh-message-typing-form
                        data-conversation-id="{{ $selectedConversation->id }}"
                        data-typing-url="{{ route('api.v1.messages.typing', $selectedConversation) }}"
                        data-csrf-token="{{ csrf_token() }}"
                    @endif>
                    @csrf
                    <div id="message__wrap" class="flex items-center gap-2 h-full dark:text-white -mt-1.5">
                        <button type="button" class="shrink-0" aria-hidden="true" tabindex="-1">
                            <ion-icon class="text-3xl flex" name="happy-outline"></ion-icon>
                        </button>
                    </div>

                    <div class="relative flex-1">
                        <textarea name="body" placeholder="{{ __('ui.message_write_placeholder') }}" rows="1" class="w-full resize-none bg-secondery rounded-full px-4 p-2 pr-11 dark:bg-white/10" maxlength="3000" required @if (! $selectedConversation->isLfgConversation()) data-hh-message-typing-input @endif></textarea>
                        <button type="submit" class="text-white shrink-0 p-2 absolute right-0.5 top-0 bg-blue-600 rounded-full" aria-label="{{ __('ui.send') }}">
                            <ion-icon class="text-xl flex" name="send-outline"></ion-icon>
                        </button>
                    </div>
                </form>
            @else
                <div class="flex items-center justify-between gap-2 w- px-6 py-3.5 z-10 border-b dark:border-slate-700 uk-animation-slide-top-medium">
                    <div class="flex items-center sm:gap-4 gap-2">
                        <button type="button" class="md:hidden" uk-toggle="target: #side-chat ; cls: max-md:-translate-x-full" aria-label="{{ __('ui.messages') }}">
                            <ion-icon name="chevron-back-outline" class="text-2xl -ml-4"></ion-icon>
                        </button>
                        <div>
                            <div class="text-base font-bold text-black dark:text-white">{{ $isLfgMessageTab ? __('ui.message_tab_lfg') : __('ui.message_tab_private') }}</div>
                            <div class="text-xs text-gray-500 font-semibold dark:text-white/70">{{ $conversationTotal }} {{ __('ui.message_conversations') }} · {{ $unreadTotal }} {{ __('ui.message_unread') }}</div>
                        </div>
                    </div>
                </div>

                <div class="w-full p-5 py-10 overflow-y-auto md:h-[calc(100vh-144px)] h-[calc(100vh-140px)]">
                    <div class="py-20 text-center text-sm lg:pt-20">
                        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                            <ion-icon name="chatbubble-ellipses-outline" class="text-4xl text-gray-500 dark:text-white/70"></ion-icon>
                        </div>
                        <div class="mt-6">
                            <div class="md:text-xl text-base font-medium text-black dark:text-white">{{ __('ui.message_choose_conversation') }}</div>
                            <div class="max-w-sm mx-auto text-gray-500 text-sm mt-2 dark:text-white/80">{{ $isLfgMessageTab ? __('ui.message_lfg_choose_conversation_text') : __('ui.message_choose_conversation_text') }}</div>
                        </div>
                        @if ($activeMessageType === 'private')
                            <div class="mt-5">
                                <button type="button" class="inline-block rounded-lg px-4 py-1.5 text-sm font-semibold bg-secondery dark:bg-white/10" uk-toggle="target: #new-message-panel ; cls: hidden">{{ __('ui.message_start_new') }}</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- user profile right info -->
            @if ($selectedConversation && $selectedOther)
                <div class="rightt w-full h-full absolute top-0 right-0 z-10 hidden transition-transform">
                    <div class="w-[360px] border-l shadow-lg h-screen bg-white absolute right-0 top-0 uk-animation-slide-right-medium delay-200 z-50 dark:bg-dark2 dark:border-slate-700">
                        <div class="w-full h-1.5 bg-gradient-to-r to-purple-500 via-red-500 from-pink-500 -mt-px"></div>

                        <div class="py-10 text-center text-sm pt-20">
                            <img src="{{ $selectedOther->avatarUrl() }}" class="w-24 h-24 rounded-full mx-auto mb-3 object-cover" alt="{{ $selectedOther->name }}">
                            <div class="mt-8">
                                <div class="md:text-xl text-base font-medium text-black dark:text-white">{{ $selectedOther->name }}</div>
                                <div class="text-gray-500 text-sm mt-1 dark:text-white/80">{{ '@'.$selectedOther->username }}</div>
                            </div>
                            <div class="mt-5">
                                <a href="{{ route('profile.public', $selectedOther) }}" class="inline-block rounded-full px-4 py-1.5 text-sm font-semibold bg-secondery dark:bg-white/10">{{ __('ui.view_profile') }}</a>
                            </div>
                        </div>

                        <hr class="opacity-80 dark:border-slate-700">

                        <ul class="text-base font-medium p-3">
                            <li>
                                <a href="{{ route('profile.public', $selectedOther) }}" class="flex items-center gap-5 rounded-md p-3 w-full hover:bg-secondery dark:hover:bg-white/10">
                                    <ion-icon name="person-circle-outline" class="text-2xl"></ion-icon>
                                    {{ __('ui.view_profile') }}
                                </a>
                            </li>
                            @if ($selectedConversation->isLfgConversation() && $selectedConversation->context_url)
                                <li>
                                    <a href="{{ $selectedConversation->context_url }}" class="flex items-center gap-5 rounded-md p-3 w-full hover:bg-secondery dark:hover:bg-white/10">
                                        <ion-icon name="people-outline" class="text-2xl"></ion-icon>
                                        {{ __('ui.message_lfg_context_open') }}
                                    </a>
                                </li>
                            @endif
                        </ul>

                        <!-- close button -->
                        <button type="button" class="absolute top-0 right-0 m-4 p-2 bg-secondery rounded-full dark:bg-white/10" uk-toggle="target: .rightt ; cls: hidden" aria-label="{{ __('ui.close') }}">
                            <ion-icon name="close" class="text-2xl flex"></ion-icon>
                        </button>
                    </div>

                    <!-- overly -->
                    <div class="bg-slate-100/40 backdrop-blur absolute w-full h-full dark:bg-slate-800/40" uk-toggle="target: .rightt ; cls: hidden"></div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if ($selectedConversation && ! $selectedConversation->isLfgConversation())
    <script src="{{ asset('assets/socialite/js/hnt-socialite-message-typing.js') }}?v=172ed" defer></script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('[data-socialite-message-search]');
        const rows = Array.from(document.querySelectorAll('[data-socialite-message-row]'));

        if (searchInput && rows.length) {
            searchInput.addEventListener('input', function () {
                const term = searchInput.value.trim().toLowerCase();

                rows.forEach(function (row) {
                    const haystack = (row.dataset.searchText || '').toLowerCase();
                    row.hidden = term !== '' && !haystack.includes(term);
                });
            });
        }

        const thread = document.querySelector('[data-socialite-message-thread]');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }
    });
</script>
@endpush
