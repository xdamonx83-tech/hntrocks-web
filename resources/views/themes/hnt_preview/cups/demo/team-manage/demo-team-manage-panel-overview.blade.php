<section class="team-panel active" data-team-panel="overview">
<div class="team-overview-grid">
<article class="team-panel-card team-name-card">
<header>
<div>
<span>TEAMNAME</span>
<h3>Night Ravens</h3>
</div>
<button aria-label="Teamname bearbeiten" data-open-team-name="" type="button">
<svg><use href="#i-edit"></use></svg>
</button>
</header>
<p>Der Captain kann den Teamnamen bis zum Team-Lock ändern.</p>
<form class="team-name-form" id="teamNameForm">
<label for="teamNameInput">Teamname bearbeiten</label>
<div>
<input id="teamNameInput" maxlength="100" value="Night Ravens"/>
<button type="submit">Speichern</button>
</div>
</form>
<div class="team-status-pills">
<span class="ready">Aktiv</span>
<span>Roster offen</span>
<span>Captain: Valentina</span>
</div>
</article>
<article class="team-panel-card team-readiness-card">
<header>
<div>
<span>TEILNAHMESTATUS</span>
<h3>Bereit für den Cup</h3>
</div>
<strong>100%</strong>
</header>
<div class="team-readiness-steps">
<span class="done"><i><svg><use href="#i-check"></use></svg></i><b>Team</b><small>3 / 3 vollständig</small></span>
<span class="done"><i><svg><use href="#i-check"></use></svg></i><b>Bestätigung</b><small>alle bereit</small></span>
<span class="current"><i>2</i><b>Scores</b><small>2 von 3 genutzt</small></span>
</div>
</article>
</div>
<article class="team-panel-card team-members-overview">
<header>
<div>
<span>DEIN TEAM</span>
<h3>Mitglieder</h3>
</div>
<button data-team-tab-shortcut="members" type="button">Alle ansehen</button>
</header>
<div class="team-member-table">
<article>
<img alt="Valentina" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg') }}"/>
<div><strong>Valentina</strong><span>@valentina · Captain</span></div>
<span class="platform">PS5</span>
<span class="member-state ready"><i></i>Bereit</span>
<button data-toast="Profil von Valentina geöffnet" type="button"><svg><use href="#i-arrow"></use></svg></button>
</article>
<article>
<img alt="Jonathan" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg') }}"/>
<div><strong>Jonathan</strong><span>@jonathan · Mitglied</span></div>
<span class="platform">PS5</span>
<span class="member-state ready"><i></i>Bereit</span>
<button data-toast="Profil von Jonathan geöffnet" type="button"><svg><use href="#i-arrow"></use></svg></button>
</article>
<article>
<img alt="Erica" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg') }}"/>
<div><strong>Erica</strong><span>@erica · Mitglied</span></div>
<span class="platform">Xbox</span>
<span class="member-state ready"><i></i>Bereit</span>
<button data-toast="Profil von Erica geöffnet" type="button"><svg><use href="#i-arrow"></use></svg></button>
</article>
</div>
</article>
<div class="team-overview-grid lower">
<article class="team-panel-card team-submission-ready">
<header>
<div>
<span>EINREICHUNGEN</span>
<h3>Captain kann hochladen</h3>
</div>
<strong>2 / 3</strong>
</header>
<p>Das Team ist vollständig und der Einreichungszeitraum ist geöffnet.</p>
<div class="team-upload-progress"><i style="width:67%"></i></div>
<button data-toast="Einreichungsbereich geöffnet" type="button">Screenshot einreichen</button>
</article>
<article class="team-panel-card team-activity-card">
<header>
<div>
<span>LETZTE AKTIVITÄT</span>
<h3>Im Team</h3>
</div>
<small>heute</small>
</header>
<ul>
<li><i></i><span><strong>Jonathan</strong> hat im Teamchat geschrieben</span><time>4m</time></li>
<li><i></i><span><strong>Valentina</strong> hat einen Score eingereicht</span><time>38m</time></li>
<li><i></i><span><strong>Erica</strong> wurde als bereit markiert</span><time>2h</time></li>
</ul>
</article>
</div>
<article class="team-panel-card team-danger-zone">
<div>
<span>TEAM VERLASSEN</span>
<h3>Teilnahme zurückziehen</h3>
<p>Als Captain würdest du das gesamte Team aus dem Cup zurückziehen.</p>
</div>
<button data-confirm-team-leave="" type="button">Team zurückziehen</button>
</article>
</section>