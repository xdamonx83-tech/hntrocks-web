@php
    abort_unless(
        auth()->check() && \App\Support\HntTheme::previewActive(auth()->user()),
        404
    );
@endphp
<!DOCTYPE html>

<html lang="de">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1" name="viewport"/>
<meta content="noindex,nofollow,noarchive" name="robots"/>
<title>HNT.rocks — Feed Preview</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v=20260710-1" rel="stylesheet"/>
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v=20260710-1" rel="stylesheet"/>
</head>
<body data-page="feed">
<svg aria-hidden="true" class="svg-defs">
<symbol id="i-search" viewbox="0 0 24 24"><circle cx="11" cy="11" r="6.8"></circle><path d="m16.2 16.2 4 4"></path></symbol>
<symbol id="i-plus" viewbox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></symbol>
<symbol id="i-sliders" viewbox="0 0 24 24"><path d="M4 7h9M17 7h3M4 17h3M11 17h9M13 4v6M8 14v6"></path></symbol>
<symbol id="i-export" viewbox="0 0 24 24"><path d="M12 3v11M8 7l4-4 4 4"></path><path d="M5 13v6h14v-6"></path></symbol>
<symbol id="i-settings" viewbox="0 0 24 24"><circle cx="12" cy="12" r="3.1"></circle><path d="M19 13.6v-3.2l-2-.7-.7-1.7.9-1.9-2.3-2.3-1.9.9-1.7-.7-.7-2H8.4l-.7 2-1.7.7-1.9-.9-2.3 2.3.9 1.9-.7 1.7-2 .7v3.2l2 .7.7 1.7-.9 1.9 2.3 2.3 1.9-.9 1.7.7.7 2h3.2l.7-2 1.7-.7 1.9.9 2.3-2.3-.9-1.9.7-1.7z"></path></symbol>
<symbol id="i-bell" viewbox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 6 3 8H3c0-2 3-1 3-8"></path><path d="M9.5 20h5"></path></symbol>
<symbol id="i-user" viewbox="0 0 24 24"><circle cx="12" cy="8" r="3.4"></circle><path d="M5.5 20a6.5 6.5 0 0 1 13 0"></path></symbol>
<symbol id="i-arrow" viewbox="0 0 24 24"><path d="M7 17 17 7M9 7h8v8"></path></symbol>
<symbol id="i-briefcase" viewbox="0 0 24 24"><rect height="12" rx="3" width="18" x="3" y="7"></rect><path d="M9 7V5h6v2M3 12h18M10 12v2h4v-2"></path></symbol>
<symbol id="i-phone" viewbox="0 0 24 24"><path d="M7.4 3.5 4.6 5.2c-.8.5-.9 1.5-.6 2.4 2.1 6.1 6.3 10.3 12.4 12.4.9.3 1.9.2 2.4-.6l1.7-2.8-4-3-1.8 2.1c-2.6-1-5.4-3.8-6.4-6.4l2.1-1.8z"></path></symbol>
<symbol id="i-chevron" viewbox="0 0 24 24"><path d="m8 10 4 4 4-4"></path></symbol>
<symbol id="i-printer" viewbox="0 0 24 24"><path d="M7 9V4h10v5M7 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M7 14h10v7H7z"></path></symbol>
<symbol id="i-users" viewbox="0 0 24 24"><circle cx="9" cy="8.5" r="3"></circle><circle cx="17" cy="9.5" r="2.3"></circle><path d="M3 19a6 6 0 0 1 12 0M14 18a4.5 4.5 0 0 1 7 0"></path></symbol>
<symbol id="i-folder" viewbox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3z"></path><path d="M3 7V5h7l2 2"></path></symbol>
<symbol id="i-check" viewbox="0 0 24 24"><path d="m6 12 4 4 8-8"></path></symbol>
<symbol id="i-male" viewbox="0 0 24 24"><circle cx="10" cy="14" r="5"></circle><path d="m14 10 6-6M15 4h5v5"></path></symbol>
<symbol id="i-female" viewbox="0 0 24 24"><circle cx="12" cy="9" r="5"></circle><path d="M12 14v7M9 18h6"></path></symbol>
<symbol id="i-heart" viewbox="0 0 24 24"><path d="M20.8 4.9a5.5 5.5 0 0 0-7.8 0L12 5.9l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.3 1-1a5.5 5.5 0 0 0 0-7.8Z"></path></symbol>
<symbol id="i-comment" viewbox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a8 8 0 1 1 18-5Z"></path></symbol>
<symbol id="i-bookmark" viewbox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"></path></symbol>
<symbol id="i-share" viewbox="0 0 24 24"><circle cx="18" cy="5" r="2.5"></circle><circle cx="6" cy="12" r="2.5"></circle><circle cx="18" cy="19" r="2.5"></circle><path d="m8.2 10.8 7.6-4.5M8.2 13.2l7.6 4.5"></path></symbol>
<symbol id="i-more" viewbox="0 0 24 24"><circle cx="5" cy="12" r="1.2"></circle><circle cx="12" cy="12" r="1.2"></circle><circle cx="19" cy="12" r="1.2"></circle></symbol>
<symbol id="i-image" viewbox="0 0 24 24"><rect height="16" rx="3" width="18" x="3" y="4"></rect><circle cx="9" cy="10" r="2"></circle><path d="m5 18 5-5 3 3 2-2 4 4"></path></symbol>
<symbol id="i-x" viewbox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"></path></symbol>
<symbol id="i-send" viewbox="0 0 24 24"><path d="m3 11 18-8-7 18-3-7z"></path><path d="m11 14 4-4"></path></symbol>
<symbol id="i-smile" viewbox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 14.5a4.5 4.5 0 0 0 7 0M9 9h.01M15 9h.01"></path></symbol>
<symbol id="i-reply" viewbox="0 0 24 24"><path d="m9 7-6 5 6 5v-3h4c4 0 6 2 8 5-.5-6-3-9-8-9H9z"></path></symbol>
</svg>
<main class="app-shell feed-shell">
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
<button aria-controls="communityNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button">
<span>Community</span>
<svg><use href="#i-chevron"></use></svg>
</button>
<section class="main-nav-dropdown align-left" id="communityNavDropdown" role="menu">
<header>
<span>COMMUNITY</span>
<strong>Gemeinsam im Bayou</strong>
</header>
<div class="main-nav-menu-grid">
<a href="{{ route('members.index') }}" role="menuitem">
<span class="main-nav-menu-icon"><svg><use href="#i-users"></use></svg></span>
<span><strong>Mitglieder</strong><small>Hunter entdecken und folgen</small></span>
</a>
<button data-toast="Freunde geöffnet" role="menuitem">
<span class="main-nav-menu-icon"><svg><use href="#i-user"></use></svg></span>
<span><strong>Freunde</strong><small>Freundesliste und Anfragen</small></span>
</button>
<button data-toast="Hashtags geöffnet" role="menuitem">
<span class="main-nav-menu-icon"><svg><use href="#i-comment"></use></svg></span>
<span><strong>Hashtags</strong><small>Trends und Community-Themen</small></span>
</button>
</div>
</section>
</div>
<div class="main-nav-item nav-lfg">
<button aria-controls="lfgNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button">
<span>LFG</span>
<svg><use href="#i-chevron"></use></svg>
</button>
<section class="main-nav-dropdown" id="lfgNavDropdown" role="menu">
<header>
<span>LOOKING FOR GROUP</span>
<strong>Finde dein Team</strong>
</header>
<div class="main-nav-menu-grid">
<button data-toast="LFG Übersicht geöffnet" role="menuitem">
<span class="main-nav-menu-icon"><svg><use href="#i-search"></use></svg></span>
<span><strong>LFG finden</strong><small>Offene Gruppensuchen durchsuchen</small></span>
</button>
<button data-toast="LFG erstellen geöffnet" role="menuitem">
<span class="main-nav-menu-icon yellow"><svg><use href="#i-plus"></use></svg></span>
<span><strong>LFG erstellen</strong><small>Eigene Suche veröffentlichen</small></span>
</button>
<button data-toast="Ready Lobbys sind für die Web-Roadmap geplant" role="menuitem">
<span class="main-nav-menu-icon green"><svg><use href="#i-users"></use></svg></span>
<span><strong>Ready Lobbys</strong><small>Sofort spielbereite Hunter finden</small></span>
<em>Bald</em>
</button>
</div>
</section>
</div>
<button class="main-nav-trigger main-nav-direct" data-toast="Moments geöffnet" type="button">
<span>Moments</span>
</button>
<div class="main-nav-item nav-cups">
<button aria-controls="cupsNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button">
<span>Cups</span>
<svg><use href="#i-chevron"></use></svg>
</button>
<section class="main-nav-dropdown" id="cupsNavDropdown" role="menu">
<header>
<span>COMMUNITY CUPS</span>
<strong>Wettbewerbe &amp; Teams</strong>
</header>
<div class="main-nav-menu-grid">
<button data-toast="Aktive Cups geöffnet" role="menuitem">
<span class="main-nav-menu-icon yellow"><svg><use href="#i-folder"></use></svg></span>
<span><strong>Aktive Cups</strong><small>Laufende und kommende Events</small></span>
</button>
<button data-toast="Cup Teams geöffnet" role="menuitem">
<span class="main-nav-menu-icon"><svg><use href="#i-users"></use></svg></span>
<span><strong>Meine Cup-Teams</strong><small>Teams, Einladungen und Chat</small></span>
</button>
<button data-toast="Einreichungen geöffnet" role="menuitem">
<span class="main-nav-menu-icon purple"><svg><use href="#i-image"></use></svg></span>
<span><strong>Einreichungen</strong><small>Screenshots und Prüfstatus</small></span>
</button>
<button data-toast="Hall of Fame geöffnet" role="menuitem">
<span class="main-nav-menu-icon green"><svg><use href="#i-check"></use></svg></span>
<span><strong>Hall of Fame</strong><small>Sieger und vergangene Cups</small></span>
</button>
</div>
</section>
</div>
<button class="main-nav-trigger main-nav-direct" data-toast="Teams geöffnet" type="button">
<span>Teams</span>
</button>
<div class="main-nav-item nav-contracts">
<button aria-controls="contractsNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button">
<span>Aufträge</span>
<svg><use href="#i-chevron"></use></svg>
</button>
<section class="main-nav-dropdown" id="contractsNavDropdown" role="menu">
<header>
<span>FORTSCHRITT</span>
<strong>Aufträge &amp; Belohnungen</strong>
</header>
<div class="main-nav-menu-grid">
<button data-toast="Wochenaufträge geöffnet" role="menuitem">
<span class="main-nav-menu-icon yellow"><svg><use href="#i-check"></use></svg></span>
<span><strong>Wochenaufträge</strong><small>Fortschritt und Rocks-Belohnungen</small></span>
</button>
<button data-toast="Quests geöffnet" role="menuitem">
<span class="main-nav-menu-icon"><svg><use href="#i-sliders"></use></svg></span>
<span><strong>Quests</strong><small>Tägliche und dauerhafte Aufgaben</small></span>
</button>
<button data-toast="Loadout Challenges geöffnet" role="menuitem">
<span class="main-nav-menu-icon purple"><svg><use href="#i-briefcase"></use></svg></span>
<span><strong>Loadout Challenges</strong><small>Builds testen und einreichen</small></span>
</button>
<button data-toast="Badges geöffnet" role="menuitem">
<span class="main-nav-menu-icon green"><svg><use href="#i-bookmark"></use></svg></span>
<span><strong>Badges</strong><small>Erfolge und Auszeichnungen</small></span>
</button>
</div>
</section>
</div>
<div class="main-nav-item nav-more">
<button aria-controls="moreNavDropdown" aria-expanded="false" aria-haspopup="true" class="main-nav-trigger" type="button">
<span>Mehr</span>
<svg><use href="#i-chevron"></use></svg>
</button>
<section class="main-nav-dropdown align-right" id="moreNavDropdown" role="menu">
<header>
<span>MEHR ENTDECKEN</span>
<strong>Weitere HNT.ROCKS-Bereiche</strong>
</header>
<div class="main-nav-menu-grid">
<button data-toast="Shop geöffnet" role="menuitem">
<span class="main-nav-menu-icon yellow"><svg><use href="#i-folder"></use></svg></span>
<span><strong>Shop &amp; Inventar</strong><small>Rocks einlösen und Items verwalten</small></span>
</button>
<button data-toast="Guides geöffnet" role="menuitem">
<span class="main-nav-menu-icon"><svg><use href="#i-bookmark"></use></svg></span>
<span><strong>Guides</strong><small>Community-Wissen und Tipps</small></span>
</button>
<button data-toast="Umfragen geöffnet" role="menuitem">
<span class="main-nav-menu-icon purple"><svg><use href="#i-comment"></use></svg></span>
<span><strong>Umfragen</strong><small>Abstimmen und diskutieren</small></span>
</button>
<button data-toast="Verlauf geöffnet" role="menuitem">
<span class="main-nav-menu-icon green"><svg><use href="#i-arrow"></use></svg></span>
<span><strong>Aktivitätsverlauf</strong><small>XP, Rocks und letzte Aktionen</small></span>
</button>
</div>
</section>
</div>
</nav>
<div class="header-actions">
<button aria-label="Stats öffnen" class="mobile-stats-trigger" id="mobileStatsTrigger" type="button">
<svg><use href="#i-sliders"></use></svg><span>Stats</span>
</button>
<div class="header-action-menu settings-menu">
<button aria-controls="settingsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Einstellungen öffnen" class="settings-button header-dropdown-trigger" id="settingsMenuTrigger">
<svg><use href="#i-settings"></use></svg><span>Setting</span>
</button>
<section aria-labelledby="settingsMenuTrigger" class="header-dropdown settings-dropdown" id="settingsDropdown" role="menu">
<header class="header-dropdown-head">
<div><span>HNT.ROCKS</span><strong>Einstellungen</strong></div>
<small>Persönlich</small>
</header>
<div class="header-menu-list">
<button data-toast="Allgemeine Einstellungen geöffnet" role="menuitem">
<span class="header-menu-icon"><svg><use href="#i-settings"></use></svg></span>
<span><strong>Allgemein</strong><small>Sprache, Darstellung und Konto</small></span>
<svg class="header-menu-arrow"><use href="#i-arrow"></use></svg>
</button>
<button data-toast="Privatsphäre geöffnet" role="menuitem">
<span class="header-menu-icon"><svg><use href="#i-user"></use></svg></span>
<span><strong>Privatsphäre</strong><small>Sichtbarkeit und Interaktionen</small></span>
<svg class="header-menu-arrow"><use href="#i-arrow"></use></svg>
</button>
<button data-toast="Benachrichtigungseinstellungen geöffnet" role="menuitem">
<span class="header-menu-icon"><svg><use href="#i-bell"></use></svg></span>
<span><strong>Benachrichtigungen</strong><small>Push, E-Mail und Hinweise</small></span>
<svg class="header-menu-arrow"><use href="#i-arrow"></use></svg>
</button>
<button data-toast="Sprache auf Deutsch" role="menuitem">
<span class="header-menu-icon"><svg><use href="#i-comment"></use></svg></span>
<span><strong>Sprache</strong><small>Deutsch</small></span>
<span class="header-menu-value">DE</span>
</button>
</div>
</section>
</div>
<div class="header-action-menu friends-menu">
<button aria-controls="friendsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Freundschaftsanfragen öffnen" class="header-circle header-dropdown-trigger" id="friendsMenuTrigger">
<svg><use href="#i-users"></use></svg>
<span class="header-action-badge" data-header-badge="friends">2</span>
</button>
<section aria-labelledby="friendsMenuTrigger" class="header-dropdown requests-dropdown" id="friendsDropdown" role="menu">
<header class="header-dropdown-head">
<div><span>COMMUNITY</span><strong>Freundschaftsanfragen</strong></div>
<small data-dropdown-count="friends">2 offen</small>
</header>
<div class="header-request-list">
<article class="header-request-item">
<img alt="Sarah Page" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg') }}"/>
<div><strong>Sarah Page</strong><small>@sarah · 12 gemeinsame Freunde</small></div>
<div class="header-request-actions">
<button aria-label="Sarah annehmen" class="friend-accept"><svg><use href="#i-check"></use></svg></button>
<button aria-label="Sarah ablehnen" class="friend-decline"><svg><use href="#i-x"></use></svg></button>
</div>
</article>
<article class="header-request-item">
<img alt="Noah Brandt" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg') }}"/>
<div><strong>Noah Brandt</strong><small>@noah · Hunt: Showdown</small></div>
<div class="header-request-actions">
<button aria-label="Noah annehmen" class="friend-accept"><svg><use href="#i-check"></use></svg></button>
<button aria-label="Noah ablehnen" class="friend-decline"><svg><use href="#i-x"></use></svg></button>
</div>
</article>
</div>
<button class="header-dropdown-footer" data-toast="Alle Freundschaftsanfragen geöffnet">
          Alle Anfragen ansehen
          <svg><use href="#i-arrow"></use></svg>
</button>
</section>
</div>
<div class="header-action-menu messages-menu">
<button aria-controls="messagesDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Nachrichten öffnen" class="header-circle header-dropdown-trigger" id="messagesMenuTrigger">
<svg><use href="#i-comment"></use></svg>
<span class="header-action-badge" data-header-badge="messages">3</span>
</button>
<section aria-labelledby="messagesMenuTrigger" class="header-dropdown messages-dropdown" id="messagesDropdown" role="menu">
<header class="header-dropdown-head">
<div><span>INBOX</span><strong>Nachrichten</strong></div>
<small data-dropdown-count="messages">3 ungelesen</small>
</header>
<div class="header-message-list">
<button class="header-message-item unread" role="menuitem">
<img alt="Katy Fuller" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg') }}"/>
<span><strong>Katy Fuller</strong><small>Wir wären ab 20:30 Uhr bereit.</small></span>
<time>jetzt</time>
</button>
<button class="header-message-item unread" role="menuitem">
<img alt="Jonathan Kelly" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg') }}"/>
<span><strong>Jonathan Kelly</strong><small>Der Clip ist im Teamchat.</small></span>
<time>4m</time>
</button>
<button class="header-message-item unread" role="menuitem">
<img alt="Erica Wyatt" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg') }}"/>
<span><strong>Erica Wyatt</strong><small>Cup-Einreichung wurde geprüft.</small></span>
<time>18m</time>
</button>
</div>
<button class="header-dropdown-footer" data-toast="Alle Nachrichten geöffnet">
          Alle Nachrichten ansehen
          <svg><use href="#i-arrow"></use></svg>
</button>
</section>
</div>
<div class="header-action-menu notifications-menu">
<button aria-controls="notificationsDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Benachrichtigungen öffnen" class="header-circle header-dropdown-trigger" id="notificationsMenuTrigger">
<svg><use href="#i-bell"></use></svg>
<span class="header-action-badge" data-header-badge="notifications">5</span>
</button>
<section aria-labelledby="notificationsMenuTrigger" class="header-dropdown notifications-dropdown" id="notificationsDropdown" role="menu">
<header class="header-dropdown-head">
<div><span>AKTUELL</span><strong>Benachrichtigungen</strong></div>
<button class="header-mark-all" type="button">Alle gelesen</button>
</header>
<div class="header-notification-list">
<button class="header-notification-item unread" role="menuitem">
<span class="header-notification-icon yellow"><svg><use href="#i-users"></use></svg></span>
<span><strong>Passende Ready Lobby</strong><small>2 offene Lobbys passen zu deinem Profil.</small></span>
<time>jetzt</time>
</button>
<button class="header-notification-item unread" role="menuitem">
<span class="header-notification-icon purple"><svg><use href="#i-check"></use></svg></span>
<span><strong>Challenge angenommen</strong><small>Budget Hunter wurde bestätigt.</small></span>
<time>6m</time>
</button>
<button class="header-notification-item unread" role="menuitem">
<span class="header-notification-icon green"><svg><use href="#i-comment"></use></svg></span>
<span><strong>Neue Antwort</strong><small>Sarah hat deinen Beitrag kommentiert.</small></span>
<time>12m</time>
</button>
<button class="header-notification-item unread" role="menuitem">
<span class="header-notification-icon"><svg><use href="#i-bell"></use></svg></span>
<span><strong>Wochenauftrag</strong><small>Noch zwei Tage bis zum Ablauf.</small></span>
<time>1h</time>
</button>
<button class="header-notification-item unread" role="menuitem">
<span class="header-notification-icon yellow"><svg><use href="#i-folder"></use></svg></span>
<span><strong>Cup-Einreichung</strong><small>Deine Einreichung wird geprüft.</small></span>
<time>2h</time>
</button>
</div>
<button class="header-dropdown-footer" data-toast="Alle Benachrichtigungen geöffnet">
          Alle Benachrichtigungen
          <svg><use href="#i-arrow"></use></svg>
</button>
</section>
</div>
<div class="header-action-menu profile-menu">
<button aria-controls="profileDropdown" aria-expanded="false" aria-haspopup="true" aria-label="Profil öffnen" class="header-circle header-dropdown-trigger" id="profileMenuTrigger">
<svg><use href="#i-user"></use></svg>
</button>
<section aria-labelledby="profileMenuTrigger" class="header-dropdown profile-dropdown" id="profileDropdown" role="menu">
<div class="header-profile-summary">
<div class="header-profile-avatar">
<img alt="Valentina" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg') }}"/>
<i></i>
</div>
<div><strong>Valentina</strong><span>@valentina · Level 18</span></div>
</div>
<div class="header-profile-stats">
<span><strong>860</strong><small>Rocks</small></span>
<span><strong>128</strong><small>Freunde</small></span>
<span><strong>42</strong><small>Posts</small></span>
</div>
<div class="header-menu-list profile-menu-list">
<a href="{{ route('profile.show') }}" role="menuitem">
<span class="header-menu-icon"><svg><use href="#i-user"></use></svg></span>
<span><strong>Mein Profil</strong><small>Profil und öffentliche Ansicht</small></span>
<svg class="header-menu-arrow"><use href="#i-arrow"></use></svg>
</a>
<button data-toast="Inventar geöffnet" role="menuitem">
<span class="header-menu-icon"><svg><use href="#i-folder"></use></svg></span>
<span><strong>Inventar</strong><small>Rahmen, Badges und Items</small></span>
<svg class="header-menu-arrow"><use href="#i-arrow"></use></svg>
</button>
<button data-toast="Gamification geöffnet" role="menuitem">
<span class="header-menu-icon"><svg><use href="#i-sliders"></use></svg></span>
<span><strong>Fortschritt</strong><small>Level, XP und Aufträge</small></span>
<svg class="header-menu-arrow"><use href="#i-arrow"></use></svg>
</button>
</div>
<button class="header-profile-logout" data-toast="Abmeldung vorbereitet">
          Abmelden
        </button>
</section>
</div>
</div>
</header>
<section class="feed-stage">
<aside aria-label="Mein Bereich" class="profile-panel fixed-profile" id="profilePanel">
<div class="profile-cover">
<span>MEIN BEREICH</span>
<button aria-label="Einstellungen" class="profile-settings" data-toast="Einstellungen geöffnet">
<svg><use href="#i-settings"></use></svg>
</button>
</div>
<div class="profile-card-head">
<div class="profile-avatar-wrap">
<img alt="Valentina" class="profile-avatar" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg') }}"/>
<i aria-label="Online"></i>
</div>
<div class="profile-meta">
<strong>Valentina</strong>
<span>@valentina · Online</span>
</div>
<button class="edit-profile-button" data-toast="Profil bearbeiten">Bearbeiten</button>
</div>
<div class="profile-stats">
<article><strong>860</strong><span>Rocks</span></article>
<article><strong>128</strong><span>Freunde</span></article>
<article><strong>42</strong><span>Posts</span></article>
</div>
<div class="profile-level">
<div class="level-row"><span>Level 18</span><strong>72%</strong></div>
<div class="level-bar"><i></i></div>
<small>Noch 280 XP bis Level 19</small>
</div>
<div class="profile-links">
<button data-toast="Mein Profil geöffnet"><svg><use href="#i-user"></use></svg><span>Profil</span></button>
<button data-toast="Nachrichten geöffnet"><svg><use href="#i-comment"></use></svg><span>Nachrichten</span></button>
<button data-toast="Inventar geöffnet"><svg><use href="#i-folder"></use></svg><span>Inventar</span></button>
</div>
</aside>
<aside class="schedule-panel fixed-schedule hnt-agenda-panel">
<div class="section-head">
<div>
<span class="hnt-section-kicker">HNT.ROCKS</span>
<h2>Heute &amp; als Nächstes</h2>
</div>
<button class="circle-button" data-toast="Alle Aktivitäten geöffnet">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
<div aria-label="Zeitraum" class="week-row hnt-agenda-row">
<button class="active" data-toast="Aktuelle Aktivitäten">
<b>Jetzt</b><small>Live</small>
</button>
<button data-toast="Heutige Aktivitäten">
<b>Heute</b><small>3</small>
</button>
<button data-toast="Aktivitäten für morgen">
<b>Morgen</b><small>2</small>
</button>
<button data-toast="Aktivitäten dieser Woche">
<b>Diese Woche</b><small>5</small>
</button>
</div>
<div class="hnt-agenda-timeline">
<div aria-hidden="true" class="hnt-agenda-axis">
<span class="dark">JETZT</span>
<span>18:30</span>
<span class="yellow">20:00</span>
<span>MORGEN</span>
<span>WOCHE</span>
<i class="time-dot dark bottom">⌄</i>
</div>
<div class="hnt-agenda-items">
<article class="hnt-agenda-card dark">
<div class="hnt-agenda-copy">
<span>INBOX</span>
<h3>3 neue Nachrichten</h3>
<p>2 ungelesen · 1 Nachricht aus deinem Cup-Team</p>
</div>
<button data-toast="Nachrichten geöffnet">Öffnen</button>
</article>
<article class="hnt-agenda-card">
<div class="hnt-agenda-copy">
<span>PASSENDES LFG</span>
<h3>Trio für entspannte Runden</h3>
<p>EU · Console · Voice · 2 freie Plätze</p>
</div>
<div class="hnt-agenda-card-footer">
<div class="hnt-mini-avatars">
<img alt="" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg') }}"/>
<img alt="" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg') }}"/>
</div>
<button data-toast="LFG geöffnet">Ansehen</button>
</div>
</article>
<article class="hnt-agenda-card cup">
<div class="hnt-agenda-copy">
<span>COMMUNITY CUP</span>
<h3>Summer Hunt startet</h3>
<p>16 / 20 Teams · Trio · PS5 / Xbox</p>
</div>
<button data-toast="Cup geöffnet">Zum Cup</button>
</article>
<article class="hnt-agenda-card contract">
<div class="hnt-agenda-copy">
<span>WOCHENAUFTRAG</span>
<h3>Bayou Contractor</h3>
<p>4 / 7 Aufgaben · noch 2 Tage · +50 Rocks</p>
</div>
<div class="hnt-agenda-progress"><i style="width:57%"></i></div>
</article>
<article class="hnt-agenda-card challenge">
<div class="hnt-agenda-copy">
<span>LOADOUT CHALLENGE</span>
<h3>Budget Hunter</h3>
<p>Einreichung offen · Screenshot oder Clip möglich</p>
</div>
<button data-toast="Challenge geöffnet">Einreichen</button>
</article>
<article class="hnt-agenda-card highlight">
<div class="hnt-agenda-copy">
<span>MOMENT DER WOCHE</span>
<h3>One shot. Three problems.</h3>
<p>124 Likes · 31 Kommentare · 2,8k Views</p>
</div>
<button data-toast="Moment abgespielt">▶</button>
</article>
</div>
</div>
</aside>
<aside class="composition-panel fixed-composition" id="compositionPanel">
<div class="composition-top">
<h2>Employee Composition</h2>
<span class="composition-live"><i></i> Live</span>
</div>
<div class="composition-ring"><div><strong>345</strong><span>Total</span></div></div>
<div class="composition-values">
<span><i class="yellow"></i><strong>70%</strong><b class="gender-icon"><svg><use href="#i-female"></use></svg></b></span>
<span><i class="dark"></i><strong>30%</strong><b class="gender-icon"><svg><use href="#i-male"></use></svg></b></span>
</div>
<div class="community-note">
<span>HNT.ROCKS</span>
<strong>Community wächst</strong>
<small>+28 neue Hunter diese Woche</small>
</div>
<div class="composition-extra">
<div class="composition-stat-grid">
<article><span>Heute aktiv</span><strong>128</strong><small>+12%</small></article>
<article><span>Offene LFGs</span><strong>24</strong><small>6 neu</small></article>
<article><span>Moments</span><strong>83</strong><small>heute</small></article>
<article><span>Cup Teams</span><strong>16</strong><small>registriert</small></article>
</div>
<section class="composition-activity">
<div class="activity-title">
<h3>Live-Aktivität</h3>
<span id="activityState">Wird geladen</span>
</div>
<div class="activity-list" id="compositionActivity">
<div class="activity-skeleton"></div>
<div class="activity-skeleton short"></div>
<div class="activity-skeleton"></div>
</div>
</section>
<section class="composition-trending">
<div class="activity-title"><h3>Aktuell beliebt</h3><span>Community</span></div>
<div class="trend-tags">
<button>#SummerCup</button>
<button>#LFG</button>
<button>#Moments</button>
<button>#Rocks</button>
</div>
</section>
</div>
</aside>
<div class="feed-scroll" id="feedScroll">
<section class="feed-overview">
<div class="overview-left">
<h1>Hello Valentina</h1>
<div class="overview-progress">
<div class="overview-metric wide">
<span>Wochenauftrag</span>
<div class="bar dark">4 / 7</div>
</div>
<div class="overview-metric">
<span>Login-Serie</span>
<div class="bar yellow">5 / 7</div>
</div>
<div class="overview-metric project">
<span>Level-Fortschritt</span>
<div class="bar striped">72%</div>
</div>
<div class="overview-metric output">
<span>Rocks heute</span>
<div class="bar outline">18 / 30</div>
</div>
</div>
</div>
<div aria-label="Persönliche Übersicht" class="overview-counts">
<article>
<strong>3</strong>
<span>Nachrichten</span>
</article>
<article>
<strong>5</strong>
<span>Hinweise</span>
</article>
<article>
<strong>2</strong>
<span>Lobbys</span>
</article>
</div>
</section>
<section class="salary-attendance-card personal-dashboard-card">
<div class="salary-section personal-progress-section">
<div class="salary-head">
<div>
<span class="hnt-section-kicker">DEIN BEREICH</span>
<h2>Mein Fortschritt</h2>
</div>
<div class="personal-progress-tools">
<button class="active" data-toast="Aktive Fortschritte">Aktiv</button>
<button data-toast="Fortschrittsverlauf geöffnet">Verlauf</button>
<button class="circle-button" data-toast="Gamification geöffnet">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
</div>
<div class="personal-progress-table">
<div class="personal-progress-labels">
<span>Aktivität</span><span>Fortschritt</span><span>Belohnung</span><span>Status</span>
</div>
<article class="personal-progress-row">
<span class="personal-progress-icon contract">W</span>
<div class="personal-progress-copy">
<strong>Bayou Contractor</strong>
<small>Wochenauftrag</small>
</div>
<div class="personal-progress-value">
<strong>4 / 7</strong>
<div><i style="width:57%"></i></div>
</div>
<span class="personal-reward">+50 Rocks</span>
<span class="status active-status"><i></i>Aktiv</span>
</article>
<article class="personal-progress-row">
<span class="personal-progress-icon profile">P</span>
<div class="personal-progress-copy">
<strong>Profil vervollständigen</strong>
<small>Noch zwei Angaben fehlen</small>
</div>
<div class="personal-progress-value">
<strong>82%</strong>
<div><i style="width:82%"></i></div>
</div>
<span class="personal-reward">+100 Rocks</span>
<span class="status open-status"><i></i>Offen</span>
</article>
<article class="personal-progress-row">
<span class="personal-progress-icon challenge">L</span>
<div class="personal-progress-copy">
<strong>Budget Hunter</strong>
<small>Loadout Challenge</small>
</div>
<div class="personal-progress-value submitted">
<strong>Eingereicht</strong>
<small>vor 38 Min.</small>
</div>
<span class="personal-reward">+15 Rocks</span>
<span class="status review-status"><i></i>Prüfung</span>
</article>
</div>
<div class="personal-attention-strip">
<span>Benötigt deine Aufmerksamkeit</span>
<div>
<button data-toast="Ready Lobbys geöffnet"><b>2</b> Ready Lobbys</button>
<button data-toast="Nachrichten geöffnet"><b>3</b> Nachrichten</button>
<button data-toast="Cup-Einreichung geöffnet"><b>1</b> Cup-Prüfung</button>
</div>
</div>
</div>
<aside class="attendance-panel personal-activity-panel">
<div class="attendance-head">
<div>
<span class="personal-activity-kicker">LETZTE 30 TAGE</span>
<h2>Meine Aktivität</h2>
</div>
<button class="circle-button" data-toast="Aktivitätsverlauf geöffnet">
<svg><use href="#i-arrow"></use></svg>
</button>
</div>
<div class="attendance-values personal-activity-values">
<div><strong>12<sup>↗</sup></strong><span>Tage aktiv</span></div>
<div><strong>47<sup>↗</sup></strong><span>Aktionen</span></div>
</div>
<div aria-label="Aktivitäts-Heatmap der letzten 30 Tage" class="dot-matrix personal-heatmap">
<span></span><span></span><span></span><span></span><span class="y"></span><span></span><span class="y"></span><span></span><span></span><span></span><span class="y"></span>
<span></span><span></span><span class="y"></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span class="y"></span>
<span></span><span class="y"></span><span class="y"></span><span></span><span class="y"></span><span class="y"></span><span class="y"></span><span class="y"></span><span class="y"></span><span></span><span class="y"></span>
<span class="y"></span><span></span><span class="y"></span><span></span><span class="y"></span><span class="y"></span><span></span><span></span><span></span><span class="y"></span><span></span>
<span class="y"></span><span class="y"></span><span></span><span class="y"></span><span class="y"></span><span></span><span class="y"></span><span class="y"></span><span></span><span class="y"></span><span class="y"></span>
</div>
<div class="personal-activity-summary">
<article><strong>5</strong><span>Tage Serie</span></article>
<article><strong>320</strong><span>XP diese Woche</span></article>
<article><strong>68</strong><span>Rocks verdient</span></article>
</div>
</aside>
</section>
<section class="social-feed-card">
<header class="social-feed-head">
<div>
<span class="eyebrow">HNT.ROCKS</span>
<h2>Community Feed</h2>
</div>
<div class="feed-tabs">
<button class="active">Für dich</button>
<button>Folge ich</button>
<button aria-label="Post erstellen" class="compose-button" id="openPostComposer"><svg><use href="#i-plus"></use></svg><span>Post</span></button>
</div>
</header>
<div class="post-list">
<article class="social-post">
<header class="post-head">
<img alt="Katy Fuller" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg') }}"/>
<div class="post-author"><strong>Katy Fuller</strong><span>@katy · vor 4 Min.</span></div>
<span class="post-badge">LFG</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Suche zwei entspannte Hunter für heute Abend. Fokus auf saubere Rotationen, Voice wäre perfekt. Kein Stress, wir spielen auf Sieg – aber ohne Geschrei.</p>
<div class="lfg-strip">
<span><b>Hunt: Showdown</b><small>EU · Console</small></span>
<span><b>20:30</b><small>Start</small></span>
<span><b>1 / 3</b><small>Team</small></span>
<button data-toast="LFG geöffnet">Mitmachen <svg><use href="#i-arrow"></use></svg></button>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>24</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>8</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Jonathan Kelly" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg') }}"/>
<div class="post-author"><strong>Jonathan Kelly</strong><span>@jonathan · vor 18 Min.</span></div>
<span class="post-badge moment">Moment</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Der letzte Push war komplett chaotisch – aber genau deshalb liebe ich dieses Spiel. Drei Hunter, eine Kugel und sehr viel Glück.</p>
<div class="moment-preview">
<div class="moment-copy">
<span class="moment-label">HNT MOMENT</span>
<strong>One shot. Three problems.</strong>
<small>00:21 · Stillwater Bayou</small>
</div>
<button data-toast="Moment abgespielt">▶</button>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>71</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>19</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Erica Wyatt" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg') }}"/>
<div class="post-author"><strong>Erica Wyatt</strong><span>@erica · vor 43 Min.</span></div>
<span class="post-badge cup">Cup</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Die Anmeldung für den nächsten Community Cup ist offen. Trio, Konsole und zwei Wertungen: erste Trophäen-Extraktion plus Hunter-Kills.</p>
<div class="cup-preview">
<div><span>COMMUNITY CUP</span><strong>SUMMER HUNT</strong><small>Trio · PS5 / Xbox · 10.07.</small></div>
<button data-toast="Cup geöffnet">Details</button>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>112</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>34</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Sarah Page" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg') }}"/>
<div class="post-author"><strong>Sarah Page</strong><span>@sarah · vor 1 Std.</span></div>
<span class="post-badge rocks">Rocks</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Fast geschafft. Mir fehlen nur noch 140 Rocks für den neuen Avatarrahmen. Die täglichen Quests motivieren tatsächlich mehr als gedacht.</p>
<div class="rocks-progress">
<div><span>Rocks</span><strong>860 / 1.000</strong></div>
<div class="rocks-bar"><i></i></div>
<small>Noch 140 Rocks bis zur Belohnung</small>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>39</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>11</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Mara Voss" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/feed-erica.jpg') }}"/>
<div class="post-author"><strong>Mara Voss</strong><span>@mara · vor 2 Std.</span></div>
<span class="post-badge discussion">Diskussion</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Welche Map spielt ihr aktuell am liebsten – und warum? Bei mir ist es wieder Lawson Delta. Die Wege fühlen sich vorhersehbarer an, aber die Kämpfe bleiben trotzdem offen.</p>
<div class="poll">
<button><span>Lawson Delta</span><b>46%</b></button>
<button><span>Stillwater Bayou</span><b>31%</b></button>
<button><span>DeSalle</span><b>23%</b></button>
<small>184 Stimmen · noch 8 Std.</small>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>58</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>27</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Noah Brandt" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg') }}"/>
<div class="post-author"><strong>Noah Brandt</strong><span>@noah · vor 3 Std.</span></div>
<span class="post-badge news">Update</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Das Community-Update ist live. Die wichtigsten Änderungen betreffen LFG, Benachrichtigungen und die Darstellung von Moments im Feed.</p>
<div class="update-preview">
<div class="update-version"><span>HNT.ROCKS</span><strong>Community Update 2.4</strong><small>Heute veröffentlicht</small></div>
<ul>
<li><i></i> Schnellere LFG-Suche</li>
<li><i></i> Neue Moment-Reaktionen</li>
<li><i></i> Verbesserte Benachrichtigungen</li>
</ul>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>96</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>22</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Lena Hart" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg') }}"/>
<div class="post-author"><strong>Lena Hart</strong><span>@lenahart · vor 4 Std.</span></div>
<span class="post-badge loadout">Loadout</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Mein aktuelles Budget-Loadout für aggressive Runden. Günstig, flexibel und überraschend stark auf mittlere Distanz.</p>
<div class="loadout-grid">
<article><span class="loadout-icon">R</span><div><strong>Ranger 73</strong><small>Hauptwaffe</small></div><b>$73</b></article>
<article><span class="loadout-icon">N</span><div><strong>New Army</strong><small>Seitenwaffe</small></div><b>$90</b></article>
<article><span class="loadout-icon">⚕</span><div><strong>First Aid Kit</strong><small>Tool</small></div><b>$30</b></article>
<article><span class="loadout-icon">F</span><div><strong>Fire Bomb</strong><small>Consumable</small></div><b>$18</b></article>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>44</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>13</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Sarah Page" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/sarah.jpg') }}"/>
<div class="post-author"><strong>Sarah Page</strong><span>@sarah · vor 5 Std.</span></div>
<span class="post-badge achievement">Badge</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Endlich freigeschaltet. Der Grind für diese Auszeichnung war länger als geplant, aber der neue Profil-Badge sieht wirklich stark aus.</p>
<div class="achievement-card">
<div class="achievement-emblem"><span>III</span></div>
<div><span>BADGE FREIGESCHALTET</span><strong>Bayou Veteran</strong><small>100 erfolgreiche Extraktionen</small></div>
<button data-toast="Profil geöffnet">Ansehen</button>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>137</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>31</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Jonathan Kelly" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg') }}"/>
<div class="post-author"><strong>Jonathan Kelly</strong><span>@jonathan · vor 6 Std.</span></div>
<span class="post-badge team">Team</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Wir suchen noch einen dritten Spieler für unser festes Trio. Regelmäßig abends, ruhig im Voice und Interesse an Cups wäre optimal.</p>
<div class="team-preview">
<div class="team-avatars">
<img alt="" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg') }}"/>
<img alt="" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg') }}"/>
<span>+1</span>
</div>
<div><strong>Night Ravens</strong><small>EU · Console · Competitive</small></div>
<button data-toast="Teamanfrage gesendet">Anfragen</button>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>32</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>15</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
<article class="social-post">
<header class="post-head">
<img alt="Erica Wyatt" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg') }}"/>
<div class="post-author"><strong>Erica Wyatt</strong><span>@erica · vor 8 Std.</span></div>
<span class="post-badge guide">Guide</span>
<button class="post-more" data-toast="Post options"><svg><use href="#i-more"></use></svg></button>
</header>
<div class="post-body">
<p>Drei kleine Gewohnheiten, die meine Runden deutlich stabiler gemacht haben. Nichts Spektakuläres – aber zusammen macht es einen großen Unterschied.</p>
<div class="guide-steps">
<article><b>01</b><div><strong>Vor dem Push nachladen</strong><small>Nicht erst reagieren, wenn der Gegner schon drückt.</small></div></article>
<article><b>02</b><div><strong>Fluchtweg offenhalten</strong><small>Jeder aggressive Winkel braucht eine zweite Option.</small></div></article>
<article><b>03</b><div><strong>Geräusche bewusst setzen</strong><small>Nicht jede Information muss kostenlos sein.</small></div></article>
</div>
</div>
<footer class="post-actions">
<button class="like-button"><svg><use href="#i-heart"></use></svg><span>84</span></button>
<button aria-label="Kommentare öffnen" class="comment-button"><svg><use href="#i-comment"></use></svg><span>26</span></button>
<button data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
<button class="save-button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
</footer>
</article>
</div>
</section>
</div>
</section>
<div aria-hidden="true" class="mobile-dashboard-modal" id="mobileProfileModal">
<section aria-labelledby="mobileProfileTitle" aria-modal="true" class="mobile-dashboard-sheet" role="dialog">
<header class="mobile-dashboard-sheet-head">
<div>
<span>HNT.ROCKS</span>
<h2 id="mobileProfileTitle">Mein Profil</h2>
</div>
<button aria-label="Profil schließen" class="mobile-dashboard-close" data-mobile-modal-close="profile">
<svg><use href="#i-x"></use></svg>
</button>
</header>
<div class="mobile-dashboard-sheet-body mobile-profile-content" id="mobileProfileContent"></div>
</section>
</div>
<div aria-hidden="true" class="mobile-dashboard-modal" id="mobileStatsModal">
<section aria-labelledby="mobileStatsTitle" aria-modal="true" class="mobile-dashboard-sheet" role="dialog">
<header class="mobile-dashboard-sheet-head">
<div>
<span>HNT.ROCKS</span>
<h2 id="mobileStatsTitle">Stats &amp; Übersicht</h2>
</div>
<button aria-label="Stats schließen" class="mobile-dashboard-close" data-mobile-modal-close="stats">
<svg><use href="#i-x"></use></svg>
</button>
</header>
<div class="mobile-dashboard-sheet-body mobile-stats-content" id="mobileStatsContent"></div>
</section>
</div>
<div aria-hidden="true" class="post-composer-backdrop" id="postComposerModal">
<section aria-labelledby="postComposerTitle" aria-modal="true" class="post-composer-modal" role="dialog">
<header class="post-composer-head">
<div>
<span>HNT.ROCKS</span>
<h2 id="postComposerTitle">Post erstellen</h2>
</div>
<div class="post-composer-head-actions">
<button class="composer-audience" id="composerAudience" type="button">
<svg><use href="#i-users"></use></svg>
<span>Öffentlich</span>
<svg class="composer-chevron"><use href="#i-chevron"></use></svg>
</button>
<button aria-label="Post-Composer schließen" class="post-composer-close" id="postComposerClose">
<svg><use href="#i-x"></use></svg>
</button>
</div>
</header>
<div class="post-composer-body">
<div class="composer-identity">
<div class="composer-avatar">
<img alt="Valentina" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg') }}"/>
<i></i>
</div>
<div>
<strong>Valentina</strong>
<span>@valentina</span>
</div>
</div>
<div aria-label="Post-Typ" class="composer-types" role="tablist">
<button class="active" data-composer-type="Beitrag" type="button">Beitrag</button>
<button data-composer-type="LFG" type="button">LFG</button>
<button data-composer-type="Moment" type="button">Moment</button>
<button data-composer-type="Frage" type="button">Frage</button>
</div>
<label class="composer-textarea-shell">
<textarea id="postComposerInput" maxlength="1000" placeholder="Was gibt es Neues im Bayou?"></textarea>
<span id="postComposerCounter">0/1000</span>
</label>
<section aria-live="polite" class="composer-type-panel" id="composerTypePanel">
<div class="composer-type-copy">
<span>BEITRAG</span>
<strong>Teile Gedanken, Updates oder einen kurzen Bericht.</strong>
</div>
</section>
<div class="composer-attachment" hidden="" id="composerAttachment">
<div class="composer-attachment-preview">
<div>
<span>MEDIEN-VORSCHAU</span>
<strong>Bayou moment.jpg</strong>
<small>1920 × 1080 · 2,4 MB</small>
</div>
<button aria-label="Anhang entfernen" id="removeComposerAttachment" type="button">
<svg><use href="#i-x"></use></svg>
</button>
</div>
</div>
<div class="composer-tools">
<span>Zum Post hinzufügen</span>
<div>
<button aria-label="Medien hinzufügen" id="composerMediaButton" type="button">
<svg><use href="#i-image"></use></svg>
</button>
<button aria-label="Emoji hinzufügen" id="composerEmojiButton" type="button">
<svg><use href="#i-smile"></use></svg>
</button>
<button aria-label="LFG-Daten hinzufügen" id="composerLfgButton" type="button">
<svg><use href="#i-users"></use></svg>
</button>
</div>
</div>
</div>
<footer class="post-composer-footer">
<div class="composer-status">
<i></i>
<span>Bereit zum Veröffentlichen</span>
</div>
<div>
<button class="composer-draft" id="saveComposerDraft" type="button">Entwurf</button>
<button class="composer-publish" id="publishComposerPost" type="button">
<span>Veröffentlichen</span>
<svg><use href="#i-send"></use></svg>
</button>
</div>
</footer>
</section>
</div>
<div aria-hidden="true" class="comments-modal-backdrop" id="commentsModal">
<section aria-labelledby="commentsModalTitle" aria-modal="true" class="comments-modal" role="dialog">
<header class="comments-modal-head">
<div>
<span>HNT.ROCKS</span>
<div class="comments-title-row">
<h2 id="commentsModalTitle">Kommentare</h2>
<small id="commentsModalCount">0 Kommentare</small>
</div>
</div>
<button aria-label="Kommentare schließen" class="comments-close" id="commentsClose">
<svg><use href="#i-x"></use></svg>
</button>
</header>
<div class="comments-modal-content">
<article class="comments-post-context">
<img alt="" id="commentsPostAvatar" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/katy.jpg') }}"/>
<div class="comments-post-copy">
<div>
<strong id="commentsPostAuthor">Katy Fuller</strong>
<span id="commentsPostMeta">@katy · vor 4 Min.</span>
</div>
<p id="commentsPostExcerpt">Suche zwei entspannte Hunter für heute Abend.</p>
</div>
<span class="comments-post-badge" id="commentsPostBadge">LFG</span>
</article>
<div class="comments-toolbar">
<strong>Diskussion</strong>
<button id="commentsSort" type="button">Relevant <svg><use href="#i-chevron"></use></svg></button>
</div>
<section aria-live="polite" class="comments-list" id="commentsList"></section>
</div>
<form class="comments-composer" id="commentsComposer">
<img alt="Valentina" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg') }}"/>
<div class="comments-input-shell">
<textarea id="commentsInput" maxlength="500" placeholder="Kommentar schreiben …" rows="1"></textarea>
<div class="comments-compose-actions">
<div>
<button aria-label="Emoji auswählen" data-toast="Emoji-Auswahl" type="button"><svg><use href="#i-smile"></use></svg></button>
<button aria-label="Medien hinzufügen" data-toast="Medien hinzufügen" type="button"><svg><use href="#i-image"></use></svg></button>
</div>
<span id="commentsCounter">0/500</span>
<button aria-label="Kommentar senden" class="comments-send" type="submit">
<svg><use href="#i-send"></use></svg>
</button>
</div>
</div>
</form>
</section>
</div>
<div class="toast" id="toast"></div>
</main>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v=20260710-1"></script>
</body>
</html>
