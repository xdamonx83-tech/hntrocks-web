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
        return \Illuminate\Support\Facades\Route::has($name) ? route($name) : url($fallback);
    };

    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $mobileAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $mobileName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');
    $mobileHandle = $viewer?->username ? '@'.$viewer->username : 'HNT.rocks';

    $mobileBottomCssVersion = @filemtime(public_path('assets/themes/rework/mobile-bottom-nav.css')) ?: time();
    $mobileBottomSheetTuneVersion = @filemtime(public_path('assets/themes/rework/mobile-bottom-sheet-tune.css')) ?: time();
    $mobileBottomJsVersion = @filemtime(public_path('assets/themes/rework/mobile-bottom-nav.js')) ?: time();

    $menuSections = [
        [
            'title' => 'Community',
            'items' => [
                ['label' => 'Feed', 'icon' => 'ph ph-newspaper', 'url' => $routeUrl('feed.index', '/feed'), 'active' => ['feed.*']],
                ['label' => 'Mitglieder', 'icon' => 'ph ph-users', 'url' => $routeUrl('members.index', '/members'), 'active' => ['members.*']],
                ['label' => 'Moments', 'icon' => 'ph ph-play-circle', 'url' => $routeUrl('moments.index', '/moments'), 'active' => ['moments.*']],
                ['label' => 'Maps', 'icon' => 'ph ph-map-trifold', 'url' => $routeUrl('maps.index', '/maps'), 'active' => ['maps.*']],
                ['label' => 'Media', 'icon' => 'ph ph-images', 'url' => $routeUrl('media.index', '/media'), 'active' => ['media.*']],
            ],
        ],
        [
            'title' => 'Hunt & LFG',
            'items' => [
                ['label' => 'Hunt', 'icon' => 'ph ph-crosshair', 'url' => $routeUrl('loadout-challenges.index', '/loadout-challenges'), 'active' => ['loadout-challenges.*']],
                ['label' => 'Trophy Room', 'icon' => 'ph ph-cube-focus', 'url' => $routeUrl('trophy-room.index', '/trophy-room'), 'active' => ['trophy-room.*']],
                ['label' => 'LFG finden', 'icon' => 'ph ph-clock-countdown', 'url' => $routeUrl('lfg.index', '/lfg'), 'active' => ['lfg.index', 'lfg.show']],
                ['label' => 'LFG erstellen', 'icon' => 'ph ph-plus-circle', 'url' => $routeUrl('lfg.create', '/lfg/create'), 'active' => ['lfg.create']],
            ],
        ],
        [
            'title' => 'Cups',
            'items' => [
                ['label' => 'Cups', 'icon' => 'ph ph-trophy', 'url' => $routeUrl('cups.index', '/cups'), 'active' => ['cups.*']],
                ['label' => 'Hall of Fame', 'icon' => 'ph ph-medal-military', 'url' => $routeUrl('hall-of-fame.index', '/hall-of-fame'), 'active' => ['hall-of-fame.*']],
                ['label' => 'Feedback', 'icon' => 'ph ph-chat-centered-text', 'url' => $routeUrl('cup-feedback.create', '/cup-feedback'), 'active' => ['cup-feedback.*']],
                ['label' => 'Cup Ideen', 'icon' => 'ph ph-bookmark-simple', 'url' => $routeUrl('cup-ideas.index', '/cup-ideas'), 'active' => ['cup-ideas.*']],
            ],
        ],
        [
            'title' => 'Bounty Marks',
            'items' => [
                ['label' => 'Badges', 'icon' => 'ph ph-medal', 'url' => $routeUrl('gamification.index', '/gamification'), 'active' => ['gamification.*']],
                ['label' => 'Bounty Marks', 'icon' => 'ph ph-coins', 'url' => $routeUrl('crowns.index', '/crowns'), 'active' => ['crowns.index']],
                ['label' => 'Inventar', 'icon' => 'ph ph-package', 'url' => $routeUrl('crowns.inventory', '/crowns/inventory'), 'active' => ['crowns.inventory']],
                ['label' => 'Shop', 'icon' => 'ph ph-storefront', 'url' => $routeUrl('crowns.shop', '/crowns/shop'), 'active' => ['crowns.shop']],
                ['label' => 'Verlauf', 'icon' => 'ph ph-clock-counter-clockwise', 'url' => $routeUrl('crowns.history', '/crowns/history'), 'active' => ['crowns.history']],
            ],
        ],
    ];
@endphp

<link href="{{ asset('assets/themes/rework/mobile-bottom-nav.css') }}?v={{ $mobileBottomCssVersion }}" rel="stylesheet">
<link href="{{ asset('assets/themes/rework/mobile-bottom-sheet-tune.css') }}?v={{ $mobileBottomSheetTuneVersion }}" rel="stylesheet">

<nav class="rework-mobile-bottom-nav" aria-label="Mobile navigation">
    <div class="rework-mobile-bottom-list">
        <a href="{{ $routeUrl('feed.index', '/feed') }}" @class(['rework-mobile-bottom-item', 'is-active' => $isActive(['feed.*'])])>
            <i aria-hidden="true" class="ph ph-house ph-icon"></i>
            <span>Home</span>
        </a>

        <a href="{{ $routeUrl('search.index', '/search') }}" @class(['rework-mobile-bottom-item', 'is-active' => $isActive(['search.*'])])>
            <i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i>
            <span>Search</span>
        </a>

        <a href="{{ $routeUrl('moments.index', '/moments') }}" @class(['rework-mobile-bottom-item', 'rework-mobile-bottom-center', 'is-active' => $isActive(['moments.*'])]) aria-label="Moments">
            <span class="rework-mobile-center-orb"><i aria-hidden="true" class="ph-bold ph-play-circle ph-icon"></i></span>
            <span class="sr-only">Moments</span>
        </a>

        <a href="{{ $routeUrl('lfg.index', '/lfg') }}" @class(['rework-mobile-bottom-item', 'is-active' => $isActive(['lfg.*'])])>
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

        <a class="rework-mobile-profile-button" href="{{ $routeUrl('profile.show', '/profile') }}">Profil</a>
    </div>

    <div class="rework-mobile-sheet-line" aria-hidden="true"></div>

    <div class="rework-mobile-menu-sections">
        @foreach($menuSections as $section)
            <div class="rework-mobile-menu-section">
                <h3>{{ $section['title'] }}</h3>
                <div class="rework-mobile-quick-links">
                    @foreach($section['items'] as $link)
                        <a href="{{ $link['url'] }}" @class(['is-active' => $isActive($link['active'] ?? [])])>
                            <span class="rework-mobile-quick-icon"><i aria-hidden="true" class="{{ $link['icon'] }} ph-icon"></i></span>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="rework-mobile-sheet-line" aria-hidden="true"></div>

    <div class="rework-mobile-sheet-actions">
        <a href="{{ $routeUrl('profile.edit', '/profile/edit') }}" data-profile-edit-modal-open>
            <i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i>
            <span>Profil bearbeiten</span>
        </a>
        <a href="{{ $routeUrl('account.settings.edit', '/account/settings') }}" data-settings-modal-open>
            <i aria-hidden="true" class="ph ph-gear-six ph-icon"></i>
            <span>Einstellungen</span>
        </a>
    </div>

    <button type="button" class="rework-mobile-menu-close" data-rework-mobile-menu-close>Schließen</button>
</section>

<script defer src="{{ asset('assets/themes/rework/mobile-bottom-nav.js') }}?v={{ $mobileBottomJsVersion }}"></script>
