<section class="cups-overview">
<div class="cups-heading">
<span>{{ __('hnt_cups_overview.eyebrow') }}</span>
<h1>{{ __('hnt_cups_overview.title') }}</h1>
<div class="cups-meta">
<span class="live"><i></i>{{ $formatCount((int) $stats['active']) }} {{ __('hnt_cups_overview.active') }}</span>
<span>{{ __('hnt_cups_overview.solo_and_teams') }}</span>
<span>{{ __('hnt_cups_overview.fair_scoring') }}</span>
<span>{{ __('hnt_cups_overview.manual_review') }}</span>
</div>
<p>{{ __('hnt_cups_overview.intro') }}</p>
<div class="cups-bars">
<div class="cups-summary-bar wide">
<span>{{ __('hnt_cups_overview.active_cups') }}</span>
<div class="dark"><b>{{ $formatCount((int) $stats['active']) }} / {{ $formatCount($trackedCupCount) }}</b></div>
</div>
<div class="cups-summary-bar">
<span>{{ __('hnt_cups_overview.planned') }}</span>
<div class="yellow"><b>{{ $formatCount((int) $stats['planned']) }}</b></div>
</div>
<div class="cups-summary-bar">
<span>{{ __('hnt_cups_overview.finished') }}</span>
<div class="striped"><b>{{ $formatCount((int) $stats['finished']) }}</b></div>
</div>
<div class="cups-summary-bar compact">
<span>{{ __('hnt_cups_overview.pending_reviews') }}</span>
<div class="outline"><b>{{ $formatCount((int) $stats['pending']) }}</b></div>
</div>
</div>
</div>
<div class="cups-overview-stats">
<article><strong>{{ $formatCount((int) $stats['teams']) }}</strong><span>{{ __('hnt_cups_overview.teams') }}</span></article>
<article><strong>{{ $formatCount((int) $stats['hunters']) }}</strong><span>{{ __('hnt_cups_overview.hunters') }}</span></article>
<article><strong>{{ $formatCount((int) $stats['scores']) }}</strong><span>{{ __('hnt_cups_overview.scores') }}</span></article>
</div>
</section>
