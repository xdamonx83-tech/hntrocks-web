<section class="profile-summary-card {{ $profileCoverDisplayMode === \App\Models\UserProfile::COVER_DISPLAY_ALWAYS ? 'is-cover-peek' : '' }}" data-profile-cover-peek data-cover-display-mode="{{ $profileCoverDisplayMode }}">
@if($profileCoverDisplayMode !== \App\Models\UserProfile::COVER_DISPLAY_HIDDEN)
<div aria-hidden="true" class="profile-cover-peek-layer" style="background-image:linear-gradient(180deg,rgba(251,249,238,.24) 0%,rgba(251,249,238,.38) 34%,rgba(251,249,238,.82) 72%,#fbf9ee 100%),url('{{ $profileCoverUrl }}')"></div>
@endif
<div class="profile-summary-top">
<div>
<span class="profile-eyebrow">HNT.ROCKS PROFIL</span>
<h2>{{ $profileDisplayName }}</h2>
<p>{{ $profileHandle }} · {{ $profileIsOnline ? 'Online' : 'Offline' }}</p>
</div>
<div class="profile-summary-actions">
@if($profileMessageUrl)
<a aria-label="Nachricht an {{ $profileDisplayName }}" href="{{ $profileMessageUrl }}"><svg><use href="#i-comment"></use></svg></a>
@endif
<button aria-label="Profil teilen" data-profile-share type="button"><svg><use href="#i-share"></use></svg></button>
</div>
</div>
<div class="profile-tags">
@if($profile?->platform)<span class="yellow">{{ $profile->platform }}</span>@endif
@if($profile?->region)<span class="blue">{{ $profile->region }}</span>@endif
@if($profile?->language)<span class="purple">{{ $profile->language }}</span>@endif
@if($profile?->playstyle)<span class="green">{{ $profile->playstyle }}</span>@endif
</div>
<div class="profile-summary-body">
<div class="profile-bio">
<p>{{ $profileBio }}</p>
<div class="profile-bio-points">
<span><b>Rolle</b> {{ $profile?->hunt_role ?: '—' }}</span>
<span><b>LFG</b> {{ $profile?->is_lfg_available ? 'Offen für Gruppen' : 'Derzeit geschlossen' }}</span>
<span><b>Discord</b> {{ $profile?->discord_name ?: '—' }}</span>
</div>
</div>
<div class="profile-level-ring" style="--profile-level-progress:{{ $profileLevelProgress }}%">
<div><strong>{{ $profileLevel }}</strong><span>Level</span></div>
</div>
</div>
<div class="profile-stat-labels">
<span>Beiträge</span><span>Moments</span><span>Freunde</span><span>Rocks</span>
</div>
<div class="profile-stat-pipeline">
<div class="yellow">{{ $profileFormatCount($profilePostsCount) }}</div>
<div class="dark">{{ $profileFormatCount($profileMomentsCount) }}</div>
<div class="hatch">{{ $profileFormatCount($profileFriendsTotal) }}</div>
<div class="grey">{{ $profileFormatCount($profileRocks) }}</div>
</div>
</section>
