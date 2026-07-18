@php
    $profileStatColumns = 1 + ($profileActivityVisible ? 2 : 0) + ($profileGamificationVisible ? 1 : 0);
    $profileLanguageLabel = match (mb_strtolower(trim((string) ($profile?->language ?? '')))) {
        'de', 'deutsch', 'german' => __('hnt_preview.profile.language_german'),
        'en', 'english', 'englisch' => __('hnt_preview.profile.language_english'),
        default => $profile?->language,
    };
@endphp
<section class="profile-summary-card {{ $profileCoverDisplayMode === \App\Models\UserProfile::COVER_DISPLAY_ALWAYS ? 'is-cover-peek' : '' }} {{ $profileGamificationVisible ? '' : 'is-gamification-hidden' }}" data-profile-cover-peek data-cover-display-mode="{{ $profileCoverDisplayMode }}">
@if($profileCoverDisplayMode !== \App\Models\UserProfile::COVER_DISPLAY_HIDDEN)
<div aria-hidden="true" class="profile-cover-peek-layer" style="background-image:linear-gradient(180deg,rgba(251,249,238,.24) 0%,rgba(251,249,238,.38) 34%,rgba(251,249,238,.82) 72%,#fbf9ee 100%),url('{{ $profileCoverUrl }}')"></div>
@endif
<div class="profile-summary-top">
<div>
<span class="profile-eyebrow">{{ __('hnt_preview.profile.eyebrow') }}</span>
<h2>{{ $profileDisplayName }}</h2>
<p>{{ $profileHandle }} · {{ $profileIsOnline ? __('hnt_preview.profile.online') : __('hnt_preview.profile.offline') }}</p>
</div>
<div class="profile-summary-actions">
@if($profileMessageUrl)
<a aria-label="{{ __('hnt_preview.profile.message_to', ['name' => $profileDisplayName]) }}" data-hnt-chat-tab-open data-hnt-chat-tab-url="{{ $profileMessageUrl }}" href="{{ $profileMessageUrl }}"><svg><use href="#i-comment"></use></svg></a>
@endif
<button aria-label="{{ __('hnt_preview.profile.share_profile') }}" data-profile-share type="button"><svg><use href="#i-share"></use></svg></button>
</div>
</div>
<div class="profile-tags">
@if($profile?->platform)<span class="yellow">{{ $profile->platform }}</span>@endif
@if($profile?->region)<span class="blue">{{ $profile->region }}</span>@endif
@if($profileLanguageLabel)<span class="purple">{{ $profileLanguageLabel }}</span>@endif
@if($profile?->playstyle)<span class="green">{{ $profile->playstyle }}</span>@endif
</div>
<div class="profile-summary-body">
<div class="profile-bio">
<p>{{ $profileBio }}</p>
<div class="profile-bio-points">
<span><b>{{ __('hnt_preview.profile.role') }}</b> {{ $profile?->hunt_role ?: '—' }}</span>
<span><b>LFG</b> {{ $profile?->is_lfg_available ? __('hnt_preview.profile.lfg_open') : __('hnt_preview.profile.lfg_closed') }}</span>
<span><b>Discord</b> {{ $profile?->discord_name ?: '—' }}</span>
</div>
@if($profileHasSocialLinks)
<nav class="profile-social-bar" aria-label="Social Links">
@if($profileSteamUrl)
<a class="profile-social-link steam" href="{{ $profileSteamUrl }}" target="_blank" rel="noopener noreferrer"><span class="profile-social-brand">S</span><span>Steam</span><small>Community</small></a>
@endif
@if($profileTwitchUrl)
<button aria-label="Twitch-Bereich von {{ $profileDisplayName }} öffnen" class="profile-social-link twitch profile-twitch-summary-button {{ $profileTwitchIsLive ? 'is-live' : '' }}" data-open-profile-twitch data-profile-display-name="{{ $profileDisplayName }}" data-profile-twitch-link data-twitch-status="{{ $profileTwitchStatusState }}" type="button"><i class="profile-social-live-dot"></i><span class="profile-social-brand">T</span><span data-profile-twitch-name>{{ $profileTwitchIsLive ? $profileDisplayName.' ist live' : 'Twitch' }}</span><small data-profile-twitch-label>{{ $profileTwitchStatusLabel }}</small></button>
@endif
@if($profileYoutubeUrl)
<a class="profile-social-link youtube" href="{{ $profileYoutubeUrl }}" target="_blank" rel="noopener noreferrer"><span class="profile-social-brand">Y</span><span>YouTube</span><small>Videos</small></a>
@endif
</nav>
@endif
</div>
@if($profileGamificationVisible)
<div class="profile-level-ring" style="--profile-level-progress:{{ $profileLevelProgress }}%">
<div><strong>{{ $profileLevel }}</strong><span>{{ __('hnt_preview.profile.level') }}</span></div>
</div>
@endif
</div>
<div class="profile-stat-labels is-adaptive" style="grid-template-columns:repeat({{ $profileStatColumns }},minmax(0,1fr))">
@if($profileActivityVisible)<span>{{ __('hnt_preview.profile.posts') }}</span><span>{{ __('hnt_preview.profile.moments') }}</span>@endif
<span>{{ __('hnt_preview.profile.friends') }}</span>
@if($profileGamificationVisible)<span>{{ __('hnt_preview.profile.rocks') }}</span>@endif
</div>
<div class="profile-stat-pipeline is-adaptive" style="grid-template-columns:repeat({{ $profileStatColumns }},minmax(0,1fr))">
@if($profileActivityVisible)<div class="yellow">{{ $profileFormatCount($profilePostsCount) }}</div><div class="dark">{{ $profileFormatCount($profileMomentsCount) }}</div>@endif
<div class="hatch">{{ $profileFormatCount($profileFriendsTotal) }}</div>
@if($profileGamificationVisible)<div class="grey">{{ $profileFormatCount($profileRocks) }}</div>@endif
</div>
</section>
