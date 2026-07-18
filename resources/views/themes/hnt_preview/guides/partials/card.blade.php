@php
    $revision = $guide->publishedRevision;
    $authorName = $guide->author?->name ?: ($guide->author?->username ?: 'HNT Hunter');
    $coverUrl = $revision?->cover_media_id ? route('guides.media.show', $revision->cover_media_id) : null;
@endphp
<article class="guide-card">
<a class="guide-card-cover" href="{{ route('guides.show', $guide) }}">
@if($coverUrl)
<img src="{{ $coverUrl }}" alt="{{ $revision->title }}" loading="lazy">
@else
<span><i class="ph ph-book-open-text" aria-hidden="true"></i>{{ __('guides.cover_missing') }}</span>
@endif
@if($guide->is_featured)<em>{{ __('guides.featured') }}</em>@endif
</a>
<div class="guide-card-body">
<div class="guide-card-meta">
<span>{{ $revision?->category?->label() }}</span>
<span>{{ __('guides.language.'.$revision?->language) }}</span>
<span>{{ __('guides.platform.'.$revision?->platform) }}</span>
</div>
<h2><a href="{{ route('guides.show', $guide) }}">{{ $revision?->title }}</a></h2>
<p>{{ $revision?->summary }}</p>
<footer>
<a class="guide-author-mini" href="{{ $guide->author ? route('profile.public', $guide->author) : '#' }}">
<img src="{{ $guide->author?->avatarUrl() }}" alt="">
<span><strong>{{ $authorName }}</strong><small>{{ $guide->published_at?->diffForHumans() }}</small></span>
</a>
<div class="guide-card-stats">
<span title="{{ __('guides.helpful.label') }}"><i class="ph ph-thumbs-up" aria-hidden="true"></i>{{ number_format($guide->helpful_count) }}</span>
<span title="{{ __('guides.comments.title') }}"><i class="ph ph-chat-circle" aria-hidden="true"></i>{{ number_format($guide->comments_count) }}</span>
</div>
</footer>
</div>
</article>
