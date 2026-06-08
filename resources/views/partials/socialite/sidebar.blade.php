@php
    $viewer = auth()->user();
    $navItems = [
        ['label' => __('ui.feed'), 'route' => route('feed.index'), 'icon' => 'home-outline', 'active' => request()->routeIs('feed.*')],
        ['label' => __('ui.members'), 'route' => route('members.index'), 'icon' => 'people-outline', 'active' => request()->routeIs('members.*')],
        ['label' => 'Teams', 'route' => route('teams.index'), 'icon' => 'shield-outline', 'active' => request()->routeIs('teams.*')],
        ['label' => 'LFG', 'route' => route('lfg.index'), 'icon' => 'person-add-outline', 'active' => request()->routeIs('lfg.*')],
        ['label' => 'Cups', 'route' => route('cups.index'), 'icon' => 'trophy-outline', 'active' => request()->routeIs('cups.*')],
        ['label' => 'Moments', 'route' => route('moments.index'), 'icon' => 'play-circle-outline', 'active' => request()->routeIs('moments.*')],
        ['label' => __('ui.messages'), 'route' => route('messages.index'), 'icon' => 'chatbubbles-outline', 'active' => request()->routeIs('messages.*')],
        ['label' => __('ui.settings'), 'route' => route('settings.security.index'), 'icon' => 'settings-outline', 'active' => request()->routeIs('settings.*')],
    ];
@endphp
<div id="site__sidebar" class="fixed top-0 left-0 z-[99] pt-[--m-top] overflow-hidden transition-transform xl:duration-500 max-xl:w-full max-xl:-translate-x-full hh-socialite-sidebar-wrap">
    <div class="p-4 max-xl:bg-white/80 backdrop-blur h-screen 2xl:w-72 w-[--w-side-sm] max-xl:w-80 max-xl:shadow max-xl:border-r border-slate-200 hh-socialite-sidebar">
        @if($viewer)
            <a href="{{ route('profile.show') }}" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-secondery hh-socialite-profile-card">
                <img src="{{ $viewer->avatarUrl() }}" alt="{{ $viewer->name }}" class="w-11 h-11 rounded-full object-cover">
                <div class="min-w-0">
                    <div class="font-semibold text-black truncate">{{ $viewer->name }}</div>
                    <div class="text-xs text-gray-500">{{ __('ui.level') }} {{ $viewer->level ?? 1 }}</div>
                </div>
            </a>
        @endif
        <nav class="mt-4 space-y-1 text-sm font-semibold hh-socialite-menu">
            @foreach($navItems as $item)
                <a href="{{ $item['route'] }}" class="{{ $item['active'] ? 'active' : '' }}">
                    <ion-icon name="{{ $item['icon'] }}"></ion-icon>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="mt-6 p-4 rounded-2xl bg-white border border-slate-200 shadow-sm hh-socialite-beta-card">
            <div class="text-sm font-bold text-black">Android App</div>
            <p class="mt-1 text-xs text-gray-500">{{ 'Hilf uns beim Google-Play-Start.' }}</p>
            <a href="{{ url('/app-beta') }}" class="mt-3 inline-flex items-center justify-center w-full rounded-xl px-3 py-2 text-sm font-bold bg-blue-600 text-white">{{ __('ui.app_promo_cta') }}</a>
        </div>
    </div>
    <div id="site__sidebar__overly" class="absolute top-0 left-0 z-20 w-screen h-screen xl:hidden backdrop-blur-sm bg-slate-900/30" uk-toggle="target: #site__sidebar ; cls :!-translate-x-0"></div>
</div>
