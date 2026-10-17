<section class="widget hunter-widget hnt-prestige-widget" id="hntPrestigeWidget" aria-label="{{ __('prestige.profile_title') }}">
  <div class="widget-top"><h2>{{ __('prestige.profile_title') }}</h2><a href="{{ route('profile.show') }}">{{ __('prestige.profile_link') }} <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div>
  <div class="hunter-profile">
    <img class="hunter-pic" src="{{ $viewer->avatarUrl() }}" alt=""/>
    <div class="hunter-profile-copy"><strong>{{ $viewer->name }}</strong><small><span class="online-dot"></span> {{ __('prestige.online') }}</small><span class="rank-sub" id="hntPrestigeRank">{{ __('prestige.menu_title') }} {{ $prestige['prestige'] }} · {{ __('prestige.level') }} {{ $prestige['level'] }}</span></div>
    <div class="hnt-prestige-emblem" aria-label="{{ __('prestige.menu_title') }} {{ $prestige['prestige'] }}"><img class="hunter-emblem" src="/build/next/hntv3-assets/prestige-skull.png" alt=""/><span id="hntPrestigeEmblemNumber">{{ $prestige['prestige'] }}</span></div>
  </div>
  <div class="hunter-progress hnt-prestige-progress">
    <span id="hntPrestigeProgressLabel">{{ __('prestige.level') }} {{ $prestige['level'] }} / 50</span><span id="hntPrestigeProgressPercent">@if($prestige['can_prestige']){{ __('prestige.max_level') }}@else{{ $prestigeProgress }} % @endif</span>
    <div role="progressbar" aria-label="{{ __('prestige.progress_label') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $prestigeProgress }}" id="hntPrestigeProgressBar"><i id="hntPrestigeProgressFill" style="width:{{ $prestigeProgress }}%"></i></div>
  </div>
  <p class="hnt-prestige-status" id="hntPrestigeStatus"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-seal-check"></use></svg><span>@if($prestige['can_prestige']){{ __('prestige.level_reached') }}@elseif($prestige['xp_boost_percent'] > 0){{ __('prestige.boost_active') }}@else{{ __('prestige.xp_missing') }} {{ max(0, ($prestige['level'] * 250) - $prestige['cycle_xp']) }} XP @endif</span></p>
  <div class="hunter-mini-stats hnt-prestige-stats">
    <div><small><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg> {{ __('prestige.prestige_count') }}</small><strong>{{ $prestige['prestige'] }}</strong></div>
    <div><small><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-lightning"></use></svg> {{ __('prestige.career_xp') }}</small><strong>{{ number_format($prestige['career_xp'], 0, ',', '.') }} <em>XP</em></strong></div>
  </div>
  <div class="hnt-prestige-action">
    <button type="button" id="hntPrestigeStart" class="hnt-prestige-start" aria-haspopup="dialog" aria-controls="hntPrestigeDialog">
      <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg>
      <span id="hntPrestigeStartText">@if($prestige['can_prestige'] && config('hnt_prestige.enabled')){{ __('prestige.start') }} {{ $prestige['prestige'] + 1 }}@else{{ __('prestige.preview') }}@endif</span>
      <svg aria-hidden="true" class="ph hnt-prestige-arrow" viewBox="0 0 256 256"><use href="#ph-arrow-right"></use></svg>
    </button>
    <small id="hntPrestigeHint">{{ __('prestige.hint') }}</small>
  </div>
</section>
