@php
$demoMembers = [
    ['name'=>'Valentina','handle'=>'valentina','headline'=>'Ruhige Rotationen und feste Teams','language'=>'DE / EN','platform'=>'PS5','region'=>'EU','playstyle'=>'Competitive','posts'=>42,'friends'=>128,'moments'=>18,'status'=>'Online','statusClass'=>'online','relationship'=>'friends','lfg'=>'ready','level'=>18,'image'=>'amelie.jpg','highlighted'=>false],
    ['name'=>'Katy Fuller','handle'=>'katy','headline'=>'Console Hunter · Voice bevorzugt','language'=>'DE','platform'=>'Xbox','region'=>'EU','playstyle'=>'Teamplay','posts'=>67,'friends'=>94,'moments'=>23,'status'=>'Ready','statusClass'=>'ready','relationship'=>'friends','lfg'=>'ready','level'=>21,'image'=>'katy.jpg','highlighted'=>true],
    ['name'=>'Jonathan Kelly','handle'=>'jonathan','headline'=>'Trio-Spieler und Cup-Team-Mitglied','language'=>'EN','platform'=>'PS5','region'=>'EU','playstyle'=>'Competitive','posts'=>31,'friends'=>82,'moments'=>11,'status'=>'Online','statusClass'=>'online','relationship'=>'friends','lfg'=>'ready','level'=>16,'image'=>'jonathan.jpg','highlighted'=>false],
    ['name'=>'Sarah Page','handle'=>'sarah','headline'=>'Entspannte Runden ohne Geschrei','language'=>'DE','platform'=>'PC','region'=>'EU','playstyle'=>'Casual','posts'=>25,'friends'=>61,'moments'=>8,'status'=>'Anfrage offen','statusClass'=>'pending','relationship'=>'pending','lfg'=>'open','level'=>14,'image'=>'sarah.jpg','highlighted'=>false],
    ['name'=>'Erica Wyatt','handle'=>'erica','headline'=>'Cup-Veteranin und Teamlead','language'=>'DE / EN','platform'=>'Xbox','region'=>'EU','playstyle'=>'Competitive','posts'=>88,'friends'=>143,'moments'=>34,'status'=>'Ready','statusClass'=>'ready','relationship'=>'friends','lfg'=>'ready','level'=>24,'image'=>'erica.jpg','highlighted'=>false],
    ['name'=>'Noah Brandt','handle'=>'noah','headline'=>'Guide-Autor · Builds und Loadouts','language'=>'DE','platform'=>'PC','region'=>'EU','playstyle'=>'Taktisch','posts'=>19,'friends'=>48,'moments'=>6,'status'=>'Offline','statusClass'=>'offline','relationship'=>'all','lfg'=>'closed','level'=>12,'image'=>'team-1.jpg','highlighted'=>false],
    ['name'=>'Mara Voss','handle'=>'mara','headline'=>'Moments und Community Cups','language'=>'DE','platform'=>'PS5','region'=>'EU','playstyle'=>'Teamplay','posts'=>54,'friends'=>109,'moments'=>29,'status'=>'Online','statusClass'=>'online','relationship'=>'all','lfg'=>'open','level'=>19,'image'=>'team-2.jpg','highlighted'=>false],
    ['name'=>'Leon Hart','handle'=>'leonhart','headline'=>'Neue Teams und feste Mitspieler gesucht','language'=>'EN','platform'=>'Xbox','region'=>'NA','playstyle'=>'Casual','posts'=>12,'friends'=>37,'moments'=>4,'status'=>'LFG offen','statusClass'=>'lfg','relationship'=>'pending','lfg'=>'open','level'=>11,'image'=>'feed-jonathan.jpg','highlighted'=>false],
];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>HNT.ROCKS — Mitglieder</title>
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-members/members-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-members/members-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="members">
@include('themes.hnt_preview.partials.icons')
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
<main class="app-shell members-page-shell">
@include('themes.hnt_preview.partials.header')
<section class="members-stage">
<div class="members-scroll">
<section class="members-overview">
    <div class="members-overview-copy">
        <span>HNT.ROCKS COMMUNITY</span>
        <h1>Mitglieder</h1>
        <div class="members-progress-row">
            <div class="members-progress-item wide"><span>Alle Hunter</span><div class="members-progress dark"><b>1.284</b><i style="width:100%"></i></div></div>
            <div class="members-progress-item"><span>Freunde</span><div class="members-progress yellow"><b>128</b><i style="width:68%"></i></div></div>
            <div class="members-progress-item"><span>Ready / LFG</span><div class="members-progress striped"><b>24</b><i style="width:42%"></i></div></div>
            <div class="members-progress-item compact"><span>Neu diese Woche</span><div class="members-progress outline"><b>28</b></div></div>
        </div>
    </div>
    <div class="members-overview-counts">
        <article><strong>128</strong><span>Online</span></article>
        <article><strong>24</strong><span>Ready</span></article>
        <article><strong>6</strong><span>Anfragen</span></article>
    </div>
</section>
<section class="members-directory-card">
    <div aria-label="Mitgliederaktionen" class="members-action-shelf">
        <button aria-label="Mitglied einladen" class="members-shelf-icon" data-toast="Mitglied einladen" type="button"><svg><use href="#i-plus"></use></svg></button>
        <button aria-label="Filter ein- oder ausblenden" class="members-shelf-icon members-shelf-filter" id="membersFilterButton" type="button"><svg><use href="#i-sliders"></use></svg></button>
        <button class="members-shelf-export" data-toast="Demo-Export vorbereitet" type="button"><svg><use href="#i-export"></use></svg><span>Export</span></button>
    </div>
    <header class="members-directory-head">
        <div aria-label="Mitgliederansicht" class="members-relationship-tabs" role="tablist">
            <button class="active" data-relationship-filter="all" type="button">Alle Mitglieder</button>
            <button data-relationship-filter="friends" type="button">Freunde <span>128</span></button>
            <button data-relationship-filter="pending" type="button">Anfragen <span>6</span></button>
        </div>
        <div class="members-directory-actions">
            <label class="members-search"><svg><use href="#i-search"></use></svg><input autocomplete="off" id="membersSearch" placeholder="Mitglieder suchen" type="search"></label>
        </div>
    </header>
    <div class="members-filter-strip" id="membersFilterStrip">
        <label><span>Plattform</span><select id="membersPlatformFilter"><option value="">Alle</option><option value="PS5">PS5</option><option value="Xbox">Xbox</option><option value="PC">PC</option></select></label>
        <label><span>Spielstil</span><select id="membersPlaystyleFilter"><option value="">Alle</option><option value="Competitive">Competitive</option><option value="Teamplay">Teamplay</option><option value="Casual">Casual</option><option value="Taktisch">Taktisch</option></select></label>
        <label><span>Region</span><select id="membersRegionFilter"><option value="">Alle</option><option value="EU">Europa</option><option value="NA">Nordamerika</option></select></label>
        <label><span>Sprache</span><select id="membersLanguageFilter"><option value="">Alle</option><option value="DE">Deutsch</option><option value="EN">Englisch</option><option value="DE / EN">Deutsch / Englisch</option></select></label>
        <button aria-pressed="false" class="members-ready-filter" id="membersReadyFilter" type="button"><i></i> Nur Ready / LFG</button>
        <button class="members-reset-filter" id="membersResetFilters" type="button">Filter zurücksetzen</button>
    </div>
    <div aria-label="Mitgliederliste" class="members-table" role="table">
        <div class="members-table-head" role="row"><span>Mitglied</span><span>Profil</span><span>Plattform</span><span>Region</span><span>Spielstil</span><span>Posts</span><span>Freunde</span><span>Moments</span><span>Status</span><span>Aktionen</span></div>
        <div class="members-table-body" id="membersTableBody">
            @foreach($demoMembers as $member)
            <article class="members-table-row {{ $member['highlighted'] ? 'is-highlighted' : '' }}" data-language="{{ $member['language'] }}" data-lfg="{{ $member['lfg'] }}" data-member-row data-name="{{ $member['name'].' @'.$member['handle'].' '.$member['headline'] }}" data-platform="{{ $member['platform'] }}" data-playstyle="{{ $member['playstyle'] }}" data-region="{{ $member['region'] }}" data-relationship="{{ $member['relationship'] }}">
                <div class="member-identity"><div class="member-avatar"><img alt="{{ $member['name'] }}" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/'.$member['image']) }}"><span>{{ $member['level'] }}</span></div><div><strong>{{ $member['name'] }}</strong><small>{{ '@'.$member['handle'] }}</small></div></div>
                <div class="member-headline"><strong>{{ $member['headline'] }}</strong><small>{{ $member['language'] }}</small></div>
                <span class="member-chip">{{ $member['platform'] }}</span>
                <span class="member-cell">{{ $member['region'] }}</span>
                <span class="member-cell">{{ $member['playstyle'] }}</span>
                <span class="member-number"><strong>{{ $member['posts'] }}</strong><small>Posts</small></span>
                <span class="member-number"><strong>{{ $member['friends'] }}</strong><small>Freunde</small></span>
                <span class="member-number"><strong>{{ $member['moments'] }}</strong><small>Moments</small></span>
                <span class="member-status {{ $member['statusClass'] }}"><i></i>{{ $member['status'] }}</span>
                <div class="member-row-actions"><button aria-label="Nachricht an {{ $member['name'] }}" data-toast="Nachricht an {{ $member['name'] }}" type="button"><svg><use href="#i-comment"></use></svg></button><button aria-label="Profil von {{ $member['name'] }} öffnen" data-toast="Profil von {{ $member['name'] }} geöffnet" type="button"><svg><use href="#i-arrow"></use></svg></button></div>
            </article>
            @endforeach
        </div>
        <div class="members-empty-state" hidden id="membersEmptyState"><strong>Keine Mitglieder gefunden</strong><span>Ändere deine Suche oder setze die Filter zurück.</span></div>
    </div>
    <footer class="members-directory-footer">
        <span id="membersResultCount">8 Mitglieder angezeigt</span>
        <nav aria-label="Mitgliederseiten" class="members-pagination"><button aria-label="Vorherige Seite" type="button">←</button><button class="active" type="button">1</button><button type="button">2</button><button type="button">3</button><button type="button">…</button><button type="button">12</button><button aria-label="Nächste Seite" type="button">→</button></nav>
    </footer>
</section>
</div>
</section>
<div aria-hidden="true" class="members-mobile-filter-backdrop" id="membersMobileFilterBackdrop">
    <section aria-labelledby="membersMobileFilterTitle" aria-modal="true" class="members-mobile-filter-modal" role="dialog">
        <header><div><span>HNT.ROCKS</span><h2 id="membersMobileFilterTitle">Mitglieder filtern</h2></div><button aria-label="Filter schließen" id="membersMobileFilterClose" type="button"><svg><use href="#i-x"></use></svg></button></header>
        <div id="membersMobileFilterMount"></div>
    </section>
</div>
<div class="toast" id="toast"></div>
</main>
<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = window.location.href;</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-members/members-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-members/members-live.js')) ?: time() }}"></script>
</body>
</html>
