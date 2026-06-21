@extends('themes.hnt_preview.layouts.app')

@section('app_window_class', 'profile-window')
@section('main_class', 'profile-main')

@php
    $profile = $profileUser->profile;
    $viewer = auth()->user();
    $displayName = trim((string) ($profileUser->name ?: $profileUser->username ?: __('ui.preview_hnt_hunter')));
    $username = trim((string) ($profileUser->username ?: __('ui.preview_hunter_handle')));
    $headline = trim((string) ($profile?->headline ?: $profile?->hunt_role ?: $profile?->playstyle ?: __('ui.preview_community_headline')));
    $bio = trim((string) ($profile?->bio ?: __('ui.preview_profile_default_bio')));
    $avatarUrl = $profileUser->avatarUrl();
    $coverUrl = $profileUser->coverUrl();
    $level = max(1, (int) ($profileUser->level ?? 1));
    $xpTotal = max(0, (int) ($profileUser->xp_total ?? 0));
    $nextLevelXp = max(250, $level * 250);
    $levelProgress = min(100, (int) round(($xpTotal % $nextLevelXp) / $nextLevelXp * 100));
    $friendsCount = (int) ($profileFriendsCount ?? $profileUser->friendsCount());
    $postsCount = (int) ($profileUser->visible_feed_posts_count ?? $profilePostsTotal ?? 0);
    $badgesCount = (int) ($profileUser->badges_count ?? 0);
    $momentsCount = (int) ($profileUser->moments_count ?? 0);
    $activeSection = $activeSection ?? 'timeline';
    $profileCosmetics = $profileCosmetics ?? \App\Support\CrownCosmetics::forUser($profileUser);
    $avatarFrameClass = trim((string) ($profileCosmetics['avatar_frame_class'] ?? ''));
    $usernameEffectClass = trim((string) ($profileCosmetics['username_effect_class'] ?? ''));
    $profileBannerClass = trim((string) ($profileCosmetics['profile_banner_class'] ?? ''));
    $profileTitleLabel = trim((string) ($profileCosmetics['profile_title_label'] ?? ''));
    $completionChecks = \App\Support\ProfileCompletion::checks($profileUser);
    $profileCompletionScore = \App\Support\ProfileCompletion::score($profileUser);
    $completionLabels = [
        'name' => __('ui.preview_profile_completion_name'),
        'username' => __('ui.preview_profile_completion_username'),
        'bio' => __('ui.preview_profile_completion_bio'),
        'platform' => __('ui.preview_profile_completion_platform'),
        'playstyle' => __('ui.preview_profile_completion_playstyle'),
    ];
    $missingCompletion = collect($completionChecks)->filter(fn ($done) => ! $done);
    $trophyStats = $trophyCabinet['stats'] ?? [];
    $safeRoute = static function (string $routeName, array $params = [], string $fallback = '/') : string {
        return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName, $params) : url($fallback);
    };
    $sectionUrl = function (string $section) use ($isOwnProfile, $profileUser, $safeRoute): string {
        $routes = [
            'timeline' => ['own' => 'profile.show', 'public' => 'profile.public', 'fallback' => '/profile'],
            'about' => ['own' => 'profile.about', 'public' => 'profile.about.public', 'fallback' => '/profile/about'],
            'friends' => ['own' => 'profile.friends', 'public' => 'profile.friends.public', 'fallback' => '/profile/friends'],
            'badges' => ['own' => 'profile.badges', 'public' => 'profile.badges.public', 'fallback' => '/profile/badges'],
            'trophies' => ['own' => 'profile.trophies', 'public' => 'profile.trophies.public', 'fallback' => '/profile/trophies'],
            'contact' => ['own' => 'profile.contact', 'public' => 'profile.contact.public', 'fallback' => '/profile/contact'],
        ];
        $meta = $routes[$section] ?? $routes['timeline'];
        return $isOwnProfile
            ? $safeRoute($meta['own'], [], $meta['fallback'])
            : $safeRoute($meta['public'], [$profileUser], '/u/' . $profileUser->username);
    };
    $tabItems = [
        'timeline' => ['label' => __('ui.preview_profile_tab_profile'), 'count' => $postsCount],
        'about' => ['label' => __('ui.preview_profile_tab_info'), 'count' => null],
        'friends' => ['label' => __('ui.preview_profile_tab_friends'), 'count' => $friendsCount],
        'badges' => ['label' => __('ui.preview_profile_tab_badges'), 'count' => $badgesCount],
        'trophies' => ['label' => __('ui.preview_profile_tab_trophies'), 'count' => (int) ($trophyStats['cup_submissions'] ?? 0)],
        'contact' => ['label' => __('ui.preview_profile_tab_links'), 'count' => null],
    ];
    $socialLinks = collect([
        'Discord' => $profile?->discord_name,
        'Steam' => $profile?->steam_url,
        'Twitch' => $profile?->twitch_url,
        'YouTube' => $profile?->youtube_url,
    ])->filter();
    $friendButtonLabel = __('ui.preview_profile_friend_add');
    if ($friendship?->isAccepted()) {
        $friendButtonLabel = __('ui.preview_profile_friend_connected');
    } elseif ($friendship?->isPending()) {
        $friendButtonLabel = $viewer && $friendship->isRequester($viewer) ? __('ui.preview_profile_friend_requested') : __('ui.preview_profile_friend_open');
    }
@endphp

@section('title', $displayName . ' · ' . __('ui.preview_profile_title_suffix') . ' · HNT.rocks')
@section('robots', request()->routeIs('profile.public', 'profile.*.public')
    && ($profile?->profile_visibility ?? 'public') === 'public'
    && in_array($activeSection, ['timeline', 'about', 'badges', 'trophies', 'teams'], true)
        ? 'index,follow'
        : 'noindex,nofollow')

@section('content')
    <section class="profile-hero-card">
        <div class="profile-cover {{ $profileBannerClass }}" data-profile-media-role="cover" style="background-image: linear-gradient(180deg, rgba(26,26,24,.08), rgba(26,26,24,.88)), url('{{ $coverUrl }}'); background-position: center; background-size: cover;">
            <div class="cover-noise"></div>
            @if($isOwnProfile)
                <button type="button" class="profile-cover-action profile-media-trigger" data-profile-media-type="cover" aria-label="{{ __('ui.preview_profile_edit_cover_aria') }}">
                    <i class="ph ph-camera" aria-hidden="true"></i>
                    {{ __('ui.preview_profile_cover_button') }}
                </button>
            @endif
        </div>

        <div class="profile-identity-panel">
            <div class="profile-avatar-wrap">
                <span class="avatar profile-avatar hnt-avatar-shell hh-crowns-avatar-wrap {{ $avatarFrameClass }}">
                    <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" data-profile-media-role="avatar">
                </span>
                @if($isOwnProfile)
                    <button type="button" class="profile-avatar-edit profile-media-trigger" data-profile-media-type="avatar" aria-label="{{ __('ui.preview_profile_edit_avatar_aria') }}">
                        <i class="ph ph-camera" aria-hidden="true"></i>
                    </button>
                @endif
                <span class="profile-level">{{ __('ui.preview_profile_level_short') }} {{ $level }}</span>
            </div>

            <div class="profile-copy">
                <div class="profile-name-row">
                    <div>
                        <h1 class="{{ $usernameEffectClass }}">{{ $displayName }}</h1>
                        <p>{{ '@' . $username }} · {{ $headline }}</p>
                    </div>
                    <div class="profile-actions">
                        @if($isOwnProfile)
                            <a href="{{ route('profile.edit') }}" class="btn-create profile-edit-btn">{{ __('ui.edit_profile') }}</a>
                        @elseif($viewer)
                            @if($friendship?->isAccepted())
                                <form method="post" action="{{ route('friends.destroy', $friendship) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="profile-soft-btn btn-following">{{ $friendButtonLabel }}</button>
                                </form>
                            @elseif($friendship?->isPending())
                                <span class="profile-soft-btn btn-following">{{ $friendButtonLabel }}</span>
                            @else
                                <form method="post" action="{{ route('friends.store', $profileUser) }}">
                                    @csrf
                                    <button type="submit" class="btn-create profile-edit-btn">{{ __('ui.preview_profile_friend_add') }}</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-create profile-edit-btn">{{ __('ui.preview_profile_login') }}</a>
                        @endif
                    </div>
                </div>

                <div class="profile-badges-line">
                    @if($profileTitleLabel !== '')
                        <span class="profile-rank-pill">{{ $profileTitleLabel }}</span>
                    @endif
                    <span class="profile-rank-pill">{{ number_format($xpTotal, 0, ',', '.') }} XP</span>
                    <span class="profile-status-pill">{{ $profile?->is_lfg_available ? __('ui.preview_profile_lfg_open') : __('ui.preview_profile_hnt_profile') }}</span>
                    <span>{{ $profile?->region ?: __('ui.preview_profile_region_open') }}</span>
                    <span>{{ $profile?->platform ?: __('ui.preview_profile_platform_open') }}</span>
                </div>
            </div>
        </div>
    </section>

    <nav class="profile-tabs" aria-label="{{ __('ui.preview_profile_sections_aria') }}">
        @foreach($tabItems as $section => $item)
            <a href="{{ $sectionUrl($section) }}" @class(['active' => $activeSection === $section])>
                {{ $item['label'] }}
                @if(! is_null($item['count']))<span>{{ number_format((int) $item['count']) }}</span>@endif
            </a>
        @endforeach
    </nav>

    @if($activeSection === 'timeline')
        @if($isOwnProfile && $profileCompletionScore < 100)
            <section class="profile-progress-card profile-completion-card" style="--profile-completion: {{ $profileCompletionScore }}%;">
                <div class="progress-orb"><span>{{ $profileCompletionScore }}%</span></div>
                <div>
                    <h2>{{ __('ui.preview_profile_complete_title') }}</h2>
                    <p>{{ __('ui.preview_profile_complete_line', ['percent' => $profileCompletionScore, 'count' => $missingCompletion->count(), 'label' => $missingCompletion->count() === 1 ? __('ui.preview_profile_missing_step_one') : __('ui.preview_profile_missing_step_many')]) }}</p>
                </div>
                <div class="progress-pills profile-completion-pills">
                    @foreach($missingCompletion as $key => $done)
                        <span>{{ $completionLabels[$key] ?? ucfirst((string) $key) }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="profile-posts-list" data-hnt-profile-posts>
            @forelse($profilePosts as $post)
                @include('themes.hnt_preview.feed.partials.post-card', ['post' => $post])
            @empty
                <section class="profile-post-card hnt-empty-state" data-hnt-profile-empty>
                    <h2>{{ __('ui.preview_profile_no_posts_title') }}</h2>
                    <p>{{ $isOwnProfile ? __('ui.preview_profile_no_posts_own') : __('ui.preview_profile_no_posts_other', ['name' => $displayName]) }}</p>
                </section>
            @endforelse
        </div>

        @if(($profilePostsShown ?? 0) < ($profilePostsTotal ?? 0))
            <div class="members-load-wrap profile-load-wrap" data-hnt-profile-load-more>
                <a class="btn-create" data-hnt-profile-load-more-link href="{{ request()->fullUrlWithQuery(['profile_posts_page' => ($profilePostPage ?? 1) + 1]) }}">{{ __('ui.preview_profile_load_more_posts') }}</a>
            </div>
        @endif
    @elseif($activeSection === 'about')
        <section class="profile-post-card profile-about-section">
            <h2>{{ __('ui.preview_profile_about_title', ['name' => $displayName]) }}</h2>
            <p class="post-text">{{ $bio }}</p>
            <div class="profile-stat-grid">
                <div><strong>{{ $level }}</strong><span>Level</span></div>
                <div><strong>{{ number_format($xpTotal, 0, ',', '.') }}</strong><span>XP</span></div>
                <div><strong>{{ $profile?->platform ?: '—' }}</strong><span>{{ __('ui.preview_profile_completion_platform') }}</span></div>
                <div><strong>{{ $profile?->region ?: '—' }}</strong><span>{{ __('ui.region') }}</span></div>
            </div>
        </section>
    @elseif($activeSection === 'friends')
        <section class="profile-post-card profile-friends-section">
            <h2>{{ __('ui.preview_profile_friends_title') }}</h2>
            <div class="members-grid profile-members-grid">
                @forelse($profileFriendsPreview as $friend)
                    <a href="{{ route('profile.public', $friend) }}" class="member-card profile-friend-card">
                        <span class="member-person">
                            <span class="avatar member-avatar hnt-avatar-shell"><img src="{{ $friend->avatarUrl() }}" alt="{{ $friend->name ?: $friend->username }}"></span>
                            <span>
                                <h2>{{ $friend->name ?: $friend->username }}</h2>
                                <p>{{ '@' . $friend->username }} · {{ __('ui.preview_profile_common_friends', ['count' => (int) ($friend->common_friends_count ?? 0)]) }}</p>
                            </span>
                        </span>
                    </a>
                @empty
                    <p class="post-text">{{ __('ui.preview_profile_no_friends') }}</p>
                @endforelse
            </div>
        </section>
    @elseif($activeSection === 'badges')
        <section class="profile-post-card profile-badges-section">
            <h2>{{ __('ui.preview_profile_badges_title') }}</h2>
            <div class="badge-grid profile-badge-grid profile-admin-badge-grid">
                @forelse($latestBadges as $badge)
                    @php
                        $badgeDescription = trim((string) ($badge->description ?? ''));
                        $badgeIconUrl = method_exists($badge, 'iconUrl') ? $badge->iconUrl() : null;
                        $badgeIcon = trim((string) ($badge->icon ?? ''));
                        $awardedAt = $badge->pivot?->awarded_at ? \Illuminate\Support\Carbon::parse($badge->pivot->awarded_at) : null;
                    @endphp
                    <a href="{{ route('gamification.index') }}" class="badge-card unlocked hnt-admin-badge-card">
                        <div class="badge-medallion hnt-admin-badge-medallion">
                            @if($badgeIconUrl)
                                <img src="{{ $badgeIconUrl }}" alt="{{ $badge->name }}">
                            @else
                                <span>{{ $badgeIcon !== '' ? $badgeIcon : '◆' }}</span>
                            @endif
                        </div>
                        <div class="badge-card-body">
                            <h2>{{ $badge->name }}</h2>
                            <p>{{ $badgeDescription !== '' ? \Illuminate\Support\Str::limit($badgeDescription, 82) : __('ui.preview_profile_unlocked') }}</p>
                            <div class="profile-badge-meta">
                                <span>{{ method_exists($badge, 'rarityLabel') ? $badge->rarityLabel() : ucfirst((string) ($badge->rarity ?: 'normal')) }}</span>
                                @if((int) ($badge->xp_reward ?? 0) > 0)
                                    <span>{{ (int) $badge->xp_reward }} XP</span>
                                @endif
                                <span>{{ $awardedAt ? $awardedAt->format('d.m.Y') : __('ui.preview_profile_unlocked') }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="post-text">{{ __('ui.preview_profile_no_badges') }}</p>
                @endforelse
            </div>
        </section>
    @elseif($activeSection === 'trophies')
        <section class="profile-post-card profile-trophies-section">
            <h2>{{ __('ui.preview_profile_trophies_title') }}</h2>
            <div class="profile-stat-grid">
                <div><strong>{{ number_format((int) ($trophyStats['cup_points'] ?? 0)) }}</strong><span>{{ __('ui.preview_profile_cup_points') }}</span></div>
                <div><strong>{{ number_format((int) ($trophyStats['cup_kills'] ?? 0)) }}</strong><span>{{ __('ui.preview_profile_kills') }}</span></div>
                <div><strong>{{ number_format((int) ($trophyStats['cup_bounty_tokens'] ?? 0)) }}</strong><span>{{ __('ui.preview_profile_bounty') }}</span></div>
                <div><strong>{{ number_format((int) ($trophyStats['cup_submissions'] ?? 0)) }}</strong><span>{{ __('ui.preview_profile_runs') }}</span></div>
            </div>
            @if(! empty($trophyCabinet['best_submission']))
                @php
                    $best = $trophyCabinet['best_submission'];
                @endphp
                <p class="post-text">{{ __('ui.preview_profile_best_run', ['points' => (int) $best->points, 'kills' => (int) $best->kills, 'bounty' => (int) $best->bounty_tokens]) }}</p>
            @endif
        </section>
    @elseif($activeSection === 'contact')
        <section class="profile-post-card profile-contact-section">
            <h2>{{ __('ui.preview_profile_links_title') }}</h2>
            <div class="about-list">
                @forelse($socialLinks as $label => $value)
                    <span>
                        <strong>{{ $label }}</strong>
                        @if(str_starts_with((string) $value, 'http'))
                            <a href="{{ $value }}" target="_blank" rel="nofollow noopener">{{ __('ui.preview_profile_open_link') }}</a>
                        @else
                            <em>{{ $value }}</em>
                        @endif
                    </span>
                @empty
                    <p class="post-text">{{ __('ui.preview_profile_no_links') }}</p>
                @endforelse
            </div>
        </section>
    @else
        <section class="profile-post-card hnt-empty-state">
            <h2>{{ __('ui.preview_profile_later_title') }}</h2>
            <p>{{ __('ui.preview_profile_later_text') }}</p>
        </section>
    @endif

    @if($isOwnProfile)
        <input type="file" id="hntProfileAvatarInput" class="profile-media-file" accept="image/jpeg,image/png,image/webp" data-profile-media-input="avatar">
        <input type="file" id="hntProfileCoverInput" class="profile-media-file" accept="image/jpeg,image/png,image/webp" data-profile-media-input="cover">

        <div class="profile-crop-backdrop" id="profileCropBackdrop" aria-hidden="true">
            <div class="profile-crop-modal" role="dialog" aria-modal="true" aria-labelledby="profileCropTitle">
                <div class="composer-orb composer-orb-one"></div>
                <div class="composer-orb composer-orb-two"></div>
                <div class="composer-handle"></div>
                <header class="profile-crop-header">
                    <div>
                        <span class="composer-kicker">{{ __('ui.preview_profile_crop_kicker') }}</span>
                        <h2 id="profileCropTitle">{{ __('ui.preview_profile_crop_title') }}</h2>
                        <p id="profileCropHelp">{{ __('ui.preview_profile_crop_help') }}</p>
                    </div>
                    <button type="button" class="icon-btn modal-close" data-profile-crop-close aria-label="{{ __('ui.preview_action_close') }}">
                        <i class="ph ph-x" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="profile-crop-body">
                    <div class="profile-crop-frame" data-profile-crop-frame>
                        <img src="" alt="{{ __('ui.preview_profile_crop_preview_alt') }}" data-profile-crop-image>
                    </div>
                    <label class="profile-crop-zoom">
                        <span>{{ __('ui.preview_profile_zoom') }}</span>
                        <input type="range" min="1" max="3" value="1" step="0.01" data-profile-crop-zoom>
                    </label>
                    <p class="profile-crop-status" data-profile-crop-status></p>
                </div>
                <footer class="profile-crop-footer">
                    <button type="button" class="btn-following modal-close-secondary" data-profile-crop-close>{{ __('ui.preview_action_cancel') }}</button>
                    <button type="button" class="btn-create" data-profile-crop-save>{{ __('ui.preview_action_save') }}</button>
                </footer>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var i18n = window.HNT_PREVIEW_I18N || {};
        var t = function (key, fallback) { return i18n[key] || fallback; };
        var profilePostsList = document.querySelector('[data-hnt-profile-posts]');
        var profileLoadMoreWrap = document.querySelector('[data-hnt-profile-load-more]');

        function initProfileReadMore(scope) {
            var root = scope || document;
            root.querySelectorAll('[data-hnt-read-more]:not([data-hnt-read-more-ready])').forEach(function (textBlock) {
                textBlock.setAttribute('data-hnt-read-more-ready', '1');
                window.requestAnimationFrame(function () {
                    var maxCollapsedHeight = 118;
                    if (textBlock.scrollHeight <= maxCollapsedHeight + 8) return;

                    textBlock.classList.add('is-collapsible', 'is-collapsed');
                    textBlock.style.setProperty('--hnt-read-more-height', maxCollapsedHeight + 'px');

                    var toggle = document.createElement('button');
                    toggle.type = 'button';
                    toggle.className = 'hnt-read-more-toggle';
                    toggle.textContent = t('preview_read_more', 'Mehr lesen');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.addEventListener('click', function () {
                        var expanded = textBlock.classList.toggle('is-expanded');
                        textBlock.classList.toggle('is-collapsed', ! expanded);
                        toggle.textContent = expanded ? t('preview_read_less', 'Weniger lesen') : t('preview_read_more', 'Mehr lesen');
                        toggle.setAttribute('aria-expanded', String(expanded));
                    });

                    textBlock.insertAdjacentElement('afterend', toggle);
                });
            });
        }

        function initProfileMediaCarousels(scope) {
            var root = scope || document;
            root.querySelectorAll('[data-hnt-media-carousel]:not([data-hnt-carousel-ready])').forEach(function (carousel) {
                var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-hnt-media-slide]'));
                var previousButton = carousel.querySelector('[data-hnt-media-prev]');
                var nextButton = carousel.querySelector('[data-hnt-media-next]');
                var currentCounter = carousel.querySelector('[data-hnt-media-current]');
                var currentIndex = 0;

                carousel.setAttribute('data-hnt-carousel-ready', '1');

                function renderSlide(nextIndex) {
                    if (! slides.length) return;
                    currentIndex = (nextIndex + slides.length) % slides.length;
                    slides.forEach(function (slide, index) {
                        var active = index === currentIndex;
                        slide.classList.toggle('is-active', active);
                        slide.setAttribute('aria-hidden', String(! active));
                        if (! active) {
                            slide.querySelectorAll('video').forEach(function (video) { video.pause(); });
                        }
                    });
                    if (currentCounter) currentCounter.textContent = String(currentIndex + 1);
                }

                if (previousButton) previousButton.addEventListener('click', function () { renderSlide(currentIndex - 1); });
                if (nextButton) nextButton.addEventListener('click', function () { renderSlide(currentIndex + 1); });
                renderSlide(0);
            });
        }

        function initProfileVideoPlayers(scope) {
            var root = scope || document;
            root.querySelectorAll('[data-hnt-video-player]:not([data-hnt-video-ready])').forEach(function (player) {
                var video = player.querySelector('[data-hnt-video]');
                if (! video) return;
                player.setAttribute('data-hnt-video-ready', '1');
                video.controls = false;
            });
        }

        function initProfileAppendedPosts(scope) {
            initProfileReadMore(scope);
            initProfileMediaCarousels(scope);
            initProfileVideoPlayers(scope);
            document.dispatchEvent(new CustomEvent('hnt:profile-posts-appended', { detail: { scope: scope } }));
        }

        if (profilePostsList && profileLoadMoreWrap) {
            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-hnt-profile-load-more-link]');
                if (! link) return;
                event.preventDefault();

                link.classList.add('is-loading');
                link.setAttribute('aria-busy', 'true');
                var originalText = link.textContent;
                link.textContent = t('preview_loading_short', 'Lade …');

                fetch(link.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin'
                })
                    .then(function (response) {
                        if (! response.ok) throw new Error(t('preview_profile_posts_load_failed', 'Beiträge konnten nicht geladen werden.'));
                        return response.text();
                    })
                    .then(function (html) {
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');
                        var incomingList = doc.querySelector('[data-hnt-profile-posts]');
                        var incomingLoad = doc.querySelector('[data-hnt-profile-load-more]');
                        if (! incomingList) throw new Error(t('preview_profile_posts_read_failed', 'Beiträge konnten nicht gelesen werden.'));

                        var existingIds = new Set(Array.prototype.slice.call(profilePostsList.querySelectorAll('[data-post-id]')).map(function (post) {
                            return post.getAttribute('data-post-id');
                        }));
                        var appended = [];

                        Array.prototype.slice.call(incomingList.children).forEach(function (node) {
                            if (node.hasAttribute && node.hasAttribute('data-hnt-profile-empty')) return;
                            var postId = node.getAttribute ? node.getAttribute('data-post-id') : null;
                            if (postId && existingIds.has(postId)) return;
                            if (postId) existingIds.add(postId);
                            var imported = document.importNode(node, true);
                            profilePostsList.appendChild(imported);
                            appended.push(imported);
                        });

                        if (incomingLoad) {
                            profileLoadMoreWrap.innerHTML = incomingLoad.innerHTML;
                        } else {
                            profileLoadMoreWrap.remove();
                        }

                        if (appended.length) {
                            var fragment = document.createElement('div');
                            appended.forEach(function (node) { fragment.appendChild(node.cloneNode(false)); });
                            initProfileAppendedPosts(profilePostsList);
                        }
                    })
                    .catch(function () {
                        window.location.href = link.href;
                    })
                    .finally(function () {
                        if (! document.body.contains(link)) return;
                        link.classList.remove('is-loading');
                        link.removeAttribute('aria-busy');
                        link.textContent = originalText;
                    });
            });
        }

        var cropBackdrop = document.getElementById('profileCropBackdrop');
        if (! cropBackdrop) return;

        var endpoint = @json(route('profile.media.update'));
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var title = document.getElementById('profileCropTitle');
        var help = document.getElementById('profileCropHelp');
        var image = cropBackdrop.querySelector('[data-profile-crop-image]');
        var frame = cropBackdrop.querySelector('[data-profile-crop-frame]');
        var zoomInput = cropBackdrop.querySelector('[data-profile-crop-zoom]');
        var status = cropBackdrop.querySelector('[data-profile-crop-status]');
        var saveButton = cropBackdrop.querySelector('[data-profile-crop-save]');
        var fileInputs = document.querySelectorAll('[data-profile-media-input]');
        var state = {
            type: 'avatar',
            objectUrl: '',
            zoom: 1,
            baseScale: 1,
            offsetX: 0,
            offsetY: 0,
            dragging: false,
            startX: 0,
            startY: 0,
            baseX: 0,
            baseY: 0
        };

        function setStatus(message, mode) {
            if (! status) return;
            status.textContent = message || '';
            status.dataset.mode = mode || '';
        }

        function applyCropTransform() {
            if (! image) return;
            image.style.transform = 'translate(calc(-50% + ' + state.offsetX + 'px), calc(-50% + ' + state.offsetY + 'px)) scale(' + state.zoom + ')';
        }

        function prepareCropImage() {
            if (! image || ! frame || ! image.naturalWidth || ! image.naturalHeight) return;
            var rect = frame.getBoundingClientRect();
            if (! rect.width || ! rect.height) return;

            state.baseScale = Math.min(rect.width / image.naturalWidth, rect.height / image.naturalHeight);
            state.zoom = 1;
            state.offsetX = 0;
            state.offsetY = 0;

            image.style.width = (image.naturalWidth * state.baseScale) + 'px';
            image.style.height = (image.naturalHeight * state.baseScale) + 'px';

            if (zoomInput) {
                zoomInput.min = '1';
                zoomInput.max = '4';
                zoomInput.value = '1';
            }

            applyCropTransform();
        }

        function openCrop(type, file) {
            state.type = type === 'cover' ? 'cover' : 'avatar';
            state.zoom = 1;
            state.offsetX = 0;
            state.offsetY = 0;
            if (zoomInput) zoomInput.value = '1';
            if (state.objectUrl) URL.revokeObjectURL(state.objectUrl);
            state.objectUrl = URL.createObjectURL(file);
            image.onload = prepareCropImage;
            image.src = state.objectUrl;
            frame.classList.toggle('is-cover', state.type === 'cover');
            frame.classList.toggle('is-avatar', state.type !== 'cover');
            title.textContent = state.type === 'cover' ? t('preview_profile_crop_cover_title', 'Titelbild zuschneiden') : t('preview_profile_crop_avatar_title', 'Avatar zuschneiden');
            help.textContent = state.type === 'cover'
                ? t('preview_profile_crop_cover_help', 'Ziehe das Bild im breiten Rahmen und wähle den passenden Ausschnitt für dein Titelbild.')
                : t('preview_profile_crop_avatar_help', 'Ziehe das Bild im Kreis und wähle den passenden Ausschnitt für deinen Avatar.');
            setStatus('');
            cropBackdrop.classList.add('open');
            cropBackdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function closeCrop() {
            cropBackdrop.classList.remove('open');
            cropBackdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            fileInputs.forEach(function (input) { input.value = ''; });
        }

        document.querySelectorAll('[data-profile-media-type]').forEach(function (button) {
            button.addEventListener('click', function () {
                var type = button.getAttribute('data-profile-media-type') || 'avatar';
                var input = document.querySelector('[data-profile-media-input="' + type + '"]');
                if (input) input.click();
            });
        });

        fileInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (! file) return;
                openCrop(input.getAttribute('data-profile-media-input') || 'avatar', file);
            });
        });

        cropBackdrop.querySelectorAll('[data-profile-crop-close]').forEach(function (button) {
            button.addEventListener('click', closeCrop);
        });

        cropBackdrop.addEventListener('click', function (event) {
            if (event.target === cropBackdrop) closeCrop();
        });

        if (zoomInput) {
            zoomInput.addEventListener('input', function () {
                state.zoom = parseFloat(zoomInput.value || '1');
                applyCropTransform();
            });
        }

        function startDrag(event) {
            state.dragging = true;
            state.startX = event.clientX;
            state.startY = event.clientY;
            state.baseX = state.offsetX;
            state.baseY = state.offsetY;
            frame.classList.add('is-dragging');
            event.preventDefault();
        }

        function moveDrag(event) {
            if (! state.dragging) return;
            state.offsetX = state.baseX + (event.clientX - state.startX);
            state.offsetY = state.baseY + (event.clientY - state.startY);
            applyCropTransform();
        }

        function endDrag() {
            state.dragging = false;
            frame.classList.remove('is-dragging');
        }

        frame.addEventListener('pointerdown', startDrag);
        window.addEventListener('pointermove', moveDrag);
        window.addEventListener('pointerup', endDrag);

        function cropToBlob(callback) {
            var naturalWidth = image.naturalWidth;
            var naturalHeight = image.naturalHeight;
            if (! naturalWidth || ! naturalHeight) return callback(null);

            var rect = frame.getBoundingClientRect();
            var targetWidth = state.type === 'cover' ? 1600 : 640;
            var targetHeight = state.type === 'cover' ? 520 : 640;
            var canvas = document.createElement('canvas');
            canvas.width = targetWidth;
            canvas.height = targetHeight;

            var context = canvas.getContext('2d');
            context.fillStyle = '#141412';
            context.fillRect(0, 0, targetWidth, targetHeight);

            var displayWidth = naturalWidth * state.baseScale * state.zoom;
            var displayHeight = naturalHeight * state.baseScale * state.zoom;
            var frameX = rect.width / 2 + state.offsetX - displayWidth / 2;
            var frameY = rect.height / 2 + state.offsetY - displayHeight / 2;
            var canvasScaleX = targetWidth / rect.width;
            var canvasScaleY = targetHeight / rect.height;

            context.drawImage(
                image,
                frameX * canvasScaleX,
                frameY * canvasScaleY,
                displayWidth * canvasScaleX,
                displayHeight * canvasScaleY
            );

            canvas.toBlob(callback, 'image/jpeg', 0.9);
        }

        saveButton.addEventListener('click', function () {
            saveButton.disabled = true;
            setStatus(t('preview_profile_image_saving', 'Bild wird gespeichert …'), 'loading');
            cropToBlob(function (blob) {
                if (! blob) {
                    saveButton.disabled = false;
                    setStatus(t('preview_profile_image_process_failed', 'Das Bild konnte nicht verarbeitet werden.'), 'error');
                    return;
                }

                var formData = new FormData();
                formData.append('type', state.type);
                formData.append('image', blob, state.type + '.jpg');

                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                }).then(function (response) {
                    return response.json().then(function (payload) {
                        if (! response.ok) throw payload;
                        return payload;
                    });
                }).then(function (payload) {
                    if (payload.avatar_url) {
                        document.querySelectorAll('[data-profile-media-role="avatar"]').forEach(function (img) {
                            img.src = payload.avatar_url;
                        });
                        document.querySelectorAll('.sidebar-left .avatar img, .post-author .avatar img, .composer-author .avatar img').forEach(function (img) {
                            if (img.alt === @json($displayName)) img.src = payload.avatar_url;
                        });
                    }
                    if (payload.cover_url) {
                        document.querySelectorAll('[data-profile-media-role="cover"]').forEach(function (cover) {
                            cover.style.backgroundImage = "linear-gradient(180deg, rgba(26,26,24,.08), rgba(26,26,24,.88)), url('" + payload.cover_url + "')";
                        });
                    }
                    setStatus(t('preview_profile_image_saved', 'Gespeichert.'), 'success');
                    setTimeout(closeCrop, 420);
                }).catch(function (error) {
                    var message = error && error.message ? error.message : t('preview_profile_image_save_failed', 'Das Bild konnte nicht gespeichert werden.');
                    if (error && error.errors) {
                        var first = Object.values(error.errors)[0];
                        if (Array.isArray(first) && first.length) message = first[0];
                    }
                    setStatus(message, 'error');
                }).finally(function () {
                    saveButton.disabled = false;
                });
            });
        });
    });
</script>
@endpush
