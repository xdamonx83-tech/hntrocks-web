@php
    $blocks = $article['content_json'] ?? [];
    $articleMedia = $article['media'] ?? [];
@endphp
@if(\App\Support\NewsContentDocument::isV2($blocks))
    {!! \App\Support\NewsContentDocument::render($blocks, $articleMedia) !!}
@else
    @foreach($blocks as $block)
        @php
            $asset = $articleMedia[(int) ($block['media_id'] ?? 0)] ?? null;
            $before = $articleMedia[(int) ($block['before_media_id'] ?? 0)] ?? null;
            $after = $articleMedia[(int) ($block['after_media_id'] ?? 0)] ?? null;
        @endphp
        @if(($block['type'] ?? '') === 'paragraph')
            @php
                $richHtml = '';
                if (is_array($block['runs'] ?? null)) {
                    foreach ($block['runs'] as $run) {
                        if (! is_array($run)) continue;
                        $piece = nl2br(e($run['text'] ?? ''), false);
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
                    $richHtml = nl2br(e($block['text'] ?? ''), false);
                }
            @endphp
            <p>{!! $richHtml !!}</p>
        @elseif(($block['type'] ?? '') === 'heading')
            @php $level = (int) ($block['level'] ?? 2) === 3 ? 3 : 2; @endphp
            @if($level === 3)<h3>{{ $block['text'] ?? '' }}</h3>@else<h2>{{ $block['text'] ?? '' }}</h2>@endif
        @elseif(($block['type'] ?? '') === 'image' && $asset)
            <figure class="nd-inline-figure"><img src="{{ $asset['url'] }}" alt="{{ $block['alt'] ?? ($asset['alt_text'] ?? '') }}" loading="lazy">@if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
        @elseif(($block['type'] ?? '') === 'video' && $asset)
            <figure class="nd-inline-figure"><video controls preload="metadata" playsinline src="{{ $asset['url'] }}">Your browser cannot play this video.</video>@if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
        @elseif(($block['type'] ?? '') === 'before_after')
            <figure class="hnt-news-compare">
                @if($before)<img src="{{ $before['url'] }}" alt="{{ $block['before_alt'] ?? '' }}" loading="lazy">@endif
                @if($after)<img src="{{ $after['url'] }}" alt="{{ $block['after_alt'] ?? '' }}" loading="lazy">@endif
                @if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif
            </figure>
        @elseif(($block['type'] ?? '') === 'quote')
            <blockquote><p>{{ $block['text'] ?? '' }}</p>@if(!empty($block['attribution']))<cite>{{ $block['attribution'] }}</cite>@endif</blockquote>
        @elseif(($block['type'] ?? '') === 'list')
            @if(($block['style'] ?? '') === 'ordered')<ol>@else<ul>@endif
            @foreach(($block['items'] ?? []) as $item)<li>{{ $item }}</li>@endforeach
            @if(($block['style'] ?? '') === 'ordered')</ol>@else</ul>@endif
        @endif
    @endforeach
@endif
