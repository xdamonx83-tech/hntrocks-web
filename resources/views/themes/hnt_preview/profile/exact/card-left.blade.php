<div class="profile-page-left">
<div class="profile-photo-shell" data-profile-cover-trigger>
<img alt="{{ $profileDisplayName }}" class="profile-page-photo" src="{{ $profileAvatarUrl }}"/>
@if($profileIsOnline)
<span aria-label="Online" class="profile-online-dot"></span>
@endif
@if($profileCoverDisplayMode !== \App\Models\UserProfile::COVER_DISPLAY_HIDDEN)
<button aria-label="{{ $profileCoverDisplayMode === \App\Models\UserProfile::COVER_DISPLAY_ALWAYS ? 'Titelbild ausblenden' : 'Titelbild anzeigen' }}" aria-pressed="{{ $profileCoverDisplayMode === \App\Models\UserProfile::COVER_DISPLAY_ALWAYS ? 'true' : 'false' }}" class="profile-cover-peek-toggle" data-profile-cover-toggle type="button">
<svg><use href="#i-eye"></use></svg>
</button>
@endif
@if($isOwnProfile)
<a aria-label="Profilbild oder Titelbild ändern" href="{{ route('profile.edit', ['tab' => 'media']) }}">
<svg><use href="#i-image"></use></svg>
</a>
@endif
</div>
<section class="profile-info-card">
<div class="profile-info-head">
<span>PROFILINFO</span>
@if($isOwnProfile)
<a aria-label="Profilinformationen bearbeiten" href="{{ route('profile.edit') }}">
<svg><use href="#i-sliders"></use></svg>
</a>
@endif
</div>
<dl>
<div><dt>Plattform</dt><dd>{{ $profile?->platform ?: '—' }}</dd></div>
<div><dt>Region</dt><dd>{{ $profile?->region ?: '—' }}</dd></div>
<div><dt>Sprache</dt><dd>{{ $profile?->language ?: '—' }}</dd></div>
<div><dt>Spielstil</dt><dd>{{ $profile?->playstyle ?: '—' }}</dd></div>
<div><dt>Mitglied seit</dt><dd>{{ $profileJoinedLabel }}</dd></div>
</dl>
</section>
<section class="profile-level-card">
<div class="profile-level-top">
<div><span>LEVEL</span><strong>{{ $profileLevel }}</strong></div>
<small>{{ $profileLevelProgress }}%</small>
</div>
<div class="profile-level-progress"><i style="width:{{ $profileLevelProgress }}%"></i></div>
<p>Noch {{ $profileFormatCount($profileXpRemaining) }} XP bis Level {{ $profileLevel + 1 }}</p>
<div class="profile-level-stats">
<span><strong>{{ $profileFormatCount($profileRocks) }}</strong><small>Rocks</small></span>
<span><strong>{{ $profileFormatCount($profileBadgesCount) }}</strong><small>Badges</small></span>
</div>
</section>
</div>
