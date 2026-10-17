@php
    $wt = \App\Http\Controllers\Next\NextWidgetText::class;
    $activity = $widgets['activity'];
    $spotlight = $widgets['spotlight'];
    $spotlightMoment = $spotlight?->moment;
    $cup = $widgets['cup'];
    $loadouts = $widgets['loadouts'];
@endphp

<section class="widget activity-widget" aria-labelledby="activityWidgetTitle">
  <div class="widget-top activity-widget-head">
    <h2 id="activityWidgetTitle">{{ $wt::get('activity') }}</h2>
    @if($activity->count() > 3)
      <button class="activity-widget-toggle" type="button" aria-controls="activityWidgetMore" aria-expanded="false"
              data-activity-toggle data-more-label="{{ $wt::get('more') }}" data-less-label="{{ $wt::get('less') }}">
        <span>{{ $wt::get('more') }}</span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-right"></use></svg>
      </button>
    @endif
  </div>
  <ul class="activity-widget-list" aria-label="{{ $wt::get('activity') }}">
    @forelse($activity->take(3) as $item)
      <li class="activity-widget-item">
        <a class="hnt-widget-activity-link" href="{{ $item['url'] }}">
          <span class="activity-avatar activity-avatar-photo">
            @if($item['avatar'])<img src="{{ $item['avatar'] }}" alt="" loading="lazy"/>@else<span class="hnt-widget-avatar-fallback">H</span>@endif
          </span>
          <span class="activity-item-copy">
            <strong>{{ $item['name'] }} {{ $wt::get(match ($item['type']) {'joined'=>'joined','moment'=>'moment',default=>'posted'}) }}</strong>
            <span>{{ $wt::get(match ($item['type']) {'joined'=>'new_member','moment'=>'new_moment',default=>'new_post'}) }}</span>
            <time>{{ $item['time'] }}</time>
          </span>
        </a>
      </li>
    @empty
      <li class="hnt-widget-empty">{{ $wt::get('empty_activity') }}</li>
    @endforelse
  </ul>
  <ul class="activity-widget-list activity-widget-more" id="activityWidgetMore" aria-label="{{ $wt::get('more') }}" hidden>
    @foreach($activity->slice(3) as $item)
      <li class="activity-widget-item">
        <a class="hnt-widget-activity-link" href="{{ $item['url'] }}">
          <span class="activity-avatar activity-avatar-photo">
            @if($item['avatar'])<img src="{{ $item['avatar'] }}" alt="" loading="lazy"/>@else<span class="hnt-widget-avatar-fallback">H</span>@endif
          </span>
          <span class="activity-item-copy">
            <strong>{{ $item['name'] }} {{ $wt::get(match ($item['type']) {'joined'=>'joined','moment'=>'moment',default=>'posted'}) }}</strong>
            <span>{{ $wt::get(match ($item['type']) {'joined'=>'new_member','moment'=>'new_moment',default=>'new_post'}) }}</span>
            <time>{{ $item['time'] }}</time>
          </span>
        </a>
      </li>
    @endforeach
  </ul>
</section>

<section class="widget hnt-weekly-moment-widget" aria-labelledby="hntWeeklyMomentTitle">
  <div class="widget-top hnt-weekly-moment-head">
    <h2 id="hntWeeklyMomentTitle"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-film-strip"></use></svg> {{ $wt::get('weekly') }}</h2>
    <a href="{{ route('moments.index') }}">{{ $wt::get('all_moments') }} <svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-up-right"></use></svg></a>
  </div>
  @if($spotlightMoment)
    <a class="hnt-weekly-moment-preview" href="{{ route('moments.show', $spotlightMoment) }}">
      <img src="{{ $spotlightMoment->coverUrl() }}" alt="" loading="lazy"/>
      <span class="hnt-weekly-moment-tag"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-sparkle"></use></svg> COMMUNITY HIGHLIGHT</span>
      <span class="hnt-weekly-moment-play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5a1 1 0 0 1 1.52-.85l10 6.5a1 1 0 0 1 0 1.7l-10 6.5A1 1 0 0 1 8 18.5z"/></svg></span>
      <span class="hnt-weekly-moment-preview-footer"><span>{{ $wt::get('moment_discover') }}</span><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-up-right"></use></svg></span>
    </a>
    <div class="hnt-weekly-moment-info"><div class="hnt-weekly-moment-author">
      <img src="{{ $spotlightMoment->user?->avatarUrl() }}" alt="" loading="lazy"/>
      <span><strong>{{ $spotlight->title ?: $spotlightMoment->caption ?: $wt::get('weekly') }}</strong><small>{{ $spotlightMoment->user?->name ?: 'Hunter' }}</small></span>
    </div></div>
  @else
    <div class="hnt-widget-empty hnt-widget-spotlight-empty">{{ $wt::get('weekly_empty') }}</div>
  @endif
  <div class="hnt-weekly-moment-foot"><span><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-trophy"></use></svg> {{ $wt::get('clip') }}</span><a href="{{ route('moments.index') }}">{{ $wt::get('all_moments') }} <svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-right"></use></svg></a></div>
</section>

<section class="widget latest-loadouts-widget" aria-labelledby="latestLoadoutsTitle">
  <div class="widget-top latest-loadouts-header">
    <h2 id="latestLoadoutsTitle"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg> {{ $wt::get('loadouts') }}</h2>
  </div>
  <div class="latest-loadouts-body">
    @forelse($loadouts as $loadout)
      <a class="latest-loadout-compact hnt-widget-loadout-link" href="{{ route('profile.public', $loadout->user) }}">
        <span class="latest-loadout-mini-art"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg></span>
        <span class="latest-loadout-mini-copy">
          <strong>{{ $loadout->title }}</strong>
          <small>{{ $loadout->user?->name }} · {{ $loadout->primary_weapon ?: $wt::get('no_weapons') }}@if($loadout->secondary_weapon) / {{ $loadout->secondary_weapon }}@endif</small>
        </span>
        <svg aria-hidden="true" class="ph latest-loadout-mini-chevron" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg>
      </a>
    @empty
      <div class="hnt-widget-empty">{{ $wt::get('loadouts_empty') }}</div>
    @endforelse
  </div>
  <div class="latest-loadout-footer"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-target"></use></svg> {{ $wt::get('loadouts_foot') }}</span><span>{{ $loadouts->count() }} {{ $wt::get('entries') }}</span></div>
</section>

<section class="widget cup-widget">
  <div class="widget-top cup-widget-heading">
    <h2><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg> {{ $wt::get('cup') }}</h2>
    <a href="{{ route('cups.index') }}">{{ $wt::get('all_cups') }} <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
  </div>
  @if($cup)
    <a class="cup-art hnt-widget-cup-art" href="{{ route('cups.show', $cup) }}">
      <img src="{{ $cup->coverUrl() }}" alt="" loading="lazy"/>
      <div class="cup-copy"><span class="cup-kicker">{{ $cup->statusLabel() }}</span><strong>{{ $cup->title }}</strong><small>{{ \Illuminate\Support\Str::limit(strip_tags((string) $cup->summary), 100) }}</small></div>
    </a>
    <a class="cup-join" href="{{ route('cups.show', $cup) }}">{{ $wt::get('cup_open') }} <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
  @else
    <p class="hnt-widget-empty">{{ $wt::get('cup_empty') }}</p>
  @endif
</section>

<section class="widget guides-widget">
  <div class="widget-top"><h2>{{ $wt::get('guide_title') }}</h2><a href="/guides">{{ $wt::get('all_guides') }} <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div>
  <div class="guide-list">
    @forelse($widgets['guides'] as $guide)
      <a class="guide-item" href="{{ $guide['url'] }}">
        @if($guide['cover_url'])
          <img src="{{ $guide['cover_url'] }}" alt="" loading="lazy"/>
        @else
          <span class="hnt-guide-image-fallback"><svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-book-open"></use></svg></span>
        @endif
        <span>
          <strong>{{ $guide['title'] }}</strong>
          <small>{{ \Illuminate\Support\Str::limit(strip_tags($guide['summary']), 62) }}</small>
          <em>{{ \Illuminate\Support\Str::upper($guide['category']) }}@if($guide['helpful_count']) · {{ $guide['helpful_count'] }} ♥@endif</em>
        </span>
      </a>
    @empty
      <div class="hnt-widget-empty">{{ $wt::get('guide_intro') }} <a class="hnt-guide-list-empty-link" href="/guides">{{ $wt::get('all_guides') }} →</a></div>
    @endforelse
  </div>
</section>
<section class="widget lobby-widget">
  <div class="widget-top"><h2><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg> {{ $wt::get('lobby') }}</h2><a href="/ready-lobbies">{{ $wt::get('lobby_join') }} <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div>
  <div class="lobby-body"><div class="lobby-signal"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg></div>
    <div><strong>{{ $widgets['openLobbies'] }} {{ $wt::get('lobby_open') }}</strong><p>{{ $widgets['openLobbies'] ? $wt::get('lobby_join') : $wt::get('lobby_empty') }}</p></div>
  </div>
  <a class="lobby-cta" href="/ready-lobbies">{{ $wt::get('lobby_join') }} <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
</section>
