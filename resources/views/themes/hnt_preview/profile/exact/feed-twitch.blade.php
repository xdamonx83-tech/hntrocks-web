@if($profileTwitchChannel)
<section class="profile-tab-panel profile-twitch-panel" data-profile-panel="twitch" hidden id="profileTabTwitch" role="tabpanel">
<div class="profile-twitch-layout" data-profile-twitch-panel data-channel="{{ $profileTwitchChannel }}" data-parent="{{ $profileTwitchParent }}" data-channel-url="{{ $profileTwitchUrl }}">
<article class="profile-twitch-player-card">
<header>
<div><span>TWITCH LIVE</span><h3>{{ $profileDisplayName }} auf Twitch</h3></div>
<span class="profile-twitch-status" data-profile-twitch-status>Wird geprüft</span>
</header>
<div class="profile-twitch-player-shell">
<div class="profile-twitch-player" id="profileTwitchPlayer">
<div class="profile-twitch-loading"><span></span><strong>Stream wird vorbereitet</strong><small>Der Player startet nicht automatisch.</small></div>
</div>
</div>
<div class="profile-twitch-mobile-fallback">
<strong>Twitch-Stream öffnen</strong>
<p>Auf sehr schmalen Displays öffnet sich der Stream direkt bei Twitch.</p>
<a href="{{ $profileTwitchUrl }}" target="_blank" rel="noopener noreferrer">Auf Twitch ansehen ↗</a>
</div>
</article>
<aside class="profile-twitch-info-card">
<span class="profile-social-brand twitch">T</span>
<div><span>VERBUNDENER KANAL</span><h3>{{ $profileTwitchChannel }}</h3><p>Live-Status, Stream und Kanalzugriff an einem Ort.</p></div>
<a href="{{ $profileTwitchUrl }}" target="_blank" rel="noopener noreferrer">Kanal öffnen ↗</a>
</aside>
</div>
</section>
@endif
