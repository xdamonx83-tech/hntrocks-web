<section class="team-manage-center-flow" id="teamManageCenter">
<article class="team-center-card">
<header class="team-center-head">
<div class="team-center-title">
<span>NIGHT RAVENS</span>
<h2 id="teamPanelTitle">Übersicht</h2>
</div>
<nav aria-label="Team Bereiche" class="team-tabs" role="tablist">
<button class="active" data-team-tab="overview" data-title="Übersicht" type="button">Übersicht</button>
<button data-team-tab="members" data-title="Mitglieder" type="button">Mitglieder</button>
<button data-team-tab="invites" data-title="Einladungen" type="button">Einladungen</button>
<button data-team-tab="submissions" data-title="Einreichungen" type="button">Einreichungen</button>
<button data-team-tab="recruiting" data-title="Recruiting" type="button">Recruiting</button>
</nav>
</header>
<div class="team-panels">
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-panel-overview')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-panel-members')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-panel-invites')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-panel-submissions')
@include('themes.hnt_preview.cups.demo.team-manage.demo-team-manage-panel-recruiting')
</div>
</article>
</section>