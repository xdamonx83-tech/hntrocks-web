@auth
@php
    $hhMobileUser = auth()->user();
    $hhMobileDashboardUrl = $hhMobileUser->isAdmin() ? route('overview.index') : route('feed.index');
    $hhMobileNavItems = app(\App\Services\Navigation\MobileNavService::class)->itemsForUser($hhMobileUser);
    $hhMobileHasProfileSheet = collect($hhMobileNavItems)->contains(fn ($item) => ($item['action'] ?? 'link') === \App\Services\Navigation\MobileNavService::PROFILE_SHEET_ACTION);
@endphp
@if($hhMobileNavItems !== [])
<nav class="hh-mobile-bottom-nav hh-mobile-bottom-nav-count-{{ min(count($hhMobileNavItems), 5) }}" aria-label="{{ __('ui.mobile_navigation') }}">
    @foreach($hhMobileNavItems as $hhMobileNavItem)
        @php
            $hhMobileNavIcon = $hhMobileNavItem['phosphor'] ?? 'circle';
            $hhMobileNavActive = ! empty($hhMobileNavItem['active']);
            $hhMobileNavIsProfileSheet = ($hhMobileNavItem['action'] ?? 'link') === \App\Services\Navigation\MobileNavService::PROFILE_SHEET_ACTION;
        @endphp

        @if($hhMobileNavIsProfileSheet)
            <button class="hh-mobile-bottom-nav-item {{ $hhMobileNavActive ? 'is-active' : '' }}" type="button" data-hh-mobile-profile-open aria-controls="hh-mobile-profile-sheet" aria-expanded="false">
                <i class="ph ph-{{ $hhMobileNavIcon }}" aria-hidden="true"></i>
                <small>{{ $hhMobileNavItem['label'] }}</small>
            </button>
        @else
            <a href="{{ $hhMobileNavItem['url'] }}" class="hh-mobile-bottom-nav-item {{ $hhMobileNavActive ? 'is-active' : '' }}" @if(! empty($hhMobileNavItem['external'])) target="_blank" rel="noopener noreferrer" @endif>
                <i class="ph ph-{{ $hhMobileNavIcon }}" aria-hidden="true"></i>
                <small>{{ $hhMobileNavItem['label'] }}</small>
            </a>
        @endif
    @endforeach
</nav>
@endif

@if($hhMobileHasProfileSheet)
<div class="hh-mobile-profile-backdrop" data-hh-mobile-profile-close hidden></div>
<section id="hh-mobile-profile-sheet" class="hh-mobile-profile-sheet" aria-labelledby="hh-mobile-profile-title" aria-hidden="true" hidden>
    <div class="hh-mobile-profile-sheet-handle" aria-hidden="true"></div>

    <div class="hh-mobile-profile-sheet-head">
        <p id="hh-mobile-profile-title">{{ __('ui.mobile_profile_sheet_title') }}</p>
        <button type="button" data-hh-mobile-profile-close>{{ __('ui.close') }}</button>
    </div>

    <a class="hh-mobile-profile-user-card" href="{{ route('profile.show') }}">
        <img src="{{ $hhMobileUser->avatarUrl() }}" alt="">
        <span>
            <strong>{{ $hhMobileUser->name ?: $hhMobileUser->username }}</strong>
            <small>{{ $hhMobileUser->email }}</small>
            <em>{{ __('ui.online') }}</em>
        </span>
    </a>


    @php($hhMobileCurrentLocale = app()->getLocale())
    <div class="hh-mobile-profile-language" aria-label="{{ __('ui.language') }}">
        <i class="ph ph-translate" aria-hidden="true"></i>
        <span>
            <strong>{{ __('ui.language') }}</strong>
            <small>{{ __('ui.mobile_profile_language_text') }}</small>
        </span>
        <div class="hh-mobile-profile-language-actions">
            <a class="{{ $hhMobileCurrentLocale === 'de' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'de') }}">DE</a>
            <a class="{{ $hhMobileCurrentLocale === 'en' ? 'is-active' : '' }}" href="{{ route('locale.switch', 'en') }}">EN</a>
        </div>
    </div>

    <div class="hh-mobile-profile-links">
        <a href="{{ route('profile.edit') }}">
            <i class="ph ph-user" aria-hidden="true"></i>
            <span><strong>{{ __('ui.edit_profile') }}</strong><small>{{ __('ui.mobile_profile_edit_text') }}</small></span>
        </a>
        <a href="{{ $hhMobileDashboardUrl }}">
            <i class="ph ph-squares-four" aria-hidden="true"></i>
            <span><strong>{{ __('ui.mobile_profile_dashboard') }}</strong><small>{{ __('ui.mobile_profile_dashboard_text') }}</small></span>
        </a>
        <a href="{{ route('profile.show') }}">
            <i class="ph ph-identification-card" aria-hidden="true"></i>
            <span><strong>{{ __('ui.mobile_profile_public') }}</strong><small>{{ __('ui.mobile_profile_public_text') }}</small></span>
        </a>
        <a href="{{ route('messages.index') }}">
            <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
            <span><strong>{{ __('ui.messages') }}</strong><small>{{ __('ui.mobile_profile_messages_text') }}</small></span>
        </a>
        <a href="{{ route('notifications.index') }}">
            <i class="ph ph-bell" aria-hidden="true"></i>
            <span><strong>{{ __('ui.notifications') }}</strong><small>{{ __('ui.mobile_profile_notifications_text') }}</small></span>
        </a>
        <a href="{{ route('settings.privacy.edit') }}">
            <i class="ph ph-shield-check" aria-hidden="true"></i>
            <span><strong>{{ __('ui.privacy') }}</strong><small>{{ __('ui.mobile_profile_privacy_text') }}</small></span>
        </a>
        <a href="{{ route('settings.security.index') }}">
            <i class="ph ph-lock-key" aria-hidden="true"></i>
            <span><strong>{{ __('ui.security') }}</strong><small>{{ __('ui.mobile_profile_security_text') }}</small></span>
        </a>
    </div>

    <form class="hh-mobile-profile-logout" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"><i class="ph ph-sign-out" aria-hidden="true"></i><span>{{ __('ui.logout') }}</span></button>
    </form>
</section>
@endif
@endauth
