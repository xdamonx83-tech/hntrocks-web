@php
    $viewer = auth()->user();
    $socialiteFeedFilter = $socialiteFeedFilter ?? 'all';
    $reworkAsset = fn (string $path): string => \App\Support\HntTheme::asset($path, 'rework');
    $formatCount = fn (int $count): string => number_format($count);
    $feedFilterUrl = function (string $filterKey): string {
        $query = request()->query();
        unset($query['page'], $query['fragment']);

        if ($filterKey === 'all') {
            unset($query['filter']);
        } else {
            $query['filter'] = $filterKey;
        }

        return route('feed.index', $query);
    };
    $memberProfileUrl = function ($member): string {
        if (! $member?->username) {
            return '#';
        }

        return (int) $member->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $member);
    };
    $postAuthorUrl = function ($author): string {
        if (! $author?->username) {
            return '#';
        }

        return (int) $author->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $author);
    };
    $feedFilters = [
        'all' => 'All',
        'friends' => 'Freunde',
        'media' => 'Medien',
        'mentions' => 'Mentions',
    ];
@endphp
<!DOCTYPE html>

<html lang="de">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>HNT.rocks Rework Feed Preview</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600;700&amp;family=Bakbak+One&amp;family=Montserrat:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/regular/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/bold/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/fill/style.css" rel="stylesheet"/>
<link href="{{ \App\Support\HntTheme::asset('styles.css', 'rework') }}" rel="stylesheet"/>
</head>
<body>
<div class="app">
<aside class="sidebar">
<div class="logo"><strong>HNT.</strong><span>ROCKS</span></div>
<button aria-expanded="false" aria-label="Sidebar erweitern" class="sidebar-toggle" data-sidebar-toggle="" type="button"><span></span><span></span></button>
<nav aria-label="Hauptnavigation" class="nav">
<a class="active" href="{{ route('feed.index') }}"><i aria-hidden="true" class="ph ph-house ph-icon"></i><span>Feed</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-game-controller ph-icon"></i><span>Games</span></a><a href="#"><i aria-hidden="true" class="ph ph-map-trifold ph-icon"></i><span>Maps</span></a>
<a class="thin" href="#"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i><span>Hunt</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-chart-bar ph-icon"></i><span>Gamification</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>Shop</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-trophy ph-icon"></i><span>Cups</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>Profil</span></a>
</nav>
<div class="nav-bottom">
<div class="nav-divider"></div>
<a data-settings-modal-open="" href="#"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>Einstellungen</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i><span>Logout</span></a>
</div>
</aside>
<main class="main">
<header class="topbar">
<a class="search-box" href="#"><i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i><span>Search</span></a>
<div class="top-actions">
<div class="action-menu notification-menu">
<a aria-expanded="false" aria-label="Notifications" class="action-btn has-dot" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-bell ph-icon"></i></a>
<div class="top-dropdown notification-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>Notifications</strong>
<span>Aktuelles aus deiner Lobby</span>
</div>
<a href="#">Alle</a>
</div>
<div class="dropdown-list">
<a class="dropdown-item unread" href="#">
<img alt="" src="{{ \App\Support\HntTheme::asset('images/high-1.png', 'rework') }}"/>
<span><strong>Summer Cup startet bald</strong><small>Team-Anmeldungen sind jetzt offen.</small></span>
<em>8m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ \App\Support\HntTheme::asset('images/sug-2.png', 'rework') }}"/>
<span><strong>Neuer Kommentar</strong><small>Tina hat auf deinen Feed-Post reagiert.</small></span>
<em>21m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/>
<span><strong>Loadout bewertet</strong><small>Dein Community-Loadout bekommt gerade Likes.</small></span>
<em>1h</em>
</a>
</div>
<a class="dropdown-footer" href="#">Alle Notifications öffnen</a>
</div>
</div>
<div class="action-menu friend-request-menu">
<a aria-expanded="false" aria-label="Freundschaftsanfragen" class="action-btn has-dot" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-user-plus ph-icon"></i></a>
<div class="top-dropdown friend-request-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>Freundschaftsanfragen</strong>
<span>Neue Hunter wollen sich verbinden</span>
</div>
<a href="#">Alle</a>
</div>
<div class="dropdown-list request-list">
<a class="dropdown-item request-item unread" href="#">
<img alt="Krispie Army" src="{{ \App\Support\HntTheme::asset('images/friend-krispie-army.png', 'rework') }}"/>
<span class="request-copy"><strong>Krispie Army</strong><small>@krispie-1 · 2 gemeinsame Freunde</small><span class="request-actions"><b>Annehmen</b><em>Ablehnen</em></span></span>
</a>
<a class="dropdown-item request-item" href="#">
<img alt="Babybel" src="{{ \App\Support\HntTheme::asset('images/friend-babybel.png', 'rework') }}"/>
<span class="request-copy"><strong>Babybel</strong><small>@Babybel · spielt EU / Xbox</small><span class="request-actions"><b>Annehmen</b><em>Ablehnen</em></span></span>
</a>
<a class="dropdown-item request-item" href="#">
<img alt="Faraz Tariq" src="{{ \App\Support\HntTheme::asset('images/sug-1.png', 'rework') }}"/>
<span class="request-copy"><strong>Faraz Tariq</strong><small>Hat dich über Members gefunden.</small><span class="request-actions"><b>Annehmen</b><em>Ablehnen</em></span></span>
</a>
</div>
<a class="dropdown-footer" href="#">Alle Anfragen öffnen</a>
</div>
</div>
<div class="action-menu message-menu">
<a aria-expanded="false" aria-label="Messages" class="action-btn" data-dropdown-toggle="" href="#"><i aria-hidden="true" class="ph ph-chat-circle-dots ph-icon"></i></a>
<div class="top-dropdown message-dropdown" data-dropdown-panel="">
<div class="dropdown-head">
<div>
<strong>Messages</strong>
<span>Neue Chats und Antworten</span>
</div>
<a href="#">Alle</a>
</div>
<div class="dropdown-list">
<a class="dropdown-item unread" href="#">
<img alt="" src="{{ \App\Support\HntTheme::asset('images/sug-2.png', 'rework') }}"/>
<span><strong>Tina Tzoo</strong><small>Bin gleich online, schick mir dein Loadout.</small></span>
<em>2m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ \App\Support\HntTheme::asset('images/sug-3.png', 'rework') }}"/>
<span><strong>MKBHD</strong><small>Sieht wild aus. Würde ich testen.</small></span>
<em>18m</em>
</a>
<a class="dropdown-item" href="#">
<img alt="" src="{{ \App\Support\HntTheme::asset('images/sug-1.png', 'rework') }}"/>
<span><strong>Faraz Tariq</strong><small>Ready Lobby später?</small></span>
<em>1h</em>
</a>
</div>
<a class="dropdown-footer" href="#">Alle Messages öffnen</a>
</div>
</div>
<div class="action-menu user-menu">
<a aria-expanded="false" aria-label="User menu" class="avatar-wrap" data-dropdown-toggle="" href="#"><img alt="Krispie" class="header-avatar" src="{{ \App\Support\HntTheme::asset('images/avatar-main.png', 'rework') }}"/></a>
<div class="top-dropdown user-dropdown" data-dropdown-panel="">
<div class="user-dropdown-head">
<img alt="Krispie" src="{{ \App\Support\HntTheme::asset('images/avatar-main.png', 'rework') }}"/>
<div>
<strong>Krispie</strong>
<span>Level 7 · 12,256 Marks</span>
</div>
</div>
<div class="user-menu-list">
<a href="#"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>Mein Profil</span></a>
<a data-settings-modal-open="" href="#"><i aria-hidden="true" class="ph ph-gear-six ph-icon"></i><span>Einstellungen</span></a>
<a href="#"><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/><span>Bounty Marks</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i><span>Shop</span></a>
</div>
<a class="user-logout" href="#"><i aria-hidden="true" class="ph ph-sign-out ph-icon"></i>Logout</a>
</div>
</div>
</div>
</header>
<section class="content-grid">
<div class="left-col">
<section class="card hero-card">
<div class="eyebrow">Newsfeed</div>
<h1>Check What Your Friends Up To!</h1>
<p>Conveniently customize proactive web services for leveraged without continually aggregate frictionless ou well-structured HNT activity..</p>
<div aria-label="{{ __('ui.rework_post_composer_open') }}" class="composer-mini" data-post-composer-open="" role="button" tabindex="0">
<span>{{ __('ui.rework_composer_prompt', ['name' => $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter')]) }}</span>
<span class="spacer"></span>
<a class="square-icon" data-post-composer-open="" href="#"><i aria-hidden="true" class="ph ph-image ph-icon"></i></a>
<a class="square-icon" data-post-composer-open="" href="#"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></a>
<a class="btn" data-post-composer-open="" href="#">{{ __('ui.rework_post_create') }}</a>
</div>
</section>
<nav class="rework-feed-filters" aria-label="Feed Filter">
@foreach($feedFilters as $filterKey => $filterLabel)
<a @class(['active' => $socialiteFeedFilter === $filterKey]) href="{{ $feedFilterUrl($filterKey) }}">{{ $filterLabel }}</a>
@endforeach
</nav>
<div data-rework-post-stream>
@include('themes.rework.feed.partials.post-items', ['socialitePosts' => $socialitePosts, 'reportedFeedKeys' => $reportedFeedKeys ?? collect()])
</div>
@if(method_exists($socialitePosts, 'hasMorePages') && $socialitePosts->hasMorePages())
<div class="rework-load-more-wrap" data-rework-load-more-wrap>
<button
    class="btn rework-load-more"
    type="button"
    data-rework-load-more
    data-next-url="{{ $socialitePosts->nextPageUrl() }}"
    data-loading-label="Lädt..."
    data-ready-label="Weitere Posts laden"
    data-error-label="Erneut versuchen"
>
    <span data-rework-load-more-label>Weitere Posts laden</span>
</button>
</div>
@endif
</div>
<aside class="right-col">
<section class="profile-card card">
<div class="profile-top"><strong>{{ $viewer?->name ?: 'HNT Hunter' }}</strong><span class="status">Online</span></div>
<div class="profile-main">
<img alt="Bounty Marks" class="mark" src="{{ $reworkAsset('images/bounty-mark.png') }}"/>
<div class="levels">
<span class="level-badge">Level {{ $viewer?->level ?? 1 }}</span>
<span class="level-badge">{{ number_format((int) ($viewer?->xp_total ?? 0)) }} XP</span>
</div>
</div>
<div class="balance">
<div><strong>{{ number_format((int) ($viewer?->xp_total ?? 0)) }}</strong><span>XP gesammelt</span></div>
<div class="profile-buttons"><a class="btn light" href="#">Shop</a><a class="btn light" href="#">ProPass</a></div>
</div>
</section>
<section class="side-card suggested">
<div class="side-head"><h2>Suggested For You</h2><a href="#">See All</a></div>
<div class="suggestion-list">
@forelse(($socialiteMembers ?? collect())->take(3) as $member)
<div class="suggestion"><img alt="{{ $member->name }}" src="{{ $member->avatarUrl() }}"/><div class="suggestion-info"><strong>{{ $member->name }}</strong><span>{{ $member->username ? '@'.$member->username : 'HNT Hunter' }}</span></div><a class="btn light" href="{{ $memberProfileUrl($member) }}">View</a></div>
@empty
<div class="suggestion"><img alt="" src="{{ $reworkAsset('images/sug-1.png') }}"/><div class="suggestion-info"><strong>HNT Community</strong><span>No hunters yet</span></div><a class="btn light" href="#">View</a></div>
@endforelse
</div>
</section>
<section class="side-card highlights">
<div class="side-head"><h2>Highlights</h2></div>
<div class="highlight-list">
@forelse(($socialiteTeams ?? collect())->take(3) as $team)
<div class="highlight"><img alt="{{ $team->name }}" src="{{ $team->avatarUrl() }}"/><div class="highlight-info"><strong>{{ $team->name }}</strong><span>{{ trans_choice('ui.hunter_count', (int) ($team->active_members_count ?? 0), ['count' => (int) ($team->active_members_count ?? 0)]) }}</span></div><span class="highlight-time">Team</span></div>
@empty
<div class="highlight"><img alt="" src="{{ $reworkAsset('images/high-1.png') }}"/><div class="highlight-info"><strong>HNT Teams</strong><span>No featured teams yet</span></div><span class="highlight-time">Preview</span></div>
@endforelse
</div>
</section>
</aside>
</section>
</main>
</div>
<div aria-hidden="true" class="modal-backdrop" data-comment-modal="" data-rework-report-url="{{ route('reports.store') }}">
<section aria-labelledby="comment-modal-title" aria-modal="true" class="comment-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<button aria-label="{{ __('ui.preview_post_modal_close_aria') }}" class="modal-close post-composer-close" data-comment-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
<div class="comment-modal-layout">
<div class="comment-modal-post">
<div class="modal-post-head">
<a data-rework-modal-author-url href="#"><img alt="" src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"/></a>
<div>
<a data-rework-modal-author-url href="#"><strong data-rework-modal-author></strong></a>
<span data-rework-modal-meta></span>
</div>
</div>
<div class="modal-post-media" data-rework-modal-media hidden></div>
<div class="modal-post-body" data-rework-modal-body hidden></div>
<div class="modal-post-stats" data-rework-modal-stats></div>
</div>
<div class="comment-modal-panel">
<div class="comment-modal-head">
<div>
<span>{{ __('ui.feed_post') }}</span>
<h2 id="comment-modal-title">{{ __('ui.comments') }}</h2>
</div>
<strong data-rework-modal-comment-count>0</strong>
</div>
<div class="comment-thread" data-rework-modal-comments></div>
<form class="modal-composer" data-rework-comment-form method="post">
<img alt="{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}" src="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<input name="body" placeholder="{{ __('ui.rework_comment_placeholder') }}" autocomplete="off" type="text" data-rework-comment-input/>
<button class="square-icon" data-rework-emoji-toggle type="button" aria-label="{{ __('ui.preview_emoji_button') }}"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></button>
<button class="btn" data-rework-comment-submit type="submit">{{ __('ui.send') }}</button>
<div class="rework-emoji-picker rework-comment-emoji-picker" data-rework-emoji-picker hidden></div>
<p class="rework-comment-status" data-rework-comment-status hidden></p>
<input name="parent_id" type="hidden" data-rework-comment-parent>
</form>
</div>
</div>
</section>
</div>
<div aria-hidden="true" class="modal-backdrop reactions-backdrop" data-reactions-modal="">
<section aria-labelledby="reactions-modal-title" aria-modal="true" class="reactions-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<button aria-label="Reaktionen schließen" class="modal-close post-composer-close" data-reactions-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
<header class="reactions-modal-head">
<div>
<span>Feed</span>
<h2 id="reactions-modal-title">Reaktionen</h2>
</div>
<strong data-reactions-total>0 Reaktionen</strong>
</header>
<div class="reactions-stats" data-reactions-stats></div>
<div class="reactions-list" data-reactions-list>
<div class="comment-empty-state">Noch keine Reaktionen.</div>
</div>
</section>
</div>
<div aria-hidden="true" class="modal-backdrop post-composer-backdrop" data-post-composer-modal="">
<form action="{{ route('feed.store') }}" data-rework-post-composer-form enctype="multipart/form-data" id="reworkPostComposerForm" method="post" hidden>
@csrf
<input name="background_style" type="hidden" value="none">
<input data-rework-composer-visibility-input name="visibility" type="hidden" value="public">
<input data-rework-composer-ai-input name="ai_generated" type="hidden" value="0">
<input data-rework-composer-feeling-input name="feeling_key" type="hidden" value="none">
<input accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" data-rework-composer-file-input id="reworkComposerMedia" multiple name="media[]" type="file">
</form>
<section aria-labelledby="post-composer-title" aria-modal="true" class="post-composer-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<header class="post-composer-header">
<div class="post-composer-titleblock">
<span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>HNT FEED</span>
<h2 id="post-composer-title">Post erstellen</h2>
<p>Teile etwas mit der HNT-Community.</p>
</div>
<button aria-label="Post erstellen schließen" class="post-composer-close" data-post-composer-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<div class="post-composer-body">
<div class="post-composer-author-row">
<div class="post-composer-author">
<img alt="{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}" src="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}">
<div>
<strong>{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}</strong>
<span>Community · HNT Feed</span>
</div>
</div>
<a class="audience-pill" data-rework-composer-audience href="#">Community <span><i aria-hidden="true" class="ph ph-caret-down ph-icon"></i></span></a>
<div class="rework-composer-audience-menu" data-rework-composer-audience-menu hidden>
<button data-rework-composer-audience-option="public" type="button"><strong>Community</strong><span>Alle eingeloggten Hunter</span></button>
<button data-rework-composer-audience-option="followers" type="button"><strong>Freunde</strong><span>Nur dein Netzwerk</span></button>
<button data-rework-composer-audience-option="private" type="button"><strong>Privat</strong><span>Nur du</span></button>
</div>
</div>
<div class="post-composer-textbox">
<textarea data-rework-composer-textarea form="reworkPostComposerForm" maxlength="5000" name="body" placeholder="Was gibt es Neues im Bayou?"></textarea>
<div class="composer-textbox-footer">
<div aria-hidden="true" class="composer-ghost-actions">
<span></span><span></span><span></span>
</div>
<a aria-label="Emoji hinzufügen" class="composer-emoji" data-rework-composer-emoji href="#"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></a>
</div>
</div>
<div class="post-composer-tools">
<a data-rework-composer-media-trigger href="#"><span><i aria-hidden="true" class="ph ph-plus ph-icon"></i></span>Medien</a>
<a data-rework-composer-feeling href="#"><span><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></span>Gefühl</a>
<a data-rework-composer-poll href="#"><span><i aria-hidden="true" class="ph ph-question ph-icon"></i></span>Umfrage</a>
<a data-rework-composer-ai-toggle href="#"><span class="composer-check"><i aria-hidden="true" class="ph ph-square ph-icon"></i></span>KI-Inhalt</a>
</div>
<div class="rework-composer-addons" data-rework-composer-addons>
<div class="rework-composer-media-preview" data-rework-composer-media-preview hidden></div>
<div class="rework-composer-selected-feeling" data-rework-composer-feeling-selected hidden></div>
<div class="rework-composer-feeling-panel" data-rework-composer-feeling-panel hidden>
<button data-rework-composer-feeling-option="happy" type="button">😄 Happy</button>
<button data-rework-composer-feeling-option="excited" type="button">🔥 Hype</button>
<button data-rework-composer-feeling-option="focused" type="button">🎯 Fokus</button>
<button data-rework-composer-feeling-option="chill" type="button">😎 Chill</button>
<button data-rework-composer-feeling-option="tired" type="button">💀 Müde</button>
<button data-rework-composer-feeling-option="salty" type="button">🧂 Salty</button>
</div>
<div class="rework-composer-poll-panel" data-rework-composer-poll-panel hidden>
<label><span>Frage</span><input form="reworkPostComposerForm" maxlength="180" name="poll_question" placeholder="Was möchtest du wissen?" type="text"></label>
<label><span>Antwort 1</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 1" type="text"></label>
<label><span>Antwort 2</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 2" type="text"></label>
<label><span>Antwort 3</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 3 optional" type="text"></label>
<label><span>Antwort 4</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 4 optional" type="text"></label>
</div>
</div>
</div>
<footer class="post-composer-footer">
<a class="composer-cancel" data-post-composer-close="" href="#">Abbrechen</a>
<a class="composer-submit" data-rework-composer-submit href="#">Posten</a>
</footer>
</section>
</div>
<div aria-hidden="true" class="modal-backdrop settings-backdrop" data-settings-modal="">
<section aria-labelledby="settings-modal-title" aria-modal="true" class="settings-modal" role="dialog">
<header class="settings-modal-header">
<div>
<span class="settings-eyebrow">Account</span>
<h2 id="settings-modal-title">Einstellungen</h2>
<p>Benachrichtigungen, Datenschutz, blockierte Nutzer und Sicherheit an einem Ort verwalten.</p>
</div>
<button aria-label="Einstellungen schließen" class="settings-close" data-settings-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<nav aria-label="Einstellungen Tabs" class="settings-tabs">
<button class="is-active" data-settings-tab="notifications" type="button"><i aria-hidden="true" class="ph ph-bell ph-icon"></i><span>Benachrichtigungen</span></button>
<button data-settings-tab="privacy" type="button"><i aria-hidden="true" class="ph ph-shield-check ph-icon"></i><span>Datenschutz</span></button>
<button data-settings-tab="blocked" type="button"><i aria-hidden="true" class="ph ph-prohibit ph-icon"></i><span>Blockierte Nutzer</span></button>
<button data-settings-tab="security" type="button"><i aria-hidden="true" class="ph ph-lock-key ph-icon"></i><span>Sicherheit</span></button>
</nav>
<div class="settings-modal-body">
<section class="settings-panel is-active" data-settings-panel="notifications">
<div class="settings-section-head">
<span>Notification Center</span>
<h3>Benachrichtigungseinstellungen</h3>
<p>Lege fest, welche HNT.rocks-Meldungen im System erscheinen sollen.</p>
</div>
<div class="settings-toggle-list">
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Feed-Kommentare</strong><p>Wenn jemand deine Feed-Beiträge kommentiert.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Feed-Reaktionen</strong><p>Wenn jemand auf deine Feed-Beiträge reagiert.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Freunde &amp; Netzwerk</strong><p>Anfragen, angenommene Freundschaften und Netzwerk-Aktivität.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Teams</strong><p>Team-Anfragen, Team-Aktivität und Team-LFG.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>LFG</strong><p>Bewerbungen, Annahmen und Ablehnungen in der Mitspielersuche.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Badges &amp; Quests</strong><p>Freigeschaltete Badges und abgeschlossene Quests.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Moments</strong><p>Likes und Kommentare auf deinen Moments.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Cups</strong><p>Cup-Teams, Einreichungen und Ergebnisse.</p></div></label>
</div>
</section>
<section class="settings-panel" data-settings-panel="privacy">
<div class="settings-section-head">
<span>Datenschutz</span>
<h3>Kontakt &amp; Profilsichtbarkeit</h3>
<p>Steuere, wer dein Profil sehen kann und wer dich direkt kontaktieren darf.</p>
</div>
<div class="settings-form-grid">
<label><span>Profil-Sichtbarkeit</span><select><option>Öffentlich</option><option>Nur angemeldete Nutzer</option><option>Privat</option></select></label>
<label><span>Nachrichten erlauben von</span><select><option>Allen</option><option>Angemeldeten Nutzern</option><option>Nur Kontakten</option><option>Niemandem</option></select></label>
</div>
<div class="settings-toggle-list compact">
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Team-Einladungen erlauben</strong><p>Andere Spieler können dich zu Teams einladen.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>LFG-Einladungen erlauben</strong><p>Andere Spieler können dich für Mitspielersuche kontaktieren.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Online-Status anzeigen</strong><p>Dein Status kann in Profil- und Community-Bereichen erscheinen.</p></div></label>
<label class="settings-toggle-row"><input checked="" type="checkbox"/><span></span><div><strong>Level, Badges und Quests anzeigen</strong><p>Dein Fortschritt darf öffentlich im Profil sichtbar sein.</p></div></label>
</div>
</section>
<section class="settings-panel" data-settings-panel="blocked">
<div class="settings-section-head">
<span>Datenschutz</span>
<h3>Blockierte Nutzer</h3>
<p>Blockierte Spieler können später für Nachrichten, Einladungen und Interaktionen ausgeschlossen werden.</p>
</div>
<div class="settings-form-grid blocked-form">
<label><span>Nutzername</span><input placeholder="z. B. huntername" type="text"/></label>
<label><span>Notiz</span><input placeholder="Optionaler Grund für dich" type="text"/></label>
<a class="settings-inline-btn" href="#">Blockieren</a>
</div>
<div class="blocked-list">
<div class="blocked-item"><div><strong>ToxicHunter</strong><span>Spam im Chat</span></div><a href="#">Aufheben</a></div>
<div class="blocked-item"><div><strong>CampKing77</strong><span>Optionaler Grund für dich</span></div><a href="#">Aufheben</a></div>
</div>
</section>
<section class="settings-panel" data-settings-panel="security">
<div class="settings-section-head">
<span>Sicherheit</span>
<h3>Passwort &amp; Datenkontrolle</h3>
<p>Passwort, 2FA, Datenexport und Kontolöschung verwalten.</p>
</div>
<div class="settings-form-grid">
<label><span>Aktuelles Passwort bestätigen</span><input placeholder="••••••••" type="password"/></label>
<label><span>Neues Passwort</span><input placeholder="Neues Passwort" type="password"/></label>
<label><span>Neues Passwort bestätigen</span><input placeholder="Wiederholen" type="password"/></label>
</div>
<div class="settings-action-grid">
<a href="#"><i aria-hidden="true" class="ph ph-lock-key ph-icon"></i><strong>Passwort ändern</strong><span>Login-Daten aktualisieren</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-device-mobile-camera ph-icon"></i><strong>2FA einrichten</strong><span>Authenticator-App verbinden</span></a>
<a href="#"><i aria-hidden="true" class="ph ph-download-simple ph-icon"></i><strong>Datenexport</strong><span>Accountdaten herunterladen</span></a>
<a class="danger" href="#"><i aria-hidden="true" class="ph ph-warning ph-icon"></i><strong>Kontolöschung</strong><span>Löschung vormerken</span></a>
</div>
</section>
</div>
<footer class="settings-modal-footer">
<button class="settings-cancel" data-settings-modal-close="" type="button">Abbrechen</button>
<button class="settings-save" type="button">Speichern</button>
</footer>
</section>
</div>
<script defer src="{{ \App\Support\HntTheme::asset('script.js', 'rework') }}"></script>
</body>
</html>
