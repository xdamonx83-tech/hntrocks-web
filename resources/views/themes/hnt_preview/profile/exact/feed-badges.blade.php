@php
    $profileBadgesLive = collect($latestBadges ?? []);
@endphp
<section class="profile-tab-panel" data-profile-panel="badges" hidden id="profileTabBadges" role="tabpanel">
<div class="profile-panel-toolbar">
<div><span>{{ $profileFormatCount($profileUser->badges_count ?? $profileBadgesLive->count()) }} BADGES</span><strong>Erfolge &amp; Auszeichnungen</strong></div>
<div class="profile-panel-filters" aria-label="Badges filtern">
<button class="active" data-profile-badge-filter="all" type="button">Alle</button>
<button data-profile-badge-filter="rare" type="button">Selten</button>
<button data-profile-badge-filter="recent" type="button">Neu</button>
</div>
</div>
<div class="profile-badges-grid" data-profile-badges-grid>
@forelse($profileBadgesLive as $badge)
@php
    $badgeName = $badge->displayName(app()->getLocale());
    $badgeDescription = trim((string) $badge->displayDescription(app()->getLocale()));
    $badgeIconUrl = $badge->iconUrl();
    $badgeRarity = (string) ($badge->rarity ?: 'common');
    $badgeIsRare = in_array($badgeRarity, ['rare', 'epic', 'legendary'], true);
    $badgeAwardedRaw = $badge->pivot?->awarded_at;
    $badgeAwardedAt = $badgeAwardedRaw ? \Illuminate\Support\Carbon::parse($badgeAwardedRaw) : $badge->created_at;
@endphp
<article class="profile-badge-card {{ $badgeIsRare ? 'featured' : '' }}" data-profile-badge-card data-badge-rarity="{{ $badgeRarity }}" data-badge-recent="{{ $loop->index < 6 ? '1' : '0' }}" data-badge-awarded="{{ $badgeAwardedAt?->timestamp ?? 0 }}">
<div class="badge-emblem">
@if($badgeIconUrl)
<img alt="{{ $badgeName }}" loading="lazy" src="{{ $badgeIconUrl }}"/>
@elseif(filled($badge->icon))
<span>{{ $badge->icon }}</span>
@else
<svg><use href="#i-check"></use></svg>
@endif
</div>
<div><span>{{ strtoupper((string) ($badge->category ?: $badge->rarityLabel())) }}</span><strong>{{ $badgeName }}</strong><p>{{ $badgeDescription !== '' ? $badgeDescription : 'Auszeichnung auf HNT.ROCKS.' }}</p></div>
<small>{{ $badgeAwardedAt ? $badgeAwardedAt->diffForHumans() : 'Freigeschaltet' }}</small>
</article>
@empty
<div class="profile-tab-empty profile-badges-empty">
<strong>Noch keine Badges</strong>
<p>Freigeschaltete Auszeichnungen erscheinen hier.</p>
@if($isOwnProfile)<a href="{{ route('gamification.index') }}">Fortschritt ansehen <svg><use href="#i-arrow"></use></svg></a>@endif
</div>
@endforelse
</div>
<div class="profile-filter-empty" data-profile-badges-filter-empty hidden>Für diesen Filter wurden keine Badges gefunden.</div>
</section>
