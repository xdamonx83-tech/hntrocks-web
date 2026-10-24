@php
    $nextPageTitle = $pageTitle;
    $nextActivePage = 'news';
    $hntPublicNews = true;
    $viewerCosmetics = $viewer ? \App\Support\CrownCosmetics::forUser($viewer) : ['avatar_frame_class'=>'', 'username_effect_class'=>'', 'like_effect_key'=>''];
    $prestige = $viewer ? app(\App\Services\HunterPrestigeService::class)->status($viewer) : null;
    $prestigeProgress = $viewer ? app(\App\Services\GamificationService::class)->progressPercent($viewer) : 0;
    $nextNewsHome = $preview ? url('/next/news/'.$locale) : route('news.overview.locale', ['locale'=>$locale]);
    $newsDate = static function (?string $iso, string $locale): string {
        if (!$iso) return '';
        try {
            $date = \Carbon\Carbon::parse($iso)->locale($locale);
            return $date->translatedFormat('j. M Y');
        } catch (\Throwable $e) { return ''; }
    };
    $newsSvg = static fn (string $name): string => '<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'.e($name).'"></use></svg>';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
@if($viewer)
@include('next.partials.shell.head')
@else
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0d0d0d">
<title>{{ $pageTitle }}</title>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@vite(['resources/css/next/hntv3.css'], 'build/next')
@endif
@if($preview)
<meta name="robots" content="noindex,follow">
@else
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">
@endif
<meta name="description" content="{{ $pageDescription }}">
<link rel="canonical" href="{{ $canonical }}">
@foreach($alternates as $al => $href)
<link rel="alternate" hreflang="{{ $al }}" href="{{ $href }}">
@endforeach
@if(count($alternates))
<link rel="alternate" hreflang="x-default" href="{{ $alternates['en'] ?? reset($alternates) }}">
@endif
<meta property="og:site_name" content="HNT.ROCKS">
<meta property="og:type" content="{{ $mode === 'article' ? 'article' : 'website' }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary_large_image">
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) !!}</script>
<link rel="stylesheet" href="/build/next/hntv3-js/{{ $mode === 'article' ? 'news-detail' : 'news-hub' }}.css?v=hntv3-news-20261024a">
<link rel="stylesheet" href="/build/next/hntv3-js/hnt-news-live.css?v=hntv3-news-20261024a">
<script defer src="/build/next/hntv3-js/hnt-news-live.js?v=hntv3-news-20261024a"></script>
</head>
<body class="{{ $viewer ? 'hnt-news-signed' : 'hnt-news-guest' }} {{ $mode === 'article' ? 'hnt-news-detail-shell' : 'hnt-news-shell' }}"
@if($viewer) data-next-viewer-name="{{ $viewer->name }}" data-next-viewer-avatar="{{ $viewer->avatarUrl() }}"
data-next-viewer-profile="{{ $viewer->username ? url('/u/'.$viewer->username) : '' }}"
data-next-like-effect="{{ \App\Support\CrownCosmetics::forUser($viewer)['like_effect_key'] }}" @endif>
@include('next.partials.shell.icons')
@if($viewer)
<script>
window.HNTPrestigeData = @json($prestige);
window.HNTPrestigeEnabled = @json(config('hnt_prestige.enabled'));
window.HNTPrestigeWords = @json(trans('prestige'));
window.HNTPrestigeProgress = @json($prestigeProgress);
</script>
@endif
@if($viewer)
@include('next.partials.shell.sidebar')
@include('next.partials.shell.topbar')
@else
<header class="hnt-news-public-top" aria-label="HNT.ROCKS">
<a href="/" class="hnt-news-guest-brand" aria-label="HNT.ROCKS Start">HR:<span>\</span> <strong>HNT.ROCKS</strong></a>
<nav aria-label="Hauptnavigation">
<a href="/maps">Maps</a><a href="{{ $nextNewsHome }}" aria-current="page">News</a><a href="/guides">Guides</a>
</nav>
<div class="hnt-news-guest-actions"><a href="/login">Anmelden</a><a href="/register">Hunter werden</a></div>
</header>
@endif
@if($mode === 'overview')
<main class="main-content feed-main hnt-news-page" id="newsTop">
 <div class="news-wrap">
 <header class="feed-page-title news-pagehead">
  <div><div class="feed-kicker news-kicker"><span class="feed-live-signal" aria-hidden="true"></span> HNT.ROCKS / NEWSROOM</div>
  <h1>{{ $labels['title'] }}<span class="title-ember">.</span></h1>
  <p>{{ $labels['description'] }}</p></div>
  <span class="news-head-edition">{!! $newsSvg('file-text') !!} HUNTER JOURNAL <b>/</b> 1896</span>
 </header>
 @if($featured)
 <section class="news-lead-grid" aria-label="{{ $labels['recent'] }}">
 <article class="news-featured">
  <a class="news-featured-image" href="{{ $featured['url'] }}">
    @if(!empty($featured['hero_media']['url']))
    <img src="{{ $featured['hero_media']['url'] }}" alt="{{ $featured['hero_media']['alt_text'] ?: $featured['title'] }}" fetchpriority="high">
    @else <span class="hnt-news-img-placeholder">HNT.ROCKS / NEWS</span> @endif
    <span class="news-editor-pick">{!! $newsSvg('seal-check') !!} {{ $labels['featured'] }}</span>
  </a>
  <div class="news-featured-body">
   <div class="news-featured-meta"><span class="news-avatar"><img src="/build/next/hntv3-assets/avatar-hnt.webp" alt=""></span><strong>HNT.ROCKS</strong><i></i>
   <span>{{ ucfirst($featured['category_key'] ?? 'News') }}</span><time datetime="{{ $featured['published_at'] }}">{{ $newsDate($featured['published_at'], $locale) }}</time></div>
   <h2><a href="{{ $featured['url'] }}">{{ $featured['title'] }}</a></h2>
   <p>{{ $featured['excerpt'] }}</p>
   <a class="news-more" href="{{ $featured['url'] }}">{{ $labels['read'] }} {!! $newsSvg('arrow-up-right') !!}</a>
  </div>
 </article>
 <aside class="news-lead-sidebar">
  <section class="news-brief-list">
   <div class="news-rail-heading"><div><span class="news-eyebrow">AUS DEM BAYOU</span><h2>{{ $labels['recent'] }}<span class="title-ember">.</span></h2></div><span class="news-count">{{ str_pad((string)$articles->count(), 2, '0', STR_PAD_LEFT) }}</span></div>
   @foreach($briefs as $story)
    <article class="news-side-story">
     <a class="news-side-image" href="{{ $story['url'] }}">
      @if(!empty($story['hero_media']['url']))<img src="{{ $story['hero_media']['url'] }}" alt="" loading="lazy">@else<span class="hnt-news-img-placeholder">HNT.ROCKS</span>@endif
     </a>
     <div class="news-side-story-copy">
      <span class="news-side-category">{{ ucfirst($story['category_key'] ?? 'News') }}</span>
      <h3><a href="{{ $story['url'] }}">{{ $story['title'] }}</a></h3>
      <div class="news-side-meta"><span>{!! $newsSvg('clock') !!} {{ $story['read_minutes'] }} {{ $labels['minutes'] }}</span><time datetime="{{ $story['published_at'] }}">{{ $newsDate($story['published_at'], $locale) }}</time></div>
     </div>
    </article>
   @endforeach
  </section>
  <section class="news-trends">
   <div class="news-rail-heading"><div><span class="news-eyebrow">HUNTER JOURNAL</span><h2>{{ $labels['trending'] }}<span class="title-ember">.</span></h2></div>{!! $newsSvg('lightning') !!}</div>
   <div class="news-trend-tags">
    @foreach($tags as $tag)<button class="news-tag" data-tag="{{ $tag }}" type="button">#{{ $tag }}</button>@endforeach
   </div>
   <div class="news-newsroom"><img src="/build/next/hntv3-assets/avatar-hnt.webp" alt=""><div><strong>{{ $labels['redaction'] }}</strong><span>HUNT: SHOWDOWN 1896</span></div><span class="news-newsroom-icon">{!! $newsSvg('newspaper') !!}</span></div>
  </section>
 </aside>
 </section>
 @endif
 <section class="news-archive" id="newsArchive">
  <div class="news-archive-heading"><div><span class="news-eyebrow">HNT.ROCKS / NEWS ARCHIVE</span><h2>{{ $labels['archive'] }}<span class="title-ember">.</span></h2><p>{{ $labels['archiveIntro'] }}</p></div><div class="news-archive-counter"><span id="newsShownCount">{{ $articles->count() }}</span> / {{ $articles->count() }}</div></div>
  <div class="news-toolbar">
   <div class="news-filter-list" role="group" aria-label="Kategorien">
    <button type="button" class="news-filter is-active" data-filter="all" aria-pressed="true">{{ $labels['catAll'] }}</button>
    @foreach($categories as $cat)
    <button type="button" class="news-filter" data-filter="{{ $cat }}" aria-pressed="false">{{ ucfirst($cat) }}</button>
    @endforeach
   </div>
   <label class="news-search" for="newsSearch">{!! $newsSvg('magnifying-glass') !!}<input type="search" id="newsSearch" placeholder="{{ $labels['search'] }}" autocomplete="off"></label>
  </div>
  <div class="news-current-filter" id="newsActiveFilters" hidden><span id="newsFilterLabel"></span><button type="button" id="newsClearFilters">✕</button></div>
  <div class="news-article-grid" id="newsGrid">
   @foreach($articles as $story)
    <article class="news-card" data-article data-category="{{ $story['category_key'] ?? 'news' }}"
       data-tags="{{ implode('|', $story['tags'] ?? []) }}" data-title="{{ mb_strtolower($story['title']) }}">
     <a href="{{ $story['url'] }}" class="news-card-image">
      @if(!empty($story['hero_media']['url']))<img src="{{ $story['hero_media']['url'] }}" alt="{{ $story['hero_media']['alt_text'] ?: $story['title'] }}" loading="lazy">@else <span class="hnt-news-img-placeholder">HNT.ROCKS</span>@endif
      <span class="news-card-badge">{{ ucfirst($story['category_key'] ?? 'News') }}</span>
     </a>
     <div class="news-card-copy">
       <div class="news-card-byline"><span class="news-avatar"><img src="/build/next/hntv3-assets/avatar-hnt.webp" alt=""></span><span>HNT.ROCKS</span><i></i><span>{{ $newsDate($story['published_at'], $locale) }}</span></div>
       <h3><a href="{{ $story['url'] }}">{{ $story['title'] }}</a></h3>
       <p>{{ $story['excerpt'] }}</p>
       <div class="news-card-footer"><span>{!! $newsSvg('clock') !!} {{ $story['read_minutes'] }} {{ $labels['minutes'] }}</span><a href="{{ $story['url'] }}" aria-label="{{ $story['title'] }}">{!! $newsSvg('arrow-up-right') !!}</a></div>
     </div>
    </article>
   @endforeach
  </div>
  <div class="news-empty" id="newsEmpty" hidden><strong>{{ $labels['empty'] }}</strong><button type="button" id="newsEmptyReset">{{ $labels['catAll'] }}</button></div>
  @if(($meta['last_page'] ?? 1) > 1)
    <nav class="hnt-news-pagination" aria-label="Weitere News-Seiten">
      @if(($meta['current_page'] ?? 1) > 1)<a href="{{ request()->fullUrlWithQuery(['page' => ($meta['current_page']-1)]) }}">← {{ $labels['archive'] }}</a>@endif
      <span>{{ $meta['current_page'] }} / {{ $meta['last_page'] }}</span>
      @if(($meta['current_page'] ?? 1) < $meta['last_page'])<a href="{{ request()->fullUrlWithQuery(['page' => ($meta['current_page']+1)]) }}">{{ $labels['archive'] }} →</a>@endif
    </nav>
  @endif
 </section>
 <footer class="news-local-foot">HNT.ROCKS · <a href="/impressum">Impressum</a> · <a href="/datenschutz">Datenschutz</a></footer>
 </div>
</main>
@else
<main class="main-content feed-main nd-page" id="newsArticle" data-news-article-id="{{ $article['id'] }}" data-news-auth="{{ $viewer ? '1' : '0' }}">
 <div class="nd-progress" id="ndProgress" role="progressbar" aria-label="Lesefortschritt" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="ndProgressFill"></span></div>
 <div class="nd-wrap">
  <header class="nd-page-heading"><div><div class="feed-kicker nd-kicker"><span class="feed-live-signal"></span> HNT.ROCKS / NEWS / DETAIL</div><h2>Hunter Journal<span class="nd-title-dot">.</span></h2></div><a href="{{ $nextNewsHome }}" class="nd-back">{!! $newsSvg('arrow-left') !!} {{ $labels['back'] }}</a></header>
  <div class="nd-layout">
   <div class="nd-main-column">
    <article class="nd-story" id="ndStory">
      <section class="nd-hero {{ empty($article['hero_media']['url']) ? 'hnt-news-no-photo' : '' }}" aria-label="{{ $article['title'] }}">
       @if(!empty($article['hero_media']['url']))<img class="nd-hero-image" src="{{ $article['hero_media']['url'] }}" alt="{{ $article['hero_media']['alt_text'] ?: $article['title'] }}" fetchpriority="high">@endif
       <div class="nd-hero-shade" aria-hidden="true"></div>
       <div class="nd-hero-glass"><div class="nd-headline">
        <span class="nd-category">{!! $newsSvg('file-text') !!} {{ mb_strtoupper($article['category_key'] ?? 'NEWS') }}</span>
        <h1>{{ $article['title'] }}</h1><p class="nd-deck">{{ $pageDescription }}</p>
        <div class="nd-author-row">
         <div class="nd-author"><img src="/build/next/hntv3-assets/avatar-hnt.webp" alt=""><span><strong>{{ $labels['redaction'] }}</strong><small>{{ $newsDate($article['published_at'], $locale) }}</small></span></div>
         <div class="nd-read-duration"><span class="nd-read-ring" id="ndReadingRing">{!! $newsSvg('clock') !!}</span><span><strong id="ndRemaining">{{ $readingMinutes }} {{ $labels['minutes'] }}</strong><small>{{ $labels['reading'] }}</small></span></div>
        </div>
       </div></div>
       <div class="nd-hero-caption">{!! $newsSvg('image') !!} HUNT: SHOWDOWN 1896 <span>·</span> HNT.ROCKS NEWS</div>
      </section>
      <div class="nd-story-inner">
       <div class="nd-story-toolbar"><span>{!! $newsSvg('book-open') !!} HUNTER JOURNAL <b>/</b> {{ $labels['article'] }}</span>
        <div class="nd-share-actions">
         @if($viewer)
         <button type="button" class="nd-mini-action" id="newsLike" aria-pressed="false" title="Gefällt mir" aria-label="Artikel gefällt mir">{!! $newsSvg('heart') !!}</button>
         <button type="button" class="nd-mini-action" id="newsSave" aria-pressed="false" title="Merken" aria-label="Artikel merken">{!! $newsSvg('bookmark-simple') !!}</button>
         @endif
         <button type="button" class="nd-mini-action" id="ndShare" title="{{ $labels['share'] }}" aria-label="{{ $labels['share'] }}">{!! $newsSvg('share-fat') !!}</button>
         <a class="nd-mini-action" href="#ndComments" aria-label="{{ $labels['comments'] }}">{!! $newsSvg('chat-circle-dots') !!}</a>
        </div>
       </div>
       <div class="nd-article-text" id="ndArticleBody">
         @include('next.partials.news-article-body')
       </div>
       <div class="nd-article-end">{!! $newsSvg('seal-check') !!}<span>HNT.ROCKS / HUNTER JOURNAL</span></div>
       @if(!empty($article['tags']))
       <div class="nd-tags"><span class="nd-section-kicker">TAGS</span><div class="nd-tags-list">@foreach($article['tags'] as $tag)<a href="{{ $nextNewsHome }}?tag={{ urlencode($tag) }}">#{{ $tag }}</a>@endforeach</div></div>
       @endif
      </div>
    </article>
    <section class="nd-comments" id="ndComments">
     <div class="nd-comments-heading"><div><h2>{{ $labels['comments'] }}<span class="nd-title-dot">.</span></h2><p>HNT.ROCKS Community</p></div>{!! $newsSvg('chat-circle-dots') !!}</div>
     <div class="hnt-news-comment-list" id="newsCommentList" aria-live="polite"><span>…</span></div>
     <div class="hnt-news-comment-entry">
       @if($viewer && ($article['comments_enabled'] ?? false))
       <form id="newsCommentForm" class="hnt-news-comment-form">
         <label for="newsCommentText">{{ $labels['comments'] }}</label>
         <textarea id="newsCommentText" maxlength="5000" rows="3" required placeholder="{{ $labels['comments'] }} …"></textarea>
         <div class="hnt-news-comment-footer"><span id="newsCommentError" role="status"></span><button type="submit" class="nd-back">Kommentar senden</button></div>
       </form>
       @elseif($viewer)
       <p>{{ $locale === 'de' ? 'Kommentare sind für diesen Artikel deaktiviert.' : 'Comments are disabled for this article.' }}</p>
       @else<a class="nd-back" href="/login">{{ $labels['login'] }}</a>@endif
     </div>
    </section>
   </div>
   <aside class="nd-sidebar" aria-label="Artikel-Informationen">
    <section class="nd-side-panel">
     <span class="nd-side-kicker">{!! $newsSvg('clock') !!} HUNTER JOURNAL</span><h2>{{ $labels['reading'] }}</h2>
     <div class="nd-progress-visual"><span>{{ $labels['article'] }}</span><strong id="ndPercent">0%</strong></div>
     <div class="nd-progress-track"><i id="ndSidebarProgress"></i></div>
     <p class="nd-reading-help">{{ $readingMinutes }} {{ $labels['minutes'] }}</p>
    </section>
    @if($related->count())
    <section class="nd-side-panel">
      <span class="nd-side-kicker">{!! $newsSvg('newspaper') !!} HNT.ROCKS</span><h2>{{ $labels['related'] }}</h2>
      <div class="nd-related-items">
       @foreach($related as $other)
        <a class="nd-related-item" href="{{ $other['url'] }}">
         @if(!empty($other['hero_media']['url']))<img src="{{ $other['hero_media']['url'] }}" alt="" loading="lazy">@endif
         <span><small>{{ ucfirst($other['category_key'] ?? 'News') }}</small><strong>{{ $other['title'] }}</strong><em>{{ $newsDate($other['published_at'], $locale) }}</em></span>
        </a>
       @endforeach
      </div>
    </section>
    @endif
    <a class="nd-side-footlink" href="{{ $nextNewsHome }}">{!! $newsSvg('arrow-left') !!} {{ $labels['back'] }}</a>
   </aside>
  </div>
  <footer class="nd-footer"><a href="{{ $nextNewsHome }}">← {{ $labels['back'] }}</a><a href="/impressum">Impressum</a></footer>
 </div>
</main>
@endif
@if($viewer)
@include('next.partials.shell.utilities')
@else
</body>
@endif
</html>
