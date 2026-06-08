@php
    $viewer = auth()->user();
@endphp
<header class="z-[100] h-[--m-top] fixed top-0 left-0 w-full flex items-center bg-white/80 backdrop-blur-xl border-b border-slate-200 hh-socialite-header">
    <div class="flex items-center w-full xl:px-6 px-2 max-lg:gap-6">
        <div class="2xl:w-[--w-side] lg:w-[--w-side-sm]">
            <div class="flex items-center gap-2">
                <button uk-toggle="target: #site__sidebar ; cls :!-translate-x-0" class="flex items-center justify-center w-9 h-9 text-xl rounded-full hover:bg-gray-100 xl:hidden group" type="button" aria-label="Menu">
                    <ion-icon name="menu-outline" class="text-2xl group-aria-expanded:hidden"></ion-icon>
                    <ion-icon name="close-outline" class="hidden text-2xl group-aria-expanded:block"></ion-icon>
                </button>
                <a href="{{ route('feed.index') }}" class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('assets/vikinger/img/favicon-96x96.png') }}" alt="HNT.rocks" class="w-9 h-9 rounded-xl shadow-sm">
                    <span class="text-xl font-extrabold tracking-tight text-black max-md:hidden">HNT.rocks</span>
                </a>
            </div>
        </div>

        <div class="flex-1 relative">
            <div class="max-w-[1220px] mx-auto flex items-center gap-4">
                <form action="{{ route('members.index') }}" method="get" id="search--box" class="xl:w-[520px] sm:w-96 sm:relative rounded-xl overflow-hidden z-20 bg-secondery max-md:hidden w-screen left-0 max-sm:fixed max-sm:top-2 hh-socialite-search">
                    <ion-icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2"></ion-icon>
                    <input type="text" name="q" placeholder="{{ __('ui.search') }}" class="w-full !pl-10 !font-normal !bg-transparent h-12 !text-sm">
                </form>

                <nav class="flex items-center gap-1 max-lg:hidden hh-socialite-topnav">
                    <a href="{{ route('feed.index') }}" class="{{ request()->routeIs('feed.*') ? 'active' : '' }}">{{ __('ui.feed') }}</a>
                    <a href="{{ route('teams.index') }}" class="{{ request()->routeIs('teams.*') ? 'active' : '' }}">Teams</a>
                    <a href="{{ route('lfg.index') }}" class="{{ request()->routeIs('lfg.*') ? 'active' : '' }}">LFG</a>
                    <a href="{{ route('cups.index') }}" class="{{ request()->routeIs('cups.*') ? 'active' : '' }}">Cups</a>
                </nav>
            </div>
        </div>

        <div class="flex items-center sm:gap-4 gap-2 text-black">
            <a href="{{ route('notifications.index') }}" class="sm:p-2 p-1 rounded-full relative sm:bg-secondery hh-socialite-icon-btn" aria-label="{{ __('ui.notifications') }}">
                <ion-icon name="notifications-outline" class="text-2xl"></ion-icon>
                @if($viewer && ($viewer->unreadNotifications()->count() ?? 0) > 0)
                    <span class="absolute top-0 right-0 -m-1 bg-red-600 text-white text-xs px-1 rounded-full">{{ min(99, $viewer->unreadNotifications()->count()) }}</span>
                @endif
            </a>
            <a href="{{ route('messages.index') }}" class="sm:p-2 p-1 rounded-full relative sm:bg-secondery hh-socialite-icon-btn" aria-label="{{ __('ui.messages') }}">
                <ion-icon name="mail-outline" class="text-2xl"></ion-icon>
            </a>
            @if($viewer)
                <a href="{{ route('profile.show') }}" class="rounded-full overflow-hidden border-2 border-white shadow-sm">
                    <img src="{{ $viewer->avatarUrl() }}" alt="{{ $viewer->name }}" class="w-9 h-9 object-cover">
                </a>
            @endif
        </div>
    </div>
</header>
