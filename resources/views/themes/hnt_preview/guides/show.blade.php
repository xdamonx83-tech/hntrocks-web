@extends('themes.hnt_preview.guides.layout')

@section('title', $revision->title.' · HNT.ROCKS')
@section('meta_description', $revision->summary)
@section('robots', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('canonical', $isPreview ? route('guides.preview', $guide) : route('guides.show', $guide))
@if($revision->cover_media_id)@section('og_image', route('guides.media.show', $revision->cover_media_id))@endif
@section('content')
@if($isPreview)<div class="guide-preview-banner"><i class="ph ph-eye"></i>{{ __('guides.detail.preview_notice') }}</div>@endif
<article class="guide-detail">
<header class="guide-detail-hero">
<div class="guide-detail-cover">
@if($revision->cover_media_id)<img src="{{ route('guides.media.show', $revision->cover_media_id) }}" alt="{{ $revision->title }}">@else<span><i class="ph ph-book-open-text"></i>{{ __('guides.cover_missing') }}</span>@endif
</div>
<div class="guide-detail-title">
<div class="guide-card-meta"><span>{{ $revision->category?->label() }}</span><span>{{ __('guides.language.'.$revision->language) }}</span><span>{{ __('guides.difficulty.'.$revision->difficulty) }}</span></div>
<h1>{{ $revision->title }}</h1>
<p>{{ $revision->summary }}</p>
<div class="guide-detail-author">
<img src="{{ $guide->author?->avatarUrl() }}" alt="">
<span><strong>{{ $guide->author?->name ?: $guide->author?->username }}</strong><small>{{ __('guides.version', ['version' => $revision->version]) }} · {{ __('guides.reading_time', ['minutes' => $revision->reading_time_minutes]) }}</small></span>
</div>
</div>
<aside class="guide-detail-actions">
@unless($isPreview)
@auth
<button class="guide-action {{ $viewerHelpful ? 'active' : '' }}" data-guide-toggle data-url="{{ route('guides.helpful.toggle', $guide) }}" data-active-label="{{ __('guides.helpful.marked') }}" data-inactive-label="{{ __('guides.helpful.label') }}"><i class="ph ph-thumbs-up"></i><span>{{ $viewerHelpful ? __('guides.helpful.marked') : __('guides.helpful.label') }}</span><b data-count>{{ $guide->helpful_count }}</b></button>
<button class="guide-action {{ $viewerBookmarked ? 'active' : '' }}" data-guide-toggle data-url="{{ route('guides.bookmark.toggle', $guide) }}" data-active-label="{{ __('guides.bookmark.saved_label') }}" data-inactive-label="{{ __('guides.bookmark.save') }}"><i class="ph ph-bookmark-simple"></i><span>{{ $viewerBookmarked ? __('guides.bookmark.saved_label') : __('guides.bookmark.save') }}</span></button>
@else
<a class="guide-action" href="{{ route('login') }}"><i class="ph ph-thumbs-up"></i><span>{{ __('guides.helpful.login') }}</span><b>{{ $guide->helpful_count }}</b></a>
@endauth
<button class="guide-action" data-guide-share data-url="{{ route('guides.show', $guide) }}" data-title="{{ $revision->title }}"><i class="ph ph-share-network"></i><span>{{ __('guides.detail.share') }}</span></button>
@auth
<details class="guide-report">
<summary><i class="ph ph-flag"></i>{{ __('guides.detail.report') }}</summary>
<form action="{{ route('reports.store') }}" method="post">@csrf<input type="hidden" name="type" value="guide"><input type="hidden" name="id" value="{{ $guide->id }}">
<label>{{ __('guides.detail.report_reason') }}<select name="reason" required><option value="spam">Spam</option><option value="abuse">Abuse</option><option value="hate">Hate</option><option value="fraud">Fraud</option><option value="privacy">Privacy</option><option value="other">Other</option></select></label>
<label>{{ __('guides.detail.report_details') }}<textarea name="body" maxlength="2000"></textarea></label>
<button class="guide-btn danger compact" type="submit">{{ __('guides.detail.report_send') }}</button>
</form>
</details>
@endauth
@endunless
</aside>
</header>

<div class="guide-detail-layout">
<aside class="guide-detail-toc">
<span class="guide-panel-kicker">{{ __('guides.detail.contents') }}</span>
<nav>
@php $headingCount = 0; @endphp
@foreach((array) $revision->content_blocks as $block)
@if(($block['type'] ?? null) === 'heading' && filled($block['text'] ?? null))
@php $headingCount++; $headingId = 'guide-block-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($block['id'] ?? $loop->index)); @endphp
<a href="#{{ $headingId }}">{{ $block['text'] }}</a>
@endif
@endforeach
@if($headingCount === 0)<p>{{ __('guides.detail.no_toc') }}</p>@endif
</nav>
</aside>

<main class="guide-article">
@include('themes.hnt_preview.guides.partials.blocks', ['revision' => $revision])
@unless($isPreview)
<section class="guide-comments" id="guide-comments">
<header><div><span class="guide-panel-kicker">{{ __('guides.comments.title') }}</span><h2>{{ __('guides.comments.title') }} <b>{{ $guide->comments_count }}</b></h2></div></header>
@auth
<form class="guide-comment-form" action="{{ route('guides.comments.store', $guide) }}" method="post" data-guide-comment-form>@csrf
<textarea name="body" maxlength="2000" required placeholder="{{ __('guides.comments.placeholder') }}"></textarea>
<button class="guide-btn primary" type="submit">{{ __('guides.comments.send') }}</button>
</form>
@else
<a class="guide-btn" href="{{ route('login') }}">{{ __('guides.comments.login') }}</a>
@endauth
<div class="guide-comment-list" data-guide-comment-list>
@forelse($comments as $comment)
<article class="guide-comment" id="comment-{{ $comment->id }}">
<img src="{{ $comment->user?->avatarUrl() }}" alt="">
<div>
<header><strong>{{ $comment->user?->name ?: $comment->user?->username }}</strong>@if($comment->user_id === $guide->author_id)<em>{{ __('guides.comments.author_badge') }}</em>@endif<span>{{ $comment->created_at?->diffForHumans() }}</span></header>
@if($comment->trashed())<p class="deleted">{{ __('guides.comments.deleted_label') }}</p>@else<p>{!! nl2br(e($comment->body)) !!}</p>@endif
@auth
@unless($comment->trashed())
<footer>
<button type="button" data-comment-reply="{{ $comment->id }}" data-comment-author="{{ $comment->user?->username }}">{{ __('guides.comments.reply') }}</button>
@if($comment->canEdit(auth()->user()))<details><summary>{{ __('guides.comments.edit') }}</summary><form action="{{ route('guides.comments.update', $comment) }}" method="post">@csrf @method('patch')<textarea name="body" maxlength="2000" required>{{ $comment->body }}</textarea><button type="submit">{{ __('guides.comments.edit') }}</button></form></details>@endif
@if($comment->canDelete(auth()->user()))<form action="{{ route('guides.comments.destroy', $comment) }}" method="post">@csrf @method('delete')<button type="submit">{{ __('guides.comments.delete') }}</button></form>@endif
<details><summary>{{ __('guides.detail.report') }}</summary><form action="{{ route('reports.store') }}" method="post">@csrf<input type="hidden" name="type" value="guide_comment"><input type="hidden" name="id" value="{{ $comment->id }}"><input type="hidden" name="reason" value="other"><textarea name="body" maxlength="2000"></textarea><button type="submit">{{ __('guides.detail.report_send') }}</button></form></details>
</footer>
@endunless
@endauth
@foreach($comment->replies as $reply)
<article class="guide-comment reply" id="comment-{{ $reply->id }}">
<img src="{{ $reply->user?->avatarUrl() }}" alt=""><div><header><strong>{{ $reply->user?->name ?: $reply->user?->username }}</strong>@if($reply->user_id === $guide->author_id)<em>{{ __('guides.comments.author_badge') }}</em>@endif<span>{{ $reply->created_at?->diffForHumans() }}</span></header><p>{{ $reply->trashed() ? __('guides.comments.deleted_label') : $reply->body }}</p></div>
</article>
@endforeach
</div>
</article>
@empty
<div class="guide-comments-empty" data-guide-comments-empty>{{ __('guides.comments.empty') }}</div>
@endforelse
</div>
@if(method_exists($comments, 'hasMorePages') && $comments->hasMorePages())
<div class="guides-pagination"><button class="guide-btn" type="button" data-guide-comments-more data-next-url="{{ $comments->nextPageUrl() }}">{{ __('guides.comments.load_more') }}</button></div>
@endif
</section>
@endunless
</main>

<aside class="guide-detail-sidebar">
<section>
<span class="guide-panel-kicker">{{ __('guides.detail.metadata') }}</span>
<dl><div><dt>{{ __('guides.editor.category') }}</dt><dd>{{ $revision->category?->label() }}</dd></div><div><dt>{{ __('guides.filters.language') }}</dt><dd>{{ __('guides.language.'.$revision->language) }}</dd></div><div><dt>{{ __('guides.filters.platform') }}</dt><dd>{{ __('guides.platform.'.$revision->platform) }}</dd></div><div><dt>{{ __('guides.filters.difficulty') }}</dt><dd>{{ __('guides.difficulty.'.$revision->difficulty) }}</dd></div></dl>
</section>
<section class="guide-author-card">
<img src="{{ $guide->author?->avatarUrl() }}" alt="">
<span class="guide-panel-kicker">{{ __('guides.detail.about_author') }}</span>
<h3>{{ $guide->author?->name ?: $guide->author?->username }}</h3>
@if($authorReputation !== null)<p><strong>{{ number_format($authorReputation) }}</strong> {{ __('guides.stats.reputation') }}</p>@endif
<a href="{{ route('profile.public', $guide->author) }}">{{ '@'.$guide->author?->username }}</a>
</section>
@if((array) $revision->tags)
<section><span class="guide-panel-kicker">{{ __('guides.detail.tags') }}</span><div class="guide-tags">@foreach($revision->tags as $tag)<span>#{{ $tag }}</span>@endforeach</div></section>
@endif
</aside>
</div>
</article>
@endsection
