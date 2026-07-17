<section class="team-manage-center-flow" id="teamManageCenter">
<article class="team-center-card">
<header class="team-center-head">
<div class="team-center-title">
<span>{{ $team->displayName() }}</span>
<h2 id="teamPanelTitle">{{ $t('Übersicht', 'Overview') }}</h2>
</div>
<nav aria-label="{{ $t('Team Bereiche', 'Team sections') }}" class="team-tabs" role="tablist">
<button class="active" data-team-tab="overview" data-title="{{ $t('Übersicht', 'Overview') }}" type="button">{{ $t('Übersicht', 'Overview') }}</button>
<button data-team-tab="members" data-title="{{ $t('Mitglieder', 'Members') }}" type="button">{{ $t('Mitglieder', 'Members') }}</button>
<button data-team-tab="invites" data-title="{{ $t('Einladungen', 'Invites') }}" type="button">{{ $t('Einladungen', 'Invites') }}</button>
<button data-team-tab="submissions" data-title="{{ $t('Einreichungen', 'Submissions') }}" type="button">{{ $t('Einreichungen', 'Submissions') }}</button>
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
