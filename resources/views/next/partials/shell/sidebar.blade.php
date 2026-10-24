<!-- HNT.ROCKS navigation: routes checked against HNT React menu and Laravel routes -->
<aside aria-label="{{ __('next_shell.item_037') }}" class="game-rail">
<a class="rail-brand icon-btn nav-link" href="{{ \App\Support\Hntv3Navigation::url('next.feed') }}" aria-label="{{ __('next_shell.item_038') }}" data-tooltip="{{ __('next_shell.item_038') }}" data-description="{{ __('next_shell.item_039') }}"><span class="hnt-rail-wordmark" aria-hidden="true">HR:<span class="hnt-rail-backslash">\</span></span></a>
<nav aria-label="{{ __('next_shell.item_040') }}" class="rail-games">
<a class="game-button icon-btn nav-link {{ ($nextActivePage ?? 'feed') === 'feed' ? 'is-active' : '' }}" href="{{ \App\Support\Hntv3Navigation::url('next.feed') }}" aria-label="Feed" data-tooltip="Feed" data-description="{{ __('next_shell.item_041') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-house"></use></svg></a>
@php
    $hntNewsNavLabel = match (app()->getLocale()) {
        'es' => 'Noticias',
        'ru' => 'Новости',
        default => 'News',
    };
    $hntNewsNavDescription = match (app()->getLocale()) {
        'de' => 'Aktuelle Meldungen und Artikel aus dem Bayou',
        'es' => 'Noticias y artículos del Bayou',
        'ru' => 'Новости и статьи из Байу',
        default => 'Latest news and articles from the Bayou',
    };
@endphp
<a class="game-button icon-btn nav-link {{ ($nextActivePage ?? '') === 'news' ? 'is-active' : '' }}"
   href="{{ route('news.overview.locale', ['locale' => in_array(app()->getLocale(), ['de','en','es','ru'], true) ? app()->getLocale() : 'de']) }}"
   aria-label="{{ $hntNewsNavLabel }}" data-tooltip="{{ $hntNewsNavLabel }}"
   data-description="{{ $hntNewsNavDescription }}"
   @if(($nextActivePage ?? '') === 'news') aria-current="page" @endif><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-file-text"></use></svg></a>
<a class="game-button icon-btn nav-link" href="https://hnt.rocks/teams" aria-label="Teams" data-tooltip="Teams" data-description="{{ __('next_shell.item_042') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg></a>
<a class="game-button icon-btn nav-link {{ ($nextActivePage ?? '') === 'cups' ? 'is-active' : '' }}" href="{{ \App\Support\Hntv3Navigation::url('next.cups') }}" aria-label="Cups" data-tooltip="Cups" data-description="{{ __('next_shell.item_043') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg></a>
<a class="game-button icon-btn nav-link {{ ($nextActivePage ?? '') === 'ready-lobbies' ? 'is-active' : '' }}" href="{{ ($nextActivePage ?? '') === 'ready-lobbies' ? route('next.ready.index') : url('/ready-lobbies') }}" aria-label="Ready Lobbies" data-tooltip="Ready Lobbies" data-description="{{ __('next_shell.item_044') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-four"></use></svg></a>
<a class="game-button icon-btn nav-link {{ in_array(($nextActivePage ?? ''), ['rocks','shop'], true) ? 'is-active' : '' }}" href="{{ \App\Support\Hntv3Navigation::url('next.rocks') }}" aria-label="Rocks" data-tooltip="Rocks" data-description="{{ __('next_shell.item_045') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-hexagon"></use></svg></a>
<button type="button" class="game-button add-game icon-btn more-toggle" id="moreMenuToggle" aria-expanded="false" aria-controls="moreMenu" aria-label="{{ __('next_shell.item_046') }}" title="{{ __('next_shell.item_046') }}" data-tooltip="{{ __('next_shell.item_046') }}" data-description="{{ __('next_shell.item_047') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-plus"></use></svg></button>
</nav>
<div class="rail-bottom">
<a class="rail-bottom-link icon-btn nav-link" href="{{ \App\Support\Hntv3Navigation::url('next.messages') }}" aria-label="{{ __('next_shell.item_010') }}" data-tooltip="{{ __('next_shell.item_010') }}" data-description="{{ __('next_shell.item_048') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-chat-circle-dots"></use></svg></a>
<a class="rail-bottom-link icon-btn nav-link" href="{{ route('settings.privacy.edit') }}" aria-label="{{ __('next_shell.item_049') }}" data-tooltip="{{ __('next_shell.item_049') }}" data-description="{{ __('next_shell.item_050') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-gear-six"></use></svg></a>
</div>
</aside>
<nav id="moreMenu" class="more-menu" aria-label="{{ __('next_shell.item_051') }}" hidden>
<div class="more-menu-heading">{{ __('next_shell.item_026') }}</div>
<a href="{{ \App\Support\Hntv3Navigation::url('next.members') }}" class="more-menu-item {{ ($nextActivePage ?? '') === 'members' ? 'is-active' : '' }}" title="{{ __('next_shell.item_027') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg><span class="more-menu-item-copy"><strong>{{ __('next_shell.item_027') }}</strong><small>{{ __('next_shell.item_028') }}</small></span></a>
<a href="{{ \App\Support\Hntv3Navigation::url('next.saved') }}" class="more-menu-item" title="{{ __('next_shell.item_029') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-bookmark-simple"></use></svg><span class="more-menu-item-copy"><strong>{{ __('next_shell.item_029') }}</strong><small>{{ __('next_shell.item_030') }}</small></span></a>
<a href="{{ route('hall-of-fame.index') }}" class="more-menu-item" title="Hall of Fame"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg><span class="more-menu-item-copy"><strong>Hall of Fame</strong><small>{{ __('next_shell.item_031') }}</small></span></a>
<a href="{{ route('loadout-generator.index') }}" class="more-menu-item {{ ($nextActivePage ?? '') === 'loadout-generator' ? 'is-active' : '' }}" title="Loadout Generator"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-dice-five"></use></svg><span class="more-menu-item-copy"><strong>Loadout Generator</strong><small>{{ __('next_loadout.sidebar_hint') }}</small></span></a>
<a href="https://hnt.rocks/loadout-challenges" class="more-menu-item" title="Loadout Challenges"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg><span class="more-menu-item-copy"><strong>Loadout Challenges</strong><small>{{ __('next_shell.item_032') }}</small></span></a>
<a href="{{ \App\Support\Hntv3Navigation::url('next.profile') }}" class="more-menu-item" title="{{ __('next_shell.item_035') }}"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-four"></use></svg><span class="more-menu-item-copy"><strong>{{ __('next_shell.item_035') }}</strong><small>{{ __('next_shell.item_036') }}</small></span></a>
</nav>
