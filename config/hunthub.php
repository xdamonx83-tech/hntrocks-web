<?php

$uploadMb = static fn (string $key, int $default): int => max(1, (int) env($key, $default)) * 1024;
$uploadCount = static fn (string $key, int $default): int => max(1, (int) env($key, $default));
$csvList = static fn (string $key, string $default = ''): array => array_values(array_filter(array_map(
    static fn ($value): string => trim((string) $value),
    explode(',', (string) env($key, $default))
), static fn (string $value): bool => $value !== ''));
$intCsvList = static fn (string $key, string $default = ''): array => array_values(array_filter(array_map(
    static fn ($value): int => (int) $value,
    explode(',', (string) env($key, $default))
), static fn (int $value): bool => $value > 0));

return [
    'product_name' => 'hnt.rocks',
    'template_reference' => 'Vikinger HTML Template',
    'marketplace_enabled' => false,
    'groups_are_teams' => true,
    'api_ready' => true,
    'modules' => [
        'feed', 'profiles', 'members', 'teams', 'lfg', 'team_lfg', 'messages', 'notifications',
        'media', 'moments', 'cups', 'referrals', 'gamification', 'admin', 'moderation',
    ],

    'theme' => [
        // Theme-System light: ausgeschaltet bedeutet: bestehende Blade-Views unverändert nutzen.
        // Für v2 kann später in der .env HH_THEME_ENABLED=true und HH_THEME=socialite gesetzt werden.
        'enabled' => (bool) env('HH_THEME_ENABLED', false),
        // Safe global switch for the new HNT/Lens preview theme.
        // false = current preview/admin-only behavior stays untouched.
        // true  = hnt_preview becomes the active theme for all users, with the configured fallback still available.
        'preview_live' => (bool) env('HNT_PREVIEW_THEME_LIVE', false),
        // Dedicated, reversible switch for the finished dashboard feed only.
        // This does not enable the preview theme for profile, settings, LFG or other pages.
        'dashboard_feed_live' => (bool) env('HNT_DASHBOARD_FEED_LIVE', false),
        // Dedicated switch for the new cream dashboard profile on /profile and /u/{username}.
        'profile_redesign_live' => (bool) env('HNT_PROFILE_REDESIGN_LIVE', false),
        // Separate kill switch for replacing real feature pages with theme views.
        // This keeps theme previews safe while the real /feed remains unchanged by default.
        'feed_enabled' => (bool) env('HH_THEME_FEED_ENABLED', false),
        'profile_enabled' => (bool) env('HH_THEME_PROFILE_ENABLED', false),
        // Settings pages follow the Socialite profile switch by default, but can be disabled separately.
        'settings_enabled' => (bool) env('HH_THEME_SETTINGS_ENABLED', env('HH_THEME_PROFILE_ENABLED', false)),
        'teams_enabled' => (bool) env('HH_THEME_TEAMS_ENABLED', false),
        'lfg_enabled' => (bool) env('HH_THEME_LFG_ENABLED', false),
        'team_lfg_enabled' => (bool) env('HH_THEME_TEAM_LFG_ENABLED', false),
        'active' => env('HH_THEME', 'vikinger'),
        'fallback' => env('HH_THEME_FALLBACK', 'vikinger'),
        'view_root' => 'themes',
        'asset_paths' => [
            'socialite' => env('HH_THEME_SOCIALITE_ASSET_PATH', 'assets/socialite'),
            'vikinger' => env('HH_THEME_VIKINGER_ASSET_PATH', 'assets/vikinger'),
            'hnt_preview' => env('HH_THEME_HNT_PREVIEW_ASSET_PATH', 'assets/themes/hnt_preview'),
            'rework' => env('HH_THEME_REWORK_ASSET_PATH', 'assets/themes/rework'),
        ],
        'preview' => [
            'theme' => env('HH_THEME_PREVIEW_THEME', 'hnt_preview'),
            'fallback' => env('HH_THEME_PREVIEW_FALLBACK', env('HH_THEME', 'socialite')),
            'allow_any_admin' => (bool) env('HH_THEME_PREVIEW_ALLOW_ANY_ADMIN', false),
            'allowed_user_ids' => $intCsvList('HH_THEME_PREVIEW_USER_IDS'),
            'allowed_emails' => $csvList('HH_THEME_PREVIEW_USER_EMAILS'),
        ],
    ],

    'hunt_news' => [
        'enabled' => (bool) env('HH_HUNT_NEWS_ENABLED', true),
        'source_url' => env('HH_HUNT_NEWS_SOURCE_URL', 'https://www.huntshowdown.com/news'),
        'user_id' => (int) env('HH_HUNT_NEWS_USER_ID', 1),
        // Optional: fixed date such as 2026-05-30. Empty = today at runtime.
        'auto_publish_from' => env('HH_HUNT_NEWS_AUTO_PUBLISH_FROM'),
        'timeout' => (int) env('HH_HUNT_NEWS_TIMEOUT', 15),
    ],

    'visitor_tracking' => [
        'enabled' => (bool) env('HH_VISITOR_TRACKING_ENABLED', true),
        'require_consent' => (bool) env('HH_VISITOR_TRACKING_REQUIRE_CONSENT', true),
    ],

    'security_headers' => [
        'enabled' => (bool) env('HH_SECURITY_HEADERS_ENABLED', true),
        'frame_options' => env('HH_SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
        'referrer_policy' => env('HH_SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('HH_SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'),
        'csp_report_only' => env('HH_SECURITY_CSP_REPORT_ONLY', ''),
        'hsts' => [
            'enabled' => (bool) env('HH_SECURITY_HSTS_ENABLED', false),
            'max_age' => (int) env('HH_SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => (bool) env('HH_SECURITY_HSTS_INCLUDE_SUBDOMAINS', false),
            'preload' => (bool) env('HH_SECURITY_HSTS_PRELOAD', false),
        ],
    ],

    'cups' => [
        'submission_screenshot_disk' => env('HH_CUP_SUBMISSION_SCREENSHOT_DISK', 'local'),
        'submission_cooldown_minutes' => max(0, (int) env('HH_CUP_SUBMISSION_COOLDOWN_MINUTES', 30)),
        'points_per_bounty_token' => max(0, min(10, (int) env('HH_CUP_POINTS_PER_BOUNTY_TOKEN', 2))),
        'points_per_kill' => max(0, min(10, (int) env('HH_CUP_POINTS_PER_KILL', 1))),
        'require_extract_for_score' => (bool) env('HH_CUP_REQUIRE_EXTRACT_FOR_SCORE', true),
        'require_bounty_for_score' => (bool) env('HH_CUP_REQUIRE_BOUNTY_FOR_SCORE', true),
    ],

    'ai_content_disclosure' => [
        'enabled' => (bool) env('HH_AI_CONTENT_DISCLOSURE_ENABLED', true),
        'require_label' => (bool) env('HH_AI_CONTENT_DISCLOSURE_REQUIRE_LABEL', true),
        'allow_user_opt_out' => (bool) env('HH_AI_CONTENT_DISCLOSURE_ALLOW_USER_OPT_OUT', false),
    ],

    'uploads' => [
        'avatar_kb' => $uploadMb('HH_UPLOAD_AVATAR_MB', 5),
        'cover_kb' => $uploadMb('HH_UPLOAD_COVER_MB', 8),
        'post_image_kb' => $uploadMb('HH_UPLOAD_POST_IMAGE_MB', 12),
        'post_video_kb' => $uploadMb('HH_UPLOAD_POST_VIDEO_MB', 80),
        'moment_video_kb' => $uploadMb('HH_UPLOAD_MOMENT_VIDEO_MB', 120),
        'message_attachment_kb' => $uploadMb('HH_UPLOAD_MESSAGE_ATTACHMENT_MB', 25),
        'max_post_media' => $uploadCount('HH_UPLOAD_MAX_POST_MEDIA', 6),
        'max_message_attachments' => $uploadCount('HH_UPLOAD_MAX_MESSAGE_ATTACHMENTS', 5),
    ],
];