@if($mode === 'head')
    @php
        $ogLocale = ['de' => 'de_DE', 'en' => 'en_US', 'es' => 'es_ES', 'ru' => 'ru_RU'][$locale];
        $xDefault = ($alternates['en'] ?? reset($alternates)) ?: $canonical;
    @endphp
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="{{ $canonical }}">
    @foreach($alternates as $alternateLocale => $alternateUrl)
        <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $xDefault }}">
    <meta property="og:site_name" content="HNT.ROCKS">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta property="og:type" content="{{ isset($publishedAt) ? 'article' : 'website' }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">
    @isset($publishedAt)<meta property="article:published_time" content="{{ $publishedAt }}">@endisset
    @isset($modifiedAt)<meta property="article:modified_time" content="{{ $modifiedAt }}">@endisset
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@elseif($mode === 'overview')
    <main style="max-width:1120px;margin:48px auto;padding:24px;color:#f2e8d8;background:#171714;font-family:Inter,Arial,sans-serif">
        <h1>{{ $title }}</h1><p>{{ $description }}</p>
        <section aria-label="{{ $title }}">
            @forelse($articles as $article)
                <article style="display:grid;grid-template-columns:minmax(150px,280px) 1fr;gap:20px;padding:20px 0;border-top:1px solid #393832">
                    @if($article['hero_media'])<a href="{{ $article['url'] }}"><img src="{{ $article['hero_media']['url'] }}" alt="{{ $article['hero_media']['alt_text'] ?? '' }}" style="width:100%;aspect-ratio:16/9;object-fit:cover"></a>@endif
                    <div><p>{{ $article['category_key'] }}</p><h2><a href="{{ $article['url'] }}">{{ $article['title'] }}</a></h2><p>{{ $article['excerpt'] }}</p><time datetime="{{ $article['published_at'] }}">{{ $article['published_at'] }}</time></div>
                </article>
            @empty
                <p>{{ ['de' => 'Noch keine News veröffentlicht.', 'en' => 'No news articles have been published yet.', 'es' => 'Todavía no hay noticias publicadas.', 'ru' => 'Пока нет опубликованных новостей.'][$locale ?? 'en'] }}</p>
            @endforelse
        </section>
    </main>
@else
    <style>
        article p.news-empty-paragraph{display:block;min-height:1.65em;margin:.45em 0}
        article .news-media-text{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
        article .news-media-text.ratio-33-67{grid-template-columns:33fr 67fr}
        article .news-media-text.ratio-40-60{grid-template-columns:40fr 60fr}
        article .news-media-text.ratio-60-40{grid-template-columns:60fr 40fr}
        article .news-media-text.ratio-67-33{grid-template-columns:67fr 33fr}
        article .news-media-text.right .news-media-text-media{order:2}
        article .news-media-text.right .news-media-text-copy{order:1}
        article .news-media-text img,article .news-media-text video{display:block;max-width:100%;height:auto}
        article .news-media-text-copy>p{margin:.65em 0;line-height:1.65}
        article figure.news-hnt-divider{display:flex;justify-content:center;width:100%;margin:20px 0}
        article figure.news-hnt-divider img{display:block;width:100%;max-width:1100px;height:auto;object-fit:contain;background:transparent}
        article figure.news-hnt-divider-small img{max-width:860px}
        @media(max-width:700px){article .news-media-text{grid-template-columns:1fr}article .news-media-text.right .news-media-text-media,article .news-media-text.right .news-media-text-copy{order:initial}}
    </style>
    <main style="max-width:980px;margin:48px auto;padding:24px;color:#f2e8d8;background:#171714;font-family:Inter,Arial,sans-serif">
        <p><a href="{{ route('news.overview.locale', ['locale' => $locale]) }}">← News</a> · {{ $article['category_key'] }}</p>
        <h1>{{ $translation->title }}</h1>
        @if($translation->excerpt)<p>{{ $translation->excerpt }}</p>@endif
        <time datetime="{{ $article['published_at'] }}">{{ $article['published_at'] }}</time>
        @if($article['hero_media'])<figure><img src="{{ $article['hero_media']['url'] }}" alt="{{ $article['hero_media']['alt_text'] ?? '' }}" style="width:100%;max-height:600px;object-fit:cover"></figure>@endif
        <article>
            @if(\App\Support\NewsContentDocument::isV2($translation->content_json))
                {!! \App\Support\NewsContentDocument::render($translation->content_json, $media) !!}
            @else
            @foreach(($translation->content_json ?? []) as $block)
                @php
                    $asset = $media[(int) ($block['media_id'] ?? 0)] ?? null;
                    $before = $media[(int) ($block['before_media_id'] ?? 0)] ?? null;
                    $after = $media[(int) ($block['after_media_id'] ?? 0)] ?? null;
                @endphp
                @if(($block['type'] ?? '') === 'paragraph')
                    @php
                        $richHtml = '';
                        if (is_array($block['runs'] ?? null)) {
                            foreach ($block['runs'] as $run) {
                                if (!is_array($run)) continue;
                                $piece = nl2br(e($run['text'] ?? ''));
                                if (!empty($run['italic'])) $piece = '<em>'.$piece.'</em>';
                                if (!empty($run['bold'])) $piece = '<strong>'.$piece.'</strong>';
                                $href = $run['href'] ?? null;
                                $isLocal = is_string($href) && str_starts_with($href, '/') && !str_starts_with($href, '//');
                                $isWeb = is_string($href) && filter_var($href, FILTER_VALIDATE_URL)
                                    && in_array(strtolower((string) parse_url($href, PHP_URL_SCHEME)), ['http', 'https'], true);
                                if (is_string($href) && strlen($href) <= 2048 && ($isLocal || $isWeb)) {
                                    $piece = '<a href="'.e($href).'" rel="noopener noreferrer">'.$piece.'</a>';
                                }
                                $richHtml .= $piece;
                            }
                        } else {
                            $richHtml = nl2br(e($block['text'] ?? ''));
                        }
                    @endphp
                    <p>{!! $richHtml !!}</p>
                @elseif(($block['type'] ?? '') === 'heading')<h{{ (int) ($block['level'] ?? 2) === 3 ? '3' : '2' }}>{{ $block['text'] ?? '' }}</h{{ (int) ($block['level'] ?? 2) === 3 ? '3' : '2' }}>
                @elseif(($block['type'] ?? '') === 'image' && $asset)<figure><img src="{{ $asset['url'] }}" alt="{{ $block['alt'] ?? '' }}" style="max-width:100%">@if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
                @elseif(($block['type'] ?? '') === 'video' && $asset)<figure><video controls preload="metadata" src="{{ $asset['url'] }}">Your browser cannot play this video.</video>@if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
                @elseif(($block['type'] ?? '') === 'before_after')<figure>@if($before)<img src="{{ $before['url'] }}" alt="{{ $block['before_alt'] ?? '' }}" style="max-width:48%">@endif @if($after)<img src="{{ $after['url'] }}" alt="{{ $block['after_alt'] ?? '' }}" style="max-width:48%">@endif @if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
                @elseif(($block['type'] ?? '') === 'quote')<blockquote><p>{!! nl2br(e($block['text'] ?? ''), false) !!}</p>@if(!empty($block['attribution']))<cite>{{ $block['attribution'] }}</cite>@endif</blockquote>
                @elseif(($block['type'] ?? '') === 'list')<{{ ($block['style'] ?? '') === 'ordered' ? 'ol' : 'ul' }}>@foreach(($block['items'] ?? []) as $item)<li>{{ $item }}</li>@endforeach</{{ ($block['style'] ?? '') === 'ordered' ? 'ol' : 'ul' }}>
                @endif
            @endforeach
            @endif
        </article>
    </main>
@endif
