@extends('admin.layouts.app')

@section('title', __('guides.admin.title').' · HNT.ROCKS')
@section('admin_heading', __('guides.admin.heading'))
@push('head')<link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/guides/admin-guides.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/admin-guides.css')) ?: time() }}">@endpush
@section('content')
@if(session('status'))<div class="hnt-admin-alert success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="hnt-admin-alert error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<header class="admin-guides-head"><div><p class="hnt-admin-eyebrow">{{ __('guides.kicker') }}</p><h1>{{ __('guides.admin.heading') }}</h1><span>{{ __('guides.admin.open_count', ['count' => $pendingCount]) }}</span></div></header>
<form class="admin-guides-filters" action="{{ route('admin.guides.index') }}" method="get">
<input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('guides.search_placeholder') }}">
<select name="status">@foreach(['pending_review','changes_requested','rejected','published','archived','all'] as $status)<option value="{{ $status }}" @selected($activeStatus === $status)>{{ $status === 'all' ? __('guides.status.all') : __('guides.status.'.$status) }}</option>@endforeach</select>
<select name="category"><option value="">{{ __('guides.all_categories') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->label() }}</option>@endforeach</select>
<select name="language"><option value="">{{ __('guides.filters.language') }}</option>@foreach(['de','en'] as $language)<option value="{{ $language }}" @selected(request('language') === $language)>{{ __('guides.language.'.$language) }}</option>@endforeach</select>
<input type="date" name="from" value="{{ request('from') }}">
<button type="submit">{{ __('guides.filters.apply') }}</button>
</form>
<div class="admin-guides-layout">
<aside class="admin-guides-queue">
<header><strong>{{ __('guides.admin.queue') }}</strong><span>{{ $queue->total() }}</span></header>
@forelse($queue as $item)
<a @class(['active' => $selected?->id === $item->id]) href="{{ route('admin.guides.index', array_merge(request()->except('page'), ['revision' => $item->id])) }}">
<span class="guide-status {{ $item->guide?->status }}">{{ __('guides.status.'.$item->guide?->status) }}</span>
<strong>{{ $item->title ?: __('guides.editor.title_placeholder') }}</strong>
<small>{{ $item->guide?->author?->username ? '@'.$item->guide->author->username : $item->guide?->author?->name }} · v{{ $item->version }}</small>
<em>{{ $item->submitted_at?->diffForHumans() }}</em>
</a>
@empty
<p>{{ __('guides.admin.empty') }}</p>
@endforelse
<div class="admin-guides-pagination">{{ $queue->links() }}</div>
</aside>
<main class="admin-guide-preview">
@if($selected)
<header>
<div><span>{{ __('guides.admin.preview') }} · v{{ $selected->version }}</span><h2>{{ $selected->title }}</h2><p>{{ $selected->summary }}</p></div>
<div class="admin-guide-author"><img src="{{ $selected->guide?->author?->avatarUrl() }}" alt=""><span><strong>{{ $selected->guide?->author?->name ?: $selected->guide?->author?->username }}</strong><small>{{ $selected->category?->label() }} · {{ __('guides.language.'.$selected->language) }}</small></span></div>
</header>
@if($selected->cover_media_id)<img class="admin-guide-cover" src="{{ route('guides.media.show', $selected->cover_media_id) }}" alt="">@endif
<article class="admin-guide-content">@include('themes.hnt_preview.guides.partials.blocks', ['revision' => $selected])</article>
<section class="admin-guide-diff">
<h3>{{ __('guides.admin.diff_title') }}</h3>
<div><article><span>{{ __('guides.admin.current_revision') }}</span>@if($selected->guide?->publishedRevision)<strong>v{{ $selected->guide->publishedRevision->version }} · {{ $selected->guide->publishedRevision->title }}</strong><p>{{ $selected->guide->publishedRevision->summary }}</p>@else<p>{{ __('guides.admin.no_published_version') }}</p>@endif</article><article><span>{{ __('guides.admin.new_revision') }}</span><strong>v{{ $selected->version }} · {{ $selected->title }}</strong><p>{{ $selected->summary }}</p></article></div>
</section>
<section class="admin-guide-history"><h3>{{ __('guides.admin.history') }}</h3>@foreach($selected->guide?->moderationEvents ?? [] as $event)<article><strong>{{ $event->action }}</strong><span>{{ $event->actor?->username ?: 'System' }} · {{ $event->created_at?->diffForHumans() }}</span>@if($event->reason)<p>{{ $event->reason }}</p>@endif</article>@endforeach</section>
@else
<div class="admin-guide-empty">{{ __('guides.admin.empty') }}</div>
@endif
</main>
<aside class="admin-guide-decision">
@if($selected)
<section><h3>{{ __('guides.admin.checklist') }}</h3><label><input type="checkbox"> {{ __('guides.admin.check_content') }}</label><label><input type="checkbox"> {{ __('guides.admin.check_media') }}</label><label><input type="checkbox"> {{ __('guides.admin.check_rules') }}</label></section>
@if($selected->status === 'pending_review')
<form action="{{ route('admin.guides.moderate', $selected) }}" method="post">@csrf
<label>{{ __('guides.admin.reason') }}<textarea name="reason" minlength="10" maxlength="3000" placeholder="{{ __('guides.admin.reason_placeholder') }}"></textarea></label>
<button class="approve" name="action" value="approve" type="submit">{{ __('guides.admin.approve') }}</button>
<button name="action" value="changes" type="submit">{{ __('guides.admin.changes') }}</button>
<button class="reject" name="action" value="reject" type="submit">{{ __('guides.admin.reject') }}</button>
</form>
@endif
@if($selected->guide?->status === 'archived')
<form action="{{ route('admin.guides.restore', $selected->guide) }}" method="post">@csrf<button class="approve" type="submit">{{ __('guides.admin.restore') }}</button></form>
@elseif($selected->guide?->isPublished())
<form action="{{ route('admin.guides.feature', $selected->guide) }}" method="post">@csrf<input type="hidden" name="is_featured" value="{{ $selected->guide->is_featured ? 0 : 1 }}"><button type="submit">{{ $selected->guide->is_featured ? __('guides.admin.unfeature') : __('guides.admin.feature') }}</button></form>
<form action="{{ route('admin.guides.archive', $selected->guide) }}" method="post">@csrf<label>{{ __('guides.admin.reason') }}<textarea name="reason" required minlength="10" maxlength="3000"></textarea></label><button class="reject" type="submit">{{ __('guides.admin.archive') }}</button></form>
@endif
@endif
</aside>
</div>
@endsection
