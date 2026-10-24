@php
    $blocks = $article['content_json'] ?? [];
    $articleMedia = $article['media'] ?? [];
@endphp
@if(\App\Support\NewsContentDocument::isV2($blocks))
    @php
        // Keep source data untouched. Repair missing word boundaries in text runs that
        // change formatting (legacy imports sometimes omit separator spaces).
        $readingDocument = $blocks;
        $repairNewsWords = function (array $node) use (&$repairNewsWords): array {
            if (!empty($node['content']) && is_array($node['content'])) {
                $children = array_map(
                    static fn($child) => is_array($child) ? $repairNewsWords($child) : $child,
                    $node['content']
                );
                if (in_array($node['type'] ?? '', ['paragraph', 'heading'], true)) {
                    for ($i = 1; $i < count($children); $i++) {
                        $prev = $children[$i - 1];
                        $next = $children[$i];
                        if (($prev['type'] ?? '') !== 'text' || ($next['type'] ?? '') !== 'text') continue;
                        $left = $prev['text'] ?? '';
                        $right = $next['text'] ?? '';
                        $hasFormatting = !empty($prev['marks']) || !empty($next['marks']);
                        if ($hasFormatting && preg_match('/[\p{L}\p{N}:;!?)]$/u', $left)
                            && preg_match('/^[\p{L}\p{N}]/u', $right)) {
                            $children[$i]['text'] = ' '.$right;
                        }
                    }
                }
                $node['content'] = $children;
            }
            return $node;
        };
        $readingDocument = $repairNewsWords($readingDocument);
    @endphp
    @php
       $renderedNewsHtml = \App\Support\NewsContentDocument::render($readingDocument, $articleMedia);
       $headingNumber = 0;
       $renderedNewsHtml = preg_replace_callback('/<h([23])([^>]*)>/i', function ($m) use (&$headingNumber): string {
           $headingNumber++;
           if (str_contains($m[2], 'id=')) return $m[0];
           return '<h'.$m[1].' id="news-heading-'.$headingNumber.'"'.$m[2].'>';
       }, $renderedNewsHtml);
       $firstImage = collect($readingDocument['content'] ?? [])
           ->first(static fn($node) => is_array($node) && ($node['type'] ?? '') === 'hntImage');
       $width = $firstImage['attrs']['width'] ?? null;
       if (is_string($width) && preg_match('/^([1-9][0-9]?)%$/', $width) && (int)$width <= 95) {
           $renderedNewsHtml = preg_replace(
               '/<figure>(?=<img\s)/i',
               '<figure class="nd-figure-preferred-width" style="--nd-media-width:'.e($width).'">',
               $renderedNewsHtml, 1
           );
       }
    @endphp
    {!! $renderedNewsHtml !!}
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
            @php
                $level = (int) ($block['level'] ?? 2) === 3 ? 3 : 2;
                $legacyHeadingNumber = ($legacyHeadingNumber ?? 0) + 1;
            @endphp
            @if($level === 3)<h3 id="news-heading-{{ $legacyHeadingNumber }}">{{ $block['text'] ?? '' }}</h3>@else<h2 id="news-heading-{{ $legacyHeadingNumber }}">{{ $block['text'] ?? '' }}</h2>@endif
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
