@extends('themes.hnt_preview.guides.layout')

@section('robots', request()->query() ? 'noindex,follow' : 'index,follow')
@section('canonical', route('guides.index'))
@section('content')
<header class="guides-hero">
<div>
<span>{{ __('guides.kicker') }}</span>
<h1>{{ __('guides.title') }}</h1>
<p>{{ __('guides.intro') }}</p>
<div class="guides-hero-actions">
@auth
<form action="{{ route('guides.store') }}" method="post">@csrf<button class="guide-btn primary" type="submit"><i class="ph ph-plus"></i>{{ __('guides.create') }}</button></form>
<a class="guide-btn" href="{{ route('guides.mine') }}"><i class="ph ph-books"></i>{{ __('guides.my_guides') }}</a>
@else
<a class="guide-btn primary" href="{{ route('login') }}"><i class="ph ph-sign-in"></i>{{ __('guides.create') }}</a>
@endauth
</div>
</div>
<div class="guides-hero-stats">
<article><strong>{{ number_format($publishedCount) }}</strong><span>{{ __('guides.stats.guides') }}</span></article>
<article><strong>{{ number_format($authorCount) }}</strong><span>{{ __('guides.stats.authors') }}</span></article>
<article><strong>{{ number_format($guides->total()) }}</strong><span>{{ __('guides.stats.results') }}</span></article>
</div>
</header>

<section class="guides-overview-layout">
<aside class="guides-category-panel">
<span class="guide-panel-kicker">{{ __('guides.all_categories') }}</span>
<nav>
<a @class(['active' => $filters['category'] === '']) href="{{ route('guides.index', request()->except(['category', 'page'])) }}"><i class="ph ph-squares-four"></i><span>{{ __('guides.all_categories') }}</span></a>
@foreach($categories as $category)
<a @class(['active' => $filters['category'] === $category->slug]) href="{{ route('guides.index', array_merge(request()->except('page'), ['category' => $category->slug])) }}"><i class="ph {{ $category->icon }}"></i><span>{{ $category->label() }}</span></a>
@endforeach
</nav>
</aside>

<div class="guides-directory">
<form class="guides-toolbar" action="{{ route('guides.index') }}" method="get">
<label class="guide-search"><i class="ph ph-magnifying-glass"></i><input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('guides.search_placeholder') }}"></label>
<label><span>{{ __('guides.filters.language') }}</span><select name="language"><option value="">{{ __('guides.filters.all') }}</option>@foreach(['de','en'] as $value)<option value="{{ $value }}" @selected($filters['language'] === $value)>{{ __('guides.language.'.$value) }}</option>@endforeach</select></label>
<label><span>{{ __('guides.filters.platform') }}</span><select name="platform"><option value="">{{ __('guides.filters.all') }}</option>@foreach(['pc','playstation','xbox'] as $value)<option value="{{ $value }}" @selected($filters['platform'] === $value)>{{ __('guides.platform.'.$value) }}</option>@endforeach</select></label>
<label><span>{{ __('guides.filters.difficulty') }}</span><select name="difficulty"><option value="">{{ __('guides.filters.all') }}</option>@foreach(['beginner','advanced','expert'] as $value)<option value="{{ $value }}" @selected($filters['difficulty'] === $value)>{{ __('guides.difficulty.'.$value) }}</option>@endforeach</select></label>
<label><span>{{ __('guides.filters.sort') }}</span><select name="sort">@foreach(['new','helpful','popular'] as $value)<option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ __('guides.sort.'.$value) }}</option>@endforeach</select></label>
@if($filters['category'])<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
<button class="guide-btn compact primary" type="submit">{{ __('guides.filters.apply') }}</button>
<a class="guide-reset" href="{{ route('guides.index') }}">{{ __('guides.filters.reset') }}</a>
</form>

<div class="guides-result-head"><strong>{{ number_format($guides->total()) }} {{ __('guides.stats.results') }}</strong><span>{{ __('guides.sort.'.$filters['sort']) }}</span></div>
<div class="guide-card-grid">
@forelse($guides as $guide)
@include('themes.hnt_preview.guides.partials.card', ['guide' => $guide])
@empty
<section class="guides-empty"><i class="ph ph-book-open"></i><h2>{{ __('guides.empty_title') }}</h2><p>{{ __('guides.empty_text') }}</p></section>
@endforelse
</div>
<div class="guides-pagination">{{ $guides->links() }}</div>
</div>
</section>
@endsection
