@php
    $profileInfoTags = collect([
        $profile?->hunt_role,
        $profile?->is_lfg_available ? 'LFG offen' : null,
        $profile?->platform,
        $profile?->playstyle,
    ])->filter()->unique()->values();

    $profileActivityValues = [
        ['label' => 'Posts', 'value' => (int) $profilePostsCount],
        ['label' => 'Mom.', 'value' => (int) $profileMomentsCount],
        ['label' => 'Freu.', 'value' => (int) $profileFriendsTotal],
        ['label' => 'Badg.', 'value' => (int) $profileBadgesCount],
        ['label' => 'Komm.', 'value' => (int) ($profileUser->feed_comments_count ?? 0)],
        ['label' => 'Teams', 'value' => (int) ($profileUser->active_teams_count ?? 0)],
        ['label' => 'Quest', 'value' => (int) ($profileCompletedQuestCount ?? 0)],
    ];
    $profileActivityMaximum = max(1, ...array_column($profileActivityValues, 'value'));
@endphp
<section class="profile-tab-panel {{ $profileDefaultTab === 'info' ? 'active' : '' }}" data-profile-panel="info" @if($profileDefaultTab !== 'info') hidden @endif id="profileTabInfo" role="tabpanel">
<div class="profile-info-dashboard">
@if(! $profileActivityVisible)
<article class="profile-detail-card">
<header><div><span>{{ __('settings.privacy_eyebrow') }}</span><h3>{{ __('settings.privacy_activity_hidden') }}</h3></div></header>
</article>
@endif
@if(! $profileGamificationVisible)
<article class="profile-detail-card">
<header><div><span>{{ __('settings.privacy_eyebrow') }}</span><h3>{{ __('settings.privacy_gamification_hidden') }}</h3></div></header>
</article>
@endif
<article class="profile-detail-card profile-about-card">
<header>
<div><span>ÜBER MICH</span><h3>{{ $profileDisplayName }}</h3></div>
@if($isOwnProfile)
<a aria-label="Info bearbeiten" href="{{ route('profile.edit') }}"><svg><use href="#i-sliders"></use></svg></a>
@endif
</header>
<p>{{ $profileBio }}</p>
@if($profileInfoTags->isNotEmpty())
<div class="profile-detail-tags">
@foreach($profileInfoTags as $tag)<span>{{ $tag }}</span>@endforeach
</div>
@endif
</article>

<article class="profile-detail-card">
<header><div><span>SPIELERPROFIL</span><h3>Hunt-Einstellungen</h3></div></header>
<dl class="profile-detail-list">
<div><dt>Plattform</dt><dd>{{ $profile?->platform ?: '—' }}</dd></div>
<div><dt>Region</dt><dd>{{ $profile?->region ?: '—' }}</dd></div>
<div><dt>Sprache</dt><dd>{{ $profile?->language ?: '—' }}</dd></div>
<div><dt>Spielstil</dt><dd>{{ $profile?->playstyle ?: '—' }}</dd></div>
<div><dt>Hunt-Rolle</dt><dd>{{ $profile?->hunt_role ?: '—' }}</dd></div>
<div><dt>Discord</dt><dd>{{ $profile?->discord_name ?: '—' }}</dd></div>
</dl>
</article>

@if($profileActivityVisible)
<article class="profile-detail-card profile-availability-card">
<header><div><span>AKTIVITÄT</span><h3>Profil in Zahlen</h3></div></header>
<div class="availability-week profile-activity-bars">
@foreach($profileActivityValues as $metric)
@php $metricHeight = $metric['value'] > 0 ? max(18, (int) round(($metric['value'] / $profileActivityMaximum) * 86)) : 8; @endphp
<span class="{{ $metric['value'] === $profileActivityMaximum && $metric['value'] > 0 ? 'active' : '' }}">
<b>{{ $metric['label'] }}</b><i style="height:{{ $metricHeight }}%"></i><small>{{ $profileFormatCount($metric['value']) }}</small>
</span>
@endforeach
</div>
</article>
@endif

<article class="profile-detail-card profile-favorites-card">
<header><div><span>PROFILSTATUS</span><h3>Community &amp; Fortschritt</h3></div></header>
<div class="profile-favorite-grid">
<span><small>LFG-Status</small><strong>{{ $profile?->is_lfg_available ? 'Offen für Gruppen' : 'Geschlossen' }}</strong></span>
<span><small>Profil-Sichtbarkeit</small><strong>{{ ucfirst((string) ($profile?->profile_visibility ?: 'public')) }}</strong></span>
@if($profileGamificationVisible)
<span><small>Abgeschlossene Quests</small><strong>{{ $profileFormatCount($profileCompletedQuestCount ?? 0) }}</strong></span>
<span><small>Aktueller Fortschritt</small><strong>Level {{ $profileLevel }} · {{ $profileLevelProgress }}%</strong></span>
@endif
</div>
</article>
</div>
</section>
