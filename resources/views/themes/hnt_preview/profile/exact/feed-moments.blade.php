@php
    $profileMomentsLive = collect($profileMomentsPreview ?? []);
@endphp
<section class="profile-tab-panel" data-profile-panel="moments" hidden id="profileTabMoments" role="tabpanel">
<div class="profile-panel-toolbar profile-moments-toolbar">
<div>
<span>{{ $profileFormatCount($profileUser->moments_count ?? $profileMomentsLive->count()) }} MOMENTS</span>
<strong>Highlights von {{ $profileDisplayName }}</strong>
</div>
<div class="profile-panel-filters" aria-label="Moments sortieren">
<button class="active" data-profile-moment-sort="newest" type="button">Neueste</button>
<button data-profile-moment-sort="popular" type="button">Beliebt</button>
<button data-profile-moment-sort="oldest" type="button">Älteste</button>
</div>
</div>
<div class="profile-moment-gallery" data-profile-moment-gallery>
@forelse($profileMomentsLive as $moment)
@php
    $momentDate = $moment->published_at ?: $moment->created_at;
    $momentTitle = trim((string) ($moment->caption ?: $moment->description ?: 'HNT Moment'));
    $momentCover = $moment->coverUrl();
    $momentDuration = max(0, (int) ($moment->duration_seconds ?? 0));
    $momentDurationLabel = sprintf('%d:%02d', intdiv($momentDuration, 60), $momentDuration % 60);
@endphp
<article class="profile-video-moment" data-profile-moment-card data-moment-published="{{ $momentDate?->timestamp ?? 0 }}" data-moment-likes="{{ (int) ($moment->likes_count ?? 0) }}">
<a class="profile-video-thumb" href="{{ route('moments.show', $moment) }}">
<img alt="{{ $momentTitle }}" class="profile-video-cover" loading="lazy" src="{{ $momentCover }}"/>
<span class="moment-corner-label">HNT MOMENT</span>
<span aria-label="Moment abspielen" class="profile-video-play"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 7 8 5-8 5z"></path></svg></span>
<span class="profile-video-duration">{{ $momentDurationLabel }}</span>
</a>
<div class="profile-video-copy">
<h3><a href="{{ route('moments.show', $moment) }}">{{ $momentTitle }}</a></h3>
<p>{{ $momentDate?->diffForHumans() ?: 'gerade eben' }}</p>
<div class="profile-video-stats">
<span><svg><use href="#i-eye"></use></svg> {{ $profileFormatCount($moment->views_count ?? 0) }}</span>
<span><svg><use href="#i-heart"></use></svg> {{ $profileFormatCount($moment->likes_count ?? 0) }}</span>
<span><svg><use href="#i-comment"></use></svg> {{ $profileFormatCount($moment->comments_count ?? 0) }}</span>
</div>
</div>
</article>
@empty
<div class="profile-tab-empty profile-moments-empty">
<strong>Noch keine Moments</strong>
<p>Veröffentlichte Highlights erscheinen hier.</p>
@if($isOwnProfile)<a href="{{ route('moments.create') }}">Moment erstellen <svg><use href="#i-arrow"></use></svg></a>@endif
</div>
@endforelse
</div>
</section>
