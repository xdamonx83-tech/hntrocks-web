@php
    $viewer = auth()->user();
    $viewer?->loadMissing('profile');

    $currentRoute = request()->route()?->getName() ?? '';
    $isActive = static function (array $patterns) use ($currentRoute): bool {
        foreach ($patterns as $pattern) {
            if (\Illuminate\Support\Str::is($pattern, $currentRoute)) {
                return true;
            }
        }

        return false;
    };

    $routeUrl = static function (string $name, string $fallback = '#'): string {
        return \Illuminate\Support\Facades\Route::has($name) ? route($name) : $fallback;
    };

    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $mobileAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $mobileName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');
    $mobileHandle = $viewer?->username ? '@'.$viewer->username : 'HNT.rocks';

    $mobileBottomCssVersion = @filemtime(public_path('assets/themes/rework/mobile-bottom-nav.css')) ?: time();
    $mobileBottomJsVersion = @filemtime(public_path('assets/themes/rework/mobile-bottom-nav.js')) ?: time();

    $menuLinks = [
        [
            'label' => 'Cups',
            'icon' => 'ph ph-trophy',
            'url' => $routeUrl('cups.index'),
        ],
        [
            'label' => 'Hall of Fame',
            'icon' => 'ph ph-medal',
            'url' => $routeUrl('hall-of-fame.index'),
        ],
        [
            'label' => 'Feedback',
            'icon' => 'ph ph-chat-centered-text',
            'url' => $routeUrl('cup-feedback.index', url('/cup-feedback')),
        ],
        [
            'label' => 'Cup Ideen',
            'icon' => 'ph ph-bookmark-simple',
            'url' => $routeUrl('cup-ideas.index'),
        ],
    ];
@endphp

<link href="{{ asset('assets/themes/rework/mobile-bottom-nav.css') }}?v={{ $mobileBottomCssVersion }}" rel="stylesheet">

<nav class="rework-mobile-bottom-nav" aria-label="Mobile navigation">
    <div class="rework-mobile-bottom-list">
        <a href="{{ $routeUrl('feed.index') }}" @class(['rework-mobile-bottom-item', 'is-active' => $isActive(['feed.*'])])>
            <i aria-hidden="true" class="ph ph-house ph-icon"></i>
            <span>Home</span>
        </a>

        <a href="{{ $routeUrl('search.index') }}" @class(['rework-mobile-bottom-item', 'is-active' => $isActive(['search.*'])])>
            <i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i>
            <span>Search</span>
        </a>

        <a href="{{ $routeUrl('moments.index') }}" @class(['rework-mobile-bottom-item', 'rework-mobile-bottom-center', 'is-active' => $isActive(['moments.*'])]) aria-label="Moments">
            <span class="rework-mobile-center-orb"><i aria-hidden="true" class="ph-bold ph-scan ph-icon"></i></span>
            <span class="sr-only">Moments</span>
        </a>

        <a href="{{ $routeUrl('lfg.index') }}" @class(['rework-mobile-bottom-item', 'is-active' => $isActive(['lfg.*'])])>
            <i aria-hidden="true" class="ph ph-clock ph-icon"></i>
            <span>LFG</span>
        </a>

        <button type="button" class="rework-mobile-bottom-item rework-mobile-menu-trigger" data-rework-mobile-menu-open aria-expanded="false" aria-controls="reworkMobileMenuSheet">
            <i aria-hidden="true" class="ph ph-list ph-icon"></i>
            <span>Menü</span>
        </button>
    </div>
</nav>

<div class="rework-mobile-menu-overlay" data-rework-mobile-menu-overlay hidden></div>

<section class="rework-mobile-menu-sheet" id="reworkMobileMenuSheet" data-rework-mobile-menu-sheet aria-hidden="true" aria-label="Mobile menu">
    <div class="rework-mobile-sheet-handle" aria-hidden="true"></div>
    <h2>Menu</h2>

    <div class="rework-mobile-sheet-line" aria-hidden="true"></div>

    <div class="rework-mobile-account-row">
        <div class="rework-mobile-account-tag">
            <img src="{{ $mobileAvatar }}" alt="{{ $mobileName }}" data-rework-profile-avatar>
            <div>
                <strong data-rework-profile-name>{{ $mobileName }}</strong>
                <span>{{ $mobileHandle }}</span>
            </div>
        </div>

        <a class="rework-mobile-profile-button" href="{{ $routeUrl('profile.show', $routeUrl('login')) }}">Profil</a>
    </div>

    <div class="rework-mobile-sheet-line" aria-hidden="true"></div>

    <div class="rework-mobile-quick-links">
        @foreach($menuLinks as $link)
            <a href="{{ $link['url'] }}">
                <span class="rework-mobile-quick-icon"><i aria-hidden="true" class="{{ $link['icon'] }} ph-icon"></i></span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="rework-mobile-sheet-line" aria-hidden="true"></div>

    <div class="rework-mobile-sheet-actions">
        <a href="{{ $routeUrl('profile.edit', $routeUrl('profile.show', '#')) }}" data-profile-edit-modal-open>
            <i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i>
            <span>Profil bearbeiten</span>
        </a>
        <a href="{{ $routeUrl('account.settings.edit', '#') }}" data-settings-modal-open>
            <i aria-hidden="true" class="ph ph-gear-six ph-icon"></i>
            <span>Einstellungen</span>
        </a>
    </div>

    <button type="button" class="rework-mobile-menu-close" data-rework-mobile-menu-close>Schließen</button>
</section>

<script defer src="{{ asset('assets/themes/rework/mobile-bottom-nav.js') }}?v={{ $mobileBottomJsVersion }}"></script>
