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
    $newsReaderWords = [
      'de'=>['mode'=>'DEIN LESEMODUS','readHow'=>'Lesen, wie du möchtest','progress'=>'LESEFORTSCHRITT','estimate'=>'Geschätzte Lesezeit','size'=>'Schriftgröße','contrast'=>'Hoher Kontrast','focus'=>'Lesefokus','focusExit'=>'Lesefokus beenden','overview'=>'AUF EINEN BLICK','inArticle'=>'In diesem Artikel','toComments'=>'Zu den Kommentaren','end'=>'ENDE DES ARTIKELS','topics'=>'THEMEN DIESER STORY','enjoy'=>'Gefällt dir der Artikel?','like'=>'Gefällt mir','liked'=>'Gefällt dir','share'=>'Teilen','discuss'=>'COMMUNITY / DISKUSSION','thoughts'=>'Diskutiere über diese Meldung.','yourComment'=>'Dein Kommentar','placeholder'=>'Was denkst du über diese News?','send'=>'Kommentar veröffentlichen','discussion'=>'Diskussion','sort'=>'Sortierung','newest'=>'Neueste zuerst','oldest'=>'Älteste zuerst','noComments'=>'Noch keine Kommentare. Beginne die Diskussion.','chars'=>'Zeichen','login'=>'Zum Kommentieren anmelden','disabled'=>'Kommentare sind für diesen Artikel deaktiviert.'],
      'en'=>['mode'=>'YOUR READING MODE','readHow'=>'Read your way','progress'=>'READING PROGRESS','estimate'=>'Estimated reading time','size'=>'Text size','contrast'=>'High contrast','focus'=>'Reading focus','focusExit'=>'Exit reading focus','overview'=>'AT A GLANCE','inArticle'=>'In this article','toComments'=>'Jump to comments','end'=>'END OF ARTICLE','topics'=>'STORY TOPICS','enjoy'=>'Enjoyed this article?','like'=>'Like','liked'=>'Liked','share'=>'Share','discuss'=>'COMMUNITY / DISCUSSION','thoughts'=>'Join the discussion.','yourComment'=>'Your comment','placeholder'=>'What do you think about this news?','send'=>'Post comment','discussion'=>'Discussion','sort'=>'Sort by','newest'=>'Newest first','oldest'=>'Oldest first','noComments'=>'No comments yet. Start the discussion.','chars'=>'characters','login'=>'Sign in to comment','disabled'=>'Comments are disabled for this article.'],
      'es'=>['mode'=>'MODO DE LECTURA','readHow'=>'Lee a tu manera','progress'=>'PROGRESO DE LECTURA','estimate'=>'Tiempo estimado','size'=>'Tamaño del texto','contrast'=>'Alto contraste','focus'=>'Modo enfoque','focusExit'=>'Salir del enfoque','overview'=>'DE UN VISTAZO','inArticle'=>'En este artículo','toComments'=>'Ir a comentarios','end'=>'FIN DEL ARTÍCULO','topics'=>'TEMAS','enjoy'=>'¿Te gustó el artículo?','like'=>'Me gusta','liked'=>'Te gusta','share'=>'Compartir','discuss'=>'COMUNIDAD / DEBATE','thoughts'=>'Únete al debate.','yourComment'=>'Tu comentario','placeholder'=>'¿Qué opinas de esta noticia?','send'=>'Publicar comentario','discussion'=>'Debate','sort'=>'Ordenar','newest'=>'Más recientes','oldest'=>'Más antiguos','noComments'=>'Todavía no hay comentarios.','chars'=>'caracteres','login'=>'Inicia sesión para comentar','disabled'=>'Comentarios desactivados.'],
      'ru'=>['mode'=>'РЕЖИМ ЧТЕНИЯ','readHow'=>'Читайте с комфортом','progress'=>'ПРОГРЕСС ЧТЕНИЯ','estimate'=>'Время чтения','size'=>'Размер текста','contrast'=>'Высокая контрастность','focus'=>'Режим фокуса','focusExit'=>'Выйти из режима','overview'=>'КРАТКО','inArticle'=>'В этой статье','toComments'=>'К комментариям','end'=>'КОНЕЦ СТАТЬИ','topics'=>'ТЕМЫ','enjoy'=>'Понравилась статья?','like'=>'Нравится','liked'=>'Вам нравится','share'=>'Поделиться','discuss'=>'СООБЩЕСТВО / ОБСУЖДЕНИЕ','thoughts'=>'Присоединяйтесь к обсуждению.','yourComment'=>'Ваш комментарий','placeholder'=>'Что вы думаете об этой новости?','send'=>'Отправить комментарий','discussion'=>'Обсуждение','sort'=>'Сортировка','newest'=>'Сначала новые','oldest'=>'Сначала старые','noComments'=>'Пока нет комментариев.','chars'=>'символов','login'=>'Войти, чтобы комментировать','disabled'=>'Комментарии отключены.'],
    ][$locale];
    $newsToc = [];
    if ($mode === 'article') {
        $headings = $article['content_json']['content'] ?? $article['content_json'] ?? [];
        $collectHeadings = function ($nodes) use (&$collectHeadings, &$newsToc): void {
            foreach (is_array($nodes) ? $nodes : [] as $node) {
                if (!is_array($node)) continue;
                if (($node['type'] ?? '') === 'heading') {
                    $caption = trim($node['text'] ?? implode('', array_map(
                        static fn($part) => is_array($part) ? (string)($part['text'] ?? '') : '',
                        $node['content'] ?? []
                    )));
                    if ($caption !== '') $newsToc[] = ['id'=>'news-heading-'.(count($newsToc)+1),'label'=>$caption];
                }
                if (!empty($node['content']) && is_array($node['content'])) $collectHeadings($node['content']);
            }
        };
        $collectHeadings($headings);
    }
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
<link rel="stylesheet" href="/build/next/hntv3-js/{{ $mode === 'article' ? 'news-detail' : 'news-hub' }}.css?v=hntv3-news-reader-v2-20261024">
<link rel="stylesheet" href="/build/next/hntv3-js/hnt-news-live.css?v=hntv3-news-reader-v2-20261024">
<script defer src="/build/next/hntv3-js/hnt-news-live.js?v=hntv3-news-reader-v2-20261024"></script>
@if($viewer)
<script defer src="/build/next/hntv3-js/hnt-news-language.js?v=locale-20261024b"></script>
@endif
</head>
<body class="{{ $viewer ? 'hnt-news-signed' : 'hnt-news-guest' }} {{ $mode === 'article' ? 'hnt-news-detail-shell' : 'hnt-news-shell' }}"
@if($viewer) data-hnt-next-news="true" data-next-viewer-name="{{ $viewer->name }}" data-next-viewer-avatar="{{ $viewer->avatarUrl() }}"
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
  <div class="hnt-news-archive-actions">
   @if($viewer?->isAdmin())
   <a class="hnt-news-create-action" href="{{ route('next.news.editor') }}">
     {!! $newsSvg('pencil-line') !!} {{ ['de'=>'News erstellen','en'=>'Create news','es'=>'Crear noticia','ru'=>'Создать новость'][$locale] }}
   </a>
   @endif
   <span class="news-head-edition">{!! $newsSvg('file-text') !!} HUNTER JOURNAL <b>/</b> 1896</span>
  </div>
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
   <div class="news-rail-heading"><div><span class="news-eyebrow">{{ $locale === 'de' ? 'AUS DEM BAYOU' : ($locale === 'es' ? 'DESDE EL BAYOU' : ($locale === 'ru' ? 'ИЗ БАЙУ' : 'FROM THE BAYOU')) }}</span><h2>{{ $labels['recent'] }}<span class="title-ember">.</span></h2></div><span class="news-count">{{ str_pad((string)$articles->count(), 2, '0', STR_PAD_LEFT) }}</span></div>
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
         @if($viewer?->isAdmin())
         <a class="nd-mini-action" href="{{ route('next.news.editor.edit', ['articleId'=>$article['id']]) }}"
            title="{{ ['de'=>'News bearbeiten','en'=>'Edit article','es'=>'Editar artículo','ru'=>'Редактировать статью'][$locale] }}"
            aria-label="{{ ['de'=>'News bearbeiten','en'=>'Edit article','es'=>'Editar artículo','ru'=>'Редактировать статью'][$locale] }}">
           {!! $newsSvg('pencil-line') !!}
         </a>
         @endif
         @if($viewer)
         <button type="button" class="nd-mini-action" id="newsLike" aria-pressed="false" title="Gefällt mir" aria-label="Artikel gefällt mir">{!! $newsSvg('heart') !!}</button>
         <button type="button" class="nd-mini-action" id="newsSave" aria-pressed="false" title="Merken" aria-label="Artikel merken">{!! $newsSvg('bookmark-simple') !!}</button>
         @endif
         <button type="button" class="nd-mini-action" id="ndShare" title="{{ $newsReaderWords['share'] }}" aria-label="{{ $newsReaderWords['share'] }}">{!! $newsSvg('share-fat') !!}</button>
         <a class="nd-mini-action" href="#ndComments" aria-label="{{ $labels['comments'] }}">{!! $newsSvg('chat-circle-dots') !!}</a>
         <button type="button" class="nd-mini-action nd-focus-exit" id="ndFocusExit" hidden aria-label="{{ $newsReaderWords['focusExit'] }}">{!! $newsSvg('x') !!} {{ $newsReaderWords['focusExit'] }}</button>
        </div>
       </div>
       <div class="nd-article-text" id="ndArticleBody">
         @include('next.partials.news-article-body')
       </div>
       <div class="nd-article-end">{!! $newsSvg('seal-check') !!} {{ $newsReaderWords['end'] }} <span></span> HNT.ROCKS</div>
       @if(!empty($article['tags']))
       <div class="nd-tags"><span class="nd-section-kicker">{{ $newsReaderWords['topics'] }}</span><div class="nd-tags-list">@foreach($article['tags'] as $tag)<a href="{{ $nextNewsHome }}?tag={{ urlencode($tag) }}">#{{ $tag }}</a>@endforeach</div></div>
       @endif
       <div class="nd-after-story">
        <span>{!! $newsSvg('heart') !!} {{ $newsReaderWords['enjoy'] }}</span>
        <div>
          @if($viewer)<button type="button" class="nd-secondary" id="newsLikeBottom" aria-pressed="false">{!! $newsSvg('heart') !!} <span id="newsBottomLikeLabel">{{ $newsReaderWords['like'] }}</span> <small id="newsBottomLikeCount" hidden></small></button>@else<a class="nd-secondary" href="/login">{!! $newsSvg('heart') !!} {{ $newsReaderWords['like'] }}</a>@endif
          <button type="button" class="nd-secondary" id="ndShareBottom">{!! $newsSvg('share-fat') !!} {{ $newsReaderWords['share'] }}</button>
        </div>
       </div>
      </div>
    </article>
    <section class="nd-comments" id="ndComments" aria-labelledby="ndCommentsHeading">
     <div class="nd-comments-heading">
       <div><span class="nd-section-kicker">{{ $newsReaderWords['discuss'] }}</span>
       <h2 id="ndCommentsHeading">{{ $labels['comments'] }}<span class="nd-title-dot">.</span> <span id="ndCommentCount">(0)</span></h2><p>{{ $newsReaderWords['thoughts'] }}</p></div>
       {!! $newsSvg('chat-circle-dots') !!}
     </div>
     @if($viewer && ($article['comments_enabled'] ?? false))
     <form id="newsCommentForm" class="nd-comment-form hnt-news-comment-form">
       <label for="newsCommentText">{{ $newsReaderWords['yourComment'] }}</label>
       <textarea id="newsCommentText" maxlength="1200" rows="4" minlength="2" required placeholder="{{ $newsReaderWords['placeholder'] }}"></textarea>
       <div class="nd-comment-form-footer"><span><span id="ndCharCount">0</span> / 1200 {{ $newsReaderWords['chars'] }}</span><button type="submit" class="nd-primary">{!! $newsSvg('paper-plane-tilt') !!} {{ $newsReaderWords['send'] }}</button></div>
       <p id="newsCommentError" class="nd-form-error" role="alert" hidden></p>
     </form>
     @elseif($viewer)
       <p class="nd-comment-preview-note">{{ $newsReaderWords['disabled'] }}</p>
     @else
       <div class="nd-comment-preview-note">{!! $newsSvg('shield-check') !!} {{ $newsReaderWords['login'] }}</div>
       <a class="nd-secondary" href="/login">{{ $newsReaderWords['login'] }}</a>
     @endif
     <div class="nd-comment-list-head">
       <h3>{{ $newsReaderWords['discussion'] }} <span id="ndCommentListCount">0</span></h3>
       <label for="ndCommentSort">{{ $newsReaderWords['sort'] }}</label>
       <select id="ndCommentSort"><option value="newest">{{ $newsReaderWords['newest'] }}</option><option value="oldest">{{ $newsReaderWords['oldest'] }}</option></select>
     </div>
     <div class="nd-comment-list hnt-news-comment-list" id="newsCommentList" aria-live="polite"><div class="nd-comments-empty">…</div></div>
    </section>
   </div>
   <aside class="nd-sidebar" aria-label="Artikel-Informationen">
    <section class="nd-side-panel nd-reading-panel">
      <div class="nd-side-kicker">{{ $newsReaderWords['mode'] }} {!! $newsSvg('gear-six') !!}</div>
      <h2>{{ $newsReaderWords['readHow'] }}<span class="nd-title-dot">.</span></h2>
      <div class="nd-progress-visual"><span>{{ $newsReaderWords['progress'] }}</span><strong id="ndPercent">0%</strong></div>
      <div class="nd-progress-track"><i id="ndSidebarProgress"></i></div>
      <p class="nd-reading-help">{{ $newsReaderWords['estimate'] }}: {{ $readingMinutes }} {{ $labels['minutes'] }}</p>
      <div class="nd-adjust-row"><span>{{ $newsReaderWords['size'] }}</span>
        <div class="nd-font-controls" role="group" aria-label="{{ $newsReaderWords['size'] }}">
          <button type="button" id="ndFontDown" aria-label="{{ $newsReaderWords['size'] }} −">A−</button>
          <button type="button" id="ndFontReset" aria-label="{{ $newsReaderWords['size'] }} 100%">A</button>
          <button type="button" id="ndFontUp" aria-label="{{ $newsReaderWords['size'] }} +">A+</button>
        </div>
      </div>
      <button type="button" class="nd-setting-toggle" id="ndContrast" aria-pressed="false">{!! $newsSvg('target') !!} {{ $newsReaderWords['contrast'] }} <span class="nd-toggle-indicator"></span></button>
      <button type="button" class="nd-setting-toggle" id="ndFocus" aria-pressed="false">{!! $newsSvg('book-open') !!} {{ $newsReaderWords['focus'] }} <span class="nd-toggle-indicator"></span></button>
    </section>
    @if(count($newsToc))
    <nav class="nd-side-panel nd-toc" aria-label="{{ $newsReaderWords['inArticle'] }}">
      <div class="nd-side-kicker">{{ $newsReaderWords['overview'] }} {!! $newsSvg('book-open') !!}</div>
      <h2>{{ $newsReaderWords['inArticle'] }}<span class="nd-title-dot">.</span></h2>
      <div class="nd-toc-links">
       @foreach($newsToc as $item)
         <a class="nd-toc-link" href="#{{ $item['id'] }}" data-toc="{{ $item['id'] }}"><span>{{ str_pad((string)$loop->iteration,2,'0',STR_PAD_LEFT) }}</span><span>{{ $item['label'] }}</span></a>
       @endforeach
      </div>
      <a class="nd-side-footlink" href="#ndComments">{{ $newsReaderWords['toComments'] }} {!! $newsSvg('arrow-right') !!}</a>
    </nav>
    @endif
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
