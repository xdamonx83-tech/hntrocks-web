@php
    $status = $securityStatus;
@endphp
<aside class="settings-status-card" data-real-security-status>
<header>
<div><span>{{ $status['labels']['eyebrow'] }}</span><h2>{{ $status['labels']['title'] }}</h2></div>
<a aria-label="{{ $status['labels']['open_security'] }}" data-settings-shortcut="security" href="#security"><svg><use href="#i-arrow"></use></svg></a>
</header>
<div class="settings-score-ring" style="--score:{{ $status['score'] }}">
<div><strong>{{ $status['score'] }}%</strong><span>{{ $status['score_label'] }}</span></div>
</div>
<div class="settings-status-list">
<article>
<span class="status-dot {{ $status['password']['good'] ? 'good' : '' }}"></span>
<div><strong>{{ $status['labels']['password'] }}</strong><small>{{ $status['password']['text'] }}</small></div>
<b>{{ $status['password']['badge'] }}</b>
</article>
<article>
<span class="status-dot {{ $status['two_factor']['good'] ? 'good' : '' }}"></span>
<div><strong>{{ $status['labels']['two_factor'] }}</strong><small>{{ $status['two_factor']['text'] }}</small></div>
<b>{{ $status['two_factor']['badge'] }}</b>
</article>
<article>
<span class="status-dot {{ $status['profile']['good'] ? 'good' : '' }}"></span>
<div><strong>{{ $status['labels']['profile'] }}</strong><small>{{ $status['profile']['text'] }}</small></div>
<b>{{ $status['profile']['badge'] }}</b>
</article>
<article>
<span class="status-dot {{ $status['messages']['good'] ? 'good' : '' }}"></span>
<div><strong>{{ $status['labels']['messages'] }}</strong><small>{{ $status['messages']['text'] }}</small></div>
<b>{{ $status['messages']['badge'] }}</b>
</article>
</div>
<section class="settings-session-card">
<div><span>{{ $status['labels']['sessions'] }}</span><strong>{{ $status['session_count'] }}</strong></div>
@foreach($status['sessions'] as $session)
<article>
<span class="session-device"><svg><use href="{{ $session['type'] === 'app' ? '#i-phone' : '#i-settings' }}"></use></svg></span>
<div><strong>{{ $session['title'] }}</strong><small>{{ $session['subtitle'] }}</small></div>
<i></i>
</article>
@endforeach
</section>
<button class="settings-status-save" id="settingsStatusSave" type="button">{{ $status['labels']['save'] }}</button>
</aside>

{{-- Keep the unfinished demo panels from mutating the visible real widget. --}}
<div hidden aria-hidden="true" data-settings-status-demo-compat>
<div id="settingsScoreRing"><strong id="settingsScoreValue">{{ $status['score'] }}%</strong></div>
<span id="twoFactorStatusDot"></span>
<span id="twoFactorStatusText"></span>
<span id="twoFactorStatusBadge"></span>
<span id="privacyStatusText"></span>
<span id="messagesStatusText"></span>
</div>
