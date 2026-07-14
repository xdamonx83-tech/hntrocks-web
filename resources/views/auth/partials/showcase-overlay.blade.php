@php
  $showcaseStats = array_merge([
    'members' => 0,
    'posts' => 0,
    'moments' => 0,
    'maps' => 0,
  ], is_array($authShowcaseStats ?? null) ? $authShowcaseStats : []);

  $formatShowcaseNumber = static function (int $value): string {
    $isGerman = app()->getLocale() === 'de';

    return number_format($value, 0, $isGerman ? ',' : '.', $isGerman ? '.' : ',');
  };

  $showcaseItems = [
    ['key' => 'members', 'index' => '01', 'label' => __('hnt_showcase.members')],
    ['key' => 'posts', 'index' => '02', 'label' => __('hnt_showcase.posts')],
    ['key' => 'moments', 'index' => '03', 'label' => __('hnt_showcase.moments')],
    ['key' => 'maps', 'index' => '04', 'label' => __('hnt_showcase.maps')],
  ];
@endphp

<section class="auth-showcase" aria-label="{{ __('hnt_showcase.stats_label') }}">
  <div class="auth-showcase-copy">
    <span class="auth-showcase-kicker">{{ __('hnt_showcase.kicker') }}</span>
    <h2>{{ __('hnt_showcase.title') }}</h2>
    <p>{{ __('hnt_showcase.text') }}</p>
  </div>

  <div class="auth-showcase-stats">
    @foreach ($showcaseItems as $item)
      <article class="auth-showcase-stat auth-showcase-stat-{{ $item['key'] }}">
        <span>{{ $item['index'] }}</span>
        <strong>{{ $formatShowcaseNumber((int) $showcaseStats[$item['key']]) }}</strong>
        <small>{{ $item['label'] }}</small>
      </article>
    @endforeach
  </div>

  <div class="auth-showcase-features">{{ __('hnt_showcase.features') }}</div>
</section>
