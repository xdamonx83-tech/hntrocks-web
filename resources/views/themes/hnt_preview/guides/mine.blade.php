@extends('themes.hnt_preview.guides.layout')

@section('title', __('guides.mine.title').' · HNT.ROCKS')
@section('content')
<header class="guides-subhero">
<div><span>{{ __('guides.kicker') }}</span><h1>{{ __('guides.mine.title') }}</h1><p>{{ __('guides.mine.intro') }}</p></div>
<div class="guides-subhero-actions"><article><strong>{{ number_format($guideReputation) }}</strong><span>{{ __('guides.stats.reputation') }}</span></article><form action="{{ route('guides.store') }}" method="post">@csrf<button class="guide-btn primary" type="submit"><i class="ph ph-plus"></i>{{ __('guides.create') }}</button></form></div>
</header>
<nav class="my-guides-tabs">
<a @class(['active' => $activeStatus === 'all']) href="{{ route('guides.mine') }}">{{ __('guides.status.all') }} <span>{{ $statusCounts->sum() }}</span></a>
@foreach(\App\Models\Guide::STATUSES as $status)<a @class(['active' => $activeStatus === $status]) href="{{ route('guides.mine', ['status' => $status]) }}">{{ __('guides.status.'.$status) }} <span>{{ $statusCounts[$status] }}</span></a>@endforeach
</nav>
<section class="my-guides-list">
@forelse($guides as $guide)
@php
    $revision = $guide->workingRevision ?: $guide->publishedRevision;
    $coverUrl = $revision?->cover_media_id ? route('guides.media.show', $revision->cover_media_id) : null;
@endphp
<article class="my-guide-row">
<div class="my-guide-cover">@if($coverUrl)<img src="{{ $coverUrl }}" alt="">@else<i class="ph ph-book-open-text"></i>@endif</div>
<div class="my-guide-copy">
<div class="guide-card-meta"><span>{{ $revision?->category?->label() ?: __('guides.all_categories') }}</span><span>{{ __('guides.language.'.$revision?->language) }}</span><span>{{ __('guides.platform.'.$revision?->platform) }}</span><span>{{ __('guides.difficulty.'.$revision?->difficulty) }}</span></div>
<h2>{{ $revision?->title ?: __('guides.editor.title_placeholder') }}</h2>
<p>{{ $revision?->summary ?: __('guides.status_text.'.$guide->status) }}</p>
@if($revision?->moderation_reason)<aside><strong>{{ __('guides.mine.moderation_reason') }}</strong>{{ $revision->moderation_reason }}</aside>@endif
<small>{{ __('guides.version', ['version' => $revision?->version ?? 1]) }} · {{ __('guides.last_edited', ['time' => $guide->updated_at?->diffForHumans()]) }}@if($guide->published_at) · {{ __('guides.published_on', ['date' => $guide->published_at->translatedFormat('d.m.Y')]) }}@endif</small>
</div>
<div class="my-guide-state"><span class="guide-status {{ $guide->status }}">{{ __('guides.status.'.$guide->status) }}</span><p>{{ __('guides.status_text.'.$guide->status) }}</p></div>
<div class="my-guide-actions">
@if($guide->status !== 'pending_review' && $guide->status !== 'archived')<a class="guide-btn compact primary" href="{{ route('guides.edit', $guide) }}">{{ __('guides.mine.edit') }}</a>@endif
@if($revision)<a class="guide-btn compact" href="{{ route('guides.preview', $guide) }}">{{ __('guides.mine.preview') }}</a>@endif
@if($guide->isPublished())<a class="guide-btn compact" href="{{ route('guides.show', $guide) }}">{{ __('guides.mine.open') }}</a>@endif
@if($guide->status === 'pending_review')<form action="{{ route('guides.withdraw', $guide) }}" method="post">@csrf<button class="guide-link danger" type="submit">{{ __('guides.editor.withdraw') }}</button></form>@endif
</div>
</article>
@empty
<section class="guides-empty"><i class="ph ph-books"></i><h2>{{ __('guides.mine.empty_title') }}</h2><p>{{ __('guides.mine.empty_text') }}</p></section>
@endforelse
</section>
<div class="guides-pagination">{{ $guides->links() }}</div>
@endsection
