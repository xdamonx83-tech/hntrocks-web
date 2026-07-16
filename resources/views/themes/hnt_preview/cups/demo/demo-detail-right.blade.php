<aside aria-label="Deine Cup-Teilnahme" class="cup-fixed-column cup-fixed-right" id="cupFixedRight"><aside class="cup-participation-card">
<section class="cup-participation-top">
<header>
<div><span>TEILNAHME</span><h2>Dein Cup</h2></div>
<strong>80%</strong>
</header>
<div class="cup-participation-segments">
<span><b>100%</b><i class="yellow"></i><small>Team</small></span>
<span><b>100%</b><i class="dark"></i><small>Bestätigt</small></span>
<span><b>40%</b><i class="grey"></i><small>Scores</small></span>
</div>
</section>
<section class="cup-my-team">
<header>
<div><span>DEIN TEAM</span><h2>Night Ravens</h2></div>
<strong>3/3</strong>
</header>
<div class="cup-team-members">
<article>
<img alt="Valentina" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg') }}"/>
<div><strong>Valentina</strong><span>Captain · bereit</span></div>
<i class="done"><svg><use href="#i-check"></use></svg></i>
</article>
<article>
<img alt="Jonathan Kelly" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/jonathan.jpg') }}"/>
<div><strong>Jonathan</strong><span>Mitglied · bereit</span></div>
<i class="done"><svg><use href="#i-check"></use></svg></i>
</article>
<article>
<img alt="Erica Wyatt" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/erica.jpg') }}"/>
<div><strong>Erica</strong><span>Mitglied · bereit</span></div>
<i class="done"><svg><use href="#i-check"></use></svg></i>
</article>
<article class="team-task">
<span class="team-task-icon"><svg><use href="#i-image"></use></svg></span>
<div><strong>Einreichungen</strong><span>2 von 3 genutzt</span></div>
<i></i>
</article>
<article class="team-task">
<span class="team-task-icon"><svg><use href="#i-comment"></use></svg></span>
<div><strong>Teamchat</strong><span>4 neue Nachrichten</span></div>
<i class="notice"></i>
</article>
</div>
<a aria-label="Team Night Ravens verwalten" class="cup-team-button" href="{{ $viewerTeam ? route('cups.teams.index', $cup) : route('cups.index') }}">Team verwalten <svg><use href="#i-arrow"></use></svg></a>
</section>
</aside></aside>