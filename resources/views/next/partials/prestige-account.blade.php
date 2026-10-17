<div class="account-rank">
  <div class="account-rank-title"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg> <strong>{{ __('prestige.menu_title') }}</strong></span><b>{{ $prestige['prestige'] }}</b></div>
  <div class="account-rank-track" role="progressbar" aria-label="{{ __('prestige.progress_label') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $prestigeProgress }}"><span style="width:{{ $prestigeProgress }}%"></span></div>
  <div class="account-stats"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-clock"></use></svg> <strong>{{ $prestige['level'] }}/50</strong> <small>{{ __('prestige.level') }}</small></span><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-lightning"></use></svg> <strong>{{ number_format($prestige['career_xp'], 0, ',', '.') }}</strong> <small>XP</small></span></div>
</div>
