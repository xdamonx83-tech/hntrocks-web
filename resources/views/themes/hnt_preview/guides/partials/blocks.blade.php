@foreach((array) $revision->content_blocks as $block)
@php
    $type = (string) ($block['type'] ?? '');
    $blockId = 'guide-block-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($block['id'] ?? $loop->index));
@endphp
@if($type === 'heading')
<section class="guide-content-heading" id="{{ $blockId }}">
@switch((int) ($block['level'] ?? 2))
@case(3)<h3>{{ $block['text'] ?? '' }}</h3>@break
@case(4)<h4>{{ $block['text'] ?? '' }}</h4>@break
@default<h2>{{ $block['text'] ?? '' }}</h2>
@endswitch
</section>
@elseif($type === 'paragraph')
<p class="guide-content-paragraph">{!! nl2br(e((string) ($block['text'] ?? ''))) !!}</p>
@elseif($type === 'steps')
<section class="guide-content-list numbered">
<span>{{ __('guides.blocks.steps') }}</span>
<ol>@foreach((array) ($block['items'] ?? []) as $item)<li>{{ $item }}</li>@endforeach</ol>
</section>
@elseif($type === 'list')
<section class="guide-content-list">
<ul>@foreach((array) ($block['items'] ?? []) as $item)<li>{{ $item }}</li>@endforeach</ul>
</section>
@elseif($type === 'image' && !empty($block['media_id']))
<figure class="guide-content-image">
<img src="{{ route('guides.media.show', (int) $block['media_id']) }}" alt="{{ $block['caption'] ?? $revision->title }}" loading="lazy">
@if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif
</figure>
@elseif(in_array($type, ['notice', 'warning'], true))
<aside class="guide-content-callout {{ $type }}">
<i class="ph {{ $type === 'warning' ? 'ph-warning' : 'ph-info' }}" aria-hidden="true"></i>
<div><strong>{{ $block['title'] ?: __('guides.blocks.'.$type) }}</strong><p>{!! nl2br(e((string) ($block['text'] ?? ''))) !!}</p></div>
</aside>
@endif
@endforeach
