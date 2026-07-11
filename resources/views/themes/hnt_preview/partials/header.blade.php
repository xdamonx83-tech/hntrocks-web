@php
    $headerViewer = auth()->user();
    $headerName = $headerViewer?->name ?: ($headerViewer?->username ?: 'HNT Hunter');
    $headerHandle = $headerViewer?->username ? '@'.$headerViewer->username : '@hunter';
    $headerAvatar = $headerViewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $headerLevel = max(1, (int) ($headerViewer?->level ?? 1));
    $headerRocks = (int) ($headerViewer?->crownWallet?->balance ?? 0);
@endphp
<header class="site-header">
<a aria-label="HNT.rocks Feed" class="brand" href="{{ route('feed.index') }}">
<svg aria-hidden="true" class="brand-mark" viewbox="0 0 44 34">
<path d="M8.2 5.5c4.4-4.4 10.8-4.2 14.5.1-1 4.7-4.2 8-8.8 9.3-3.9-1.4-6.2-4.7-5.7-9.4Z"></path>
<path d="M22.1 8.1c5.9-.2 10.2 4 10.4 9.2-3.4 3.2-8 4-12.3 2.1-2-3.8-1.3-8.2 1.9-11.3Z"></path>
<path d="M14.3 18.3c3.5-3.5 8.5-3.8 12.2-.9.4 4.7-1.8 8.5-6.1 10.5-4-.7-6.6-4.1-6.1-9.6Z"></path>
<circle cx="18.8" cy="15.5" r="3.4"></circle>
</svg>
<span>HNT.ROCKS</span>
</a>
<nav aria-label="HNT.ROCKS Hauptnavigation" class="main-nav hnt-main-nav">
<a data-page="feed" href="{{ route('feed.index') }}">Feed</a>
<div class="main-nav-item nav-community">
<button aria-controls="communityNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>Community</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown align-left" id="communityNavDropdown" role="menu">
<header><span>COMMUNITY</span><strong>Gemeinsam im Bayou</strong></header>
<div class="main-nav-menu-grid">
<a href="{{ route('members.index') }}" role="menuitem"><span class="main-nav-menu-icon"><svg><use href="#i-users"></use></svg></span><span><strong>Mitglieder</strong><small>Hunter entdecken und folgen</small></span></a>
<a href="{{ auth()->check() ? route('profile.friends') : route('login') }}" role="menuitem"><span class="main-nav-menu-icon"><svg><use href="#i-user"></use></svg></span><span><strong>Freunde</strong><small>Freundesliste und Anfragen</small></span></a>
<a href="{{ route('feed.index') }}#community-hashtags" role="menuitem"><span class="main-nav-menu-icon"><svg><use href="#i-comment"></use></svg></span><span><strong>Hashtags</strong><small>Trends und Community-Themen</small></span></a>
</div>
</section>
</div>
<div class="main-nav-item nav-lfg">
<button aria-controls="lfgNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>LFG</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown" id="lfgNavDropdown" role="menu">
<header><span>LOOKING FOR GROUP</span><strong>Finde dein Team</strong></header>
<div class="main-nav-menu-grid">
<a href="{{ route('lfg.index') }}" role="menuitem"><span class="main-nav-menu-icon"><svg><use href="#i-search"></use></svg></span><span><strong>LFG finden</strong><small>Offene Gruppensuchen durchsuchen</small></span></a>
<a href="{{ route('lfg.create') }}" role="menuitem"><span class="main-nav-menu-icon yellow"><svg><use href="#i-plus"></use></svg></span><span><strong>LFG erstellen</strong><small>Eigene Suche veröffentlichen</small></span></a>
<button aria-disabled="true" data-unavailable="1" role="menuitem"><span class="main-nav-menu-icon green"><svg><use href="#i-users"></use></svg></span><span><strong>Ready Lobbys</strong><small>Für die Web-Roadmap geplant</small></span><em>Bald</em></button>
</div>
</section>
</div>
<a class="main-nav-trigger main-nav-direct" href="{{ route('moments.index') }}"><span>Moments</span></a>
<div class="main-nav-item nav-cups">
<button aria-controls="cupsNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>Cups</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown" id="cupsNavDropdown" role="menu">
<header><span>COMMUNITY CUPS</span><strong>Wettbewerbe &amp; Teams</strong></header>
<div class="main-nav-menu-grid">
<a href="{{ route('cups.index') }}" role="menuitem"><span class="main-nav-menu-icon yellow"><svg><use href="#i-folder"></use></svg></span><span><strong>Cup-Übersicht</strong><small>Aktive und vergangene Cups</small></span></a>
<a href="{{ route('hall-of-fame.index') }}" role="menuitem"><span class="main-nav-menu-icon green"><svg><use href="#i-check"></use></svg></span><span><strong>Hall of Fame</strong><small>Sieger und Platzierungen</small></span></a>
<a href="{{ route('teams.index') }}" role="menuitem"><span class="main-nav-menu-icon"><svg><use href="#i-users"></use></svg></span><span><strong>Teams</strong><small>Community- und Cup-Teams</small></span></a>
</div>
</section>
</div>
<a class="main-nav-trigger main-nav-direct" href="{{ route('teams.index') }}"><span>Teams</span></a>
<div class="main-nav-item nav-contracts">
<button aria-controls="contractsNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button"><span>Aufträge</span><svg><use href="#i-chevron"></use></svg></button>
<section class="main-nav-dropdown" id="contractsNavDropdown" role="menu">
<header><span>FORTSCHRITT</span><strong>Aufträge &amp; Belohnungen</strong></header>
<div class="main-nav-menu-grid">
<a href="{{ route('gamification.index') }}" role="menuitem"><span class="main-nav-menu-icon green"><svg><use href="#i-sliders"></use></svg></span><span><strong>Gamification</strong><small>Level, XP, Badges und Quests</small></span></a>
<a href="{{ route('contracts.index') }}" role="menuitem"><span class="main-nav-menu-icon yellow"><svg><use href="#i-check"></use></svg></span><span><strong>Wochenaufträge</strong><small>Fortschritt und Belohnungen</small></span></a>
<a href="{{ route('crowns.shop') }}" role="menuitem"><span class="main-nav-menu-icon"><svg><use href="#i-folder"></use></svg></span><span><strong>Shop &amp; Inventar</strong><small>Rocks, Rahmen und Items</small></span></a>
</div>
</section>
</div>
</nav>
<div class="header-actions">
<a aria-label="Feed öffnen" class="mobile-stats-trigger profile-mobile-feed-link" href="{{ route('feed.index') }}"><svg><use href="#i-arrow"></use></svg><span>Feed</span></a>
@if($headerViewer)
<div class="header-action-menu settings-menu">
<button aria-controls="settingsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Einstellungen öffnen" class="settings-button header-dropdown-trigger" id="settingsMenuTrigger"><svg><use href="#i-settings"></use></svg><span>Einstellungen</span></button>
<section aria-labelledby="settingsMenuTrigger" class="header-dropdown settings-dropdown" id="settingsDropdown" role="menu">
<header class="header-dropdown-head"><div><span>HNT.ROCKS</span><strong>Einstellungen</strong></div><small>Persönlich</small></header>
<div class="header-menu-list">
<a href="{{ route('account.settings.edit') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-settings"></use></svg></span><span><strong>Allgemein</strong><small>Sprache, Darstellung und Konto</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
<a href="{{ route('settings.privacy.edit') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-eye"></use></svg></span><span><strong>Privatsphäre</strong><small>Sichtbarkeit und Interaktionen</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
</div>
</section>
</div>
<div class="header-action-menu friends-menu">
<button aria-controls="friendsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Freundschaftsanfragen öffnen" class="header-circle header-dropdown-trigger" id="friendsMenuTrigger"><svg><use href="#i-users"></use></svg><span class="header-action-badge is-empty" data-header-badge="friends">0</span></button>
<section aria-labelledby="friendsMenuTrigger" class="header-dropdown requests-dropdown" id="friendsDropdown" role="menu"><header class="header-dropdown-head"><div><span>COMMUNITY</span><strong>Freundschaftsanfragen</strong></div><small data-dropdown-count="friends">0 offen</small></header><div class="header-request-list"><div class="header-live-state"><strong>Freundschaftsanfragen</strong>Echte Daten werden geladen …</div></div><a class="header-dropdown-footer" href="{{ route('profile.friends') }}">Alle Anfragen ansehen <svg><use href="#i-arrow"></use></svg></a></section>
</div>
<div class="header-action-menu messages-menu">
<button aria-controls="messagesDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Nachrichten öffnen" class="header-circle header-dropdown-trigger" id="messagesMenuTrigger"><svg><use href="#i-comment"></use></svg><span class="header-action-badge is-empty" data-header-badge="messages">0</span></button>
<section aria-labelledby="messagesMenuTrigger" class="header-dropdown messages-dropdown" id="messagesDropdown" role="menu"><header class="header-dropdown-head"><div><span>INBOX</span><strong>Nachrichten</strong></div><small data-dropdown-count="messages">0 ungelesen</small></header><div class="header-message-list"><div class="header-live-state"><strong>Nachrichten</strong>Echte Daten werden geladen …</div></div><a class="header-dropdown-footer" href="{{ route('messages.index') }}">Alle Nachrichten ansehen <svg><use href="#i-arrow"></use></svg></a></section>
</div>
<div class="header-action-menu notifications-menu">
<button aria-controls="notificationsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Benachrichtigungen öffnen" class="header-circle header-dropdown-trigger" id="notificationsMenuTrigger"><svg><use href="#i-bell"></use></svg><span class="header-action-badge is-empty" data-header-badge="notifications">0</span></button>
<section aria-labelledby="notificationsMenuTrigger" class="header-dropdown notifications-dropdown" id="notificationsDropdown" role="menu"><header class="header-dropdown-head"><div><span>AKTIVITÄT</span><strong>Benachrichtigungen</strong></div><small data-dropdown-count="notifications">0 ungelesen</small></header><div class="header-notification-list"><div class="header-live-state"><strong>Benachrichtigungen</strong>Echte Daten werden geladen …</div></div><a class="header-dropdown-footer" href="{{ route('notifications.index') }}">Alle Benachrichtigungen <svg><use href="#i-arrow"></use></svg></a></section>
</div>
<div class="header-action-menu profile-menu">
<button aria-controls="profileDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Profil öffnen" class="header-circle header-dropdown-trigger" id="profileMenuTrigger"><svg><use href="#i-user"></use></svg></button>
<section aria-labelledby="profileMenuTrigger" class="header-dropdown profile-dropdown" id="profileDropdown" role="menu">
<div class="header-profile-summary"><div class="header-profile-avatar"><img alt="{{ $headerName }}" src="{{ $headerAvatar }}"/><i></i></div><div><strong>{{ $headerName }}</strong><span>{{ $headerHandle }} · Level {{ $headerLevel }}</span></div></div>
<div class="header-profile-stats"><span><strong>{{ number_format($headerRocks, 0, ',', '.') }}</strong><small>Rocks</small></span><span><strong>—</strong><small>Freunde</small></span><span><strong>—</strong><small>Posts</small></span></div>
<div class="header-menu-list profile-menu-list">
<a href="{{ route('profile.show') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-user"></use></svg></span><span><strong>Mein Profil</strong><small>Profil und öffentliche Ansicht</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
<a href="{{ route('profile.edit') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-sliders"></use></svg></span><span><strong>Profil bearbeiten</strong><small>Infos, Medien und Privatsphäre</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
<a href="{{ route('crowns.inventory') }}" role="menuitem"><span class="header-menu-icon"><svg><use href="#i-folder"></use></svg></span><span><strong>Inventar</strong><small>Rahmen, Badges und Items</small></span><svg class="header-menu-arrow"><use href="#i-arrow"></use></svg></a>
</div>
<form action="{{ route('logout') }}" method="post">@csrf<button class="header-profile-logout" type="submit">Abmelden</button></form>
</section>
</div>
@else
<a class="settings-button" href="{{ route('login') }}">Anmelden</a>
@endif
</div>
</header>