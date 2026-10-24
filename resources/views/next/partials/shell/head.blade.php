
@unless(($hntPublicMaps ?? false) || ($hntPublicNews ?? false) || ($hntPublicCup ?? false))
<meta name="robots" content="noindex,nofollow,noarchive"/>
@endunless
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta content="#0d0d0d" name="theme-color"/>
<title>{{ ($hntPublicMaps ?? false) ? $mapsSeoTitle : 'HNT.ROCKS – '.($nextPageTitle ?? (($feedFilter ?? 'all') === 'saved' ? \App\Http\Controllers\Next\NextAccountText::get('saved_page') : 'Feed')) }}</title>




<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500;600;700&amp;display=swap" rel="stylesheet" data-hnt-standard-font="exo"/>
@vite(['resources/css/next/hntv3.css'], 'build/next')
<link rel="stylesheet" href="{{ asset('assets/themes/rework/hnt-cosmetics-v3.css') }}?v=20261017"/>
<link rel="stylesheet" href="/build/next/hntv3-js/feed-cosmetics.css?v=20261018-cosmetic-comments1"/>
<link rel="stylesheet" href="/build/next/hntv3-js/ready-widget.css?v=flat-rows-20261017"/>
<script>window.HNTNextLocaleMoodLabels=@json(\App\Http\Controllers\Next\NextText::moods());</script>
<script>window.HNTFeedLabels=@json(\App\Http\Controllers\Next\NextText::feedActionLabels());</script>
<script defer src="/build/next/hntv3-js/dialog-i18n.js?v=20261018c"></script>
<script defer src="/build/next/hntv3-js/feed-shell-live.js?v=20261018-cleanup1"></script>
<script defer src="/build/next/hntv3-js/feed-new-posts.js?v=20261022-feed-live-pill1"></script>
<link rel="stylesheet" href="/build/next/hntv3-js/feed-new-posts.css?v=20261022-feed-live-pill1"/>
<script defer src="/build/next/hntv3-js/navigation.js?v=20261018-cleanup1"></script>
<script defer src="/build/next/hntv3-js/account-menu.js?v=account-actions-20261017"></script>
<script defer src="/build/next/hntv3-js/social-dropdowns.js?v=20261021-singlepost1"></script>
<script defer src="/build/next/hntv3-js/feed-live-ui.js?v=20261018-multimedia1"></script>
<script type="module" src="/build/next/hntv3-js/videojs-neutral.bundle.js?v=videojs10-20261016"></script>
<link rel="stylesheet" href="/build/next/hntv3-js/author-profile-links.css?v=20261021-author1">
<script defer src="/build/next/hntv3-js/author-profile-links.js?v=20261021-author1"></script>
<script defer src="/build/next/hntv3-js/media-viewer.js?v=20261022-videojs-neutral2"></script>
<script defer src="/build/next/hntv3-js/media-viewer-comments.js?v=20261022-mobile-fixed-composer1"></script>
<script defer src="/build/next/hntv3-js/hnt-live-adapter.js?v=20261021-live-comments2"></script>
<script defer src="/build/next/hntv3-js/hnt-next-writes.js?v=20261022-immediate-reply1"></script>
<script defer src="/build/next/hntv3-js/like-embers.js?v=heart-thumb-20261017"></script>
<script defer src="/build/next/hntv3-js/composer-dispatch.js?v=20261021-categories1"></script>
<script defer src="/build/next/hntv3-js/composer-extras.js?v=20261018-multimedia1"></script>
<script defer src="/build/next/hntv3-js/feed-comment-media.js?v=20261022-picker-refine1"></script>
<script defer src="/build/next/hntv3-js/feed-discussions.js?v=20261021-author1"></script>
<script defer src="/build/next/hntv3-js/post-comments-modal.js?v=20261022-immediate-reply1"></script>
<link rel="stylesheet" href="/build/next/hntv3-js/comment-actions.css?v=20261021-menu-drawer2">
<script defer src="/build/next/hntv3-js/comment-actions.js?v=20261021-menu-drawer2"></script>
<link rel="stylesheet" href="/build/next/hntv3-js/mention-display.css?v=20261021-feed-hr-white2">
<link rel="stylesheet" href="/build/next/hntv3-js/mention-autocomplete.css?v=20261021-mentions1">
<script defer src="/build/next/hntv3-js/mention-autocomplete.js?v=20261021-mentions1"></script>
<script defer src="/build/next/hntv3-js/post-navigation.js?v=20261022-immediate-reply1"></script>
<script defer src="/build/next/hntv3-js/hnt-next-polls.js?v=poll-voters-20261017"></script>
<script defer src="/build/next/hntv3-js/post-options-menu.js?v=20261018-cleanup1"></script>
<script defer src="/build/next/hntv3-js/hnt-post-management.js?v=locale-alerts-20261018"></script>
<link rel="stylesheet" href="/build/next/hntv3-js/post-category-labels.css?v=20261021-categories1">
<script defer src="/build/next/hntv3-js/post-type-badges.js?v=20261021-categories1"></script>
<script defer src="/build/next/hntv3-js/share-modal.js?v=20261021-singlepost1"></script>
<script defer src="/build/next/hntv3-js/font-boot.js"></script>
<script defer src="/build/next/hntv3-js/activity-widget.js?v=live-20261017"></script>

<script defer src="/assets/socialite/js/hnt-presence-heartbeat.js?v=172f-a"></script>
<script defer src="/build/next/hntv3-js/hunter-hud.js?v=cosmetics-online-20261017c"></script>
<script defer src="/build/next/hntv3-js/widget-focus.js?v=locale-20261018"></script>
<script defer src="/build/next/hntv3-js/mobile-header.js"></script>
<script defer src="/build/next/hntv3-js/search-experience.js?v=20261018-cleanup1"></script>
<script defer src="/build/next/hntv3-js/language-switcher.js?v=locale-20261021-messenger1"></script>
<script defer src="/build/next/hntv3-js/prestige-widget.js?v=prestige-shared-profile-20261018"></script>
<script defer src="/build/next/hntv3-js/prestige-live.js?v=prestige-ready-toast-20261017"></script>


<link rel="stylesheet" href="/build/next/hntv3-js/feed-comment-attachments.css?v=20261022-picker-refine1"/>
@php
$hntPostExpandLocale = in_array(app()->getLocale(), ['de', 'en', 'es', 'ru'], true) ? app()->getLocale() : 'en';
$hntPostExpandLabels = require base_path('lang/'.$hntPostExpandLocale.'/next_post_expand.php');
@endphp
<script>window.HNTPostExpandLabels = @json($hntPostExpandLabels);</script>
<link rel="stylesheet" href="/build/next/hntv3-js/feed-live-overrides.css?v=20261022-mobile-fixed-composer1"/>
<link rel="stylesheet" href="/build/next/hntv3-js/feed-video-viewer.css?v=20261022-videojs-neutral2"/>
<script defer src="/build/next/hntv3-js/feed-post-expand.js?v=20261021-readmore1"></script>
