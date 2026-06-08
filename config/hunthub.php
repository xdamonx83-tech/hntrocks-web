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
        'images' => (bool) env('HH_AI_CONTENT_DISCLOSURE_IMAGES', true),
        'videos' => (bool) env('HH_AI_CONTENT_DISCLOSURE_VIDEOS', true),
        'possible_threshold' => (float) env('HH_AI_CONTENT_DISCLOSURE_THRESHOLD', 0.72),
        'model' => env('HH_AI_CONTENT_DISCLOSURE_MODEL', 'gpt-5-nano'),
        'fallback_model' => env('HH_AI_CONTENT_DISCLOSURE_FALLBACK_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('HH_AI_CONTENT_DISCLOSURE_TIMEOUT', 20),
        'max_files' => (int) env('HH_AI_CONTENT_DISCLOSURE_MAX_FILES', 4),
        'image_max_side' => (int) env('HH_AI_CONTENT_DISCLOSURE_IMAGE_MAX_SIDE', 768),
        'image_jpeg_quality' => (int) env('HH_AI_CONTENT_DISCLOSURE_IMAGE_JPEG_QUALITY', 72),
        'ffmpeg_binary' => env('HH_AI_CONTENT_DISCLOSURE_FFMPEG_BINARY', 'ffmpeg'),
        'video_sample_seconds' => (array_values(array_filter(array_map(
            static fn ($value): int => max(0, (int) trim($value)),
            explode(',', (string) env('HH_AI_CONTENT_DISCLOSURE_VIDEO_SAMPLE_SECONDS', '1,4,8'))
        ))) ?: [1, 4, 8]),
        'openai_api_key' => env('HH_AI_DISCLOSURE_OPENAI_API_KEY')
            ?: (env('HH_TRANSLATION_OPENAI_API_KEY')
            ?: (env('HH_OPENAI_API_KEY')
            ?: (env('OPENAI_API_KEY')
            ?: (env('HH_MEDIA_OPENAI_API_KEY')
            ?: env('HH_CUP_OPENAI_API_KEY'))))),
    ],

    'feed_video_transcoding' => [
        'enabled' => (bool) env('HH_FEED_VIDEO_TRANSCODING_ENABLED', true),
        'queue' => env('HH_VIDEO_TRANSCODING_QUEUE', env('HH_FEED_VIDEO_TRANSCODING_QUEUE', 'media')),
        'ffmpeg_binary' => env('HH_VIDEO_FFMPEG_BINARY', env('HH_FEED_VIDEO_FFMPEG_BINARY', env('HH_MEDIA_MODERATION_FFMPEG_BINARY', 'ffmpeg'))),
        'ffprobe_binary' => env('HH_VIDEO_FFPROBE_BINARY', env('HH_FEED_VIDEO_FFPROBE_BINARY', 'ffprobe')),
        'max_width' => max(240, (int) env('HH_FEED_VIDEO_MAX_WIDTH', 1280)),
        'max_height' => max(240, (int) env('HH_FEED_VIDEO_MAX_HEIGHT', 1280)),
        'fps' => max(15, min(60, (int) env('HH_FEED_VIDEO_FPS', 30))),
        'crf' => max(18, min(35, (int) env('HH_FEED_VIDEO_CRF', 24))),
        'preset' => env('HH_FEED_VIDEO_PRESET', 'medium'),
        'audio_bitrate' => env('HH_FEED_VIDEO_AUDIO_BITRATE', '128k'),
        'thumbnail_width' => max(360, (int) env('HH_FEED_VIDEO_THUMBNAIL_WIDTH', 720)),
        'generate_thumbnail' => (bool) env('HH_FEED_VIDEO_GENERATE_THUMBNAIL', true),
        'delete_original' => (bool) env('HH_FEED_VIDEO_DELETE_ORIGINAL', true),
        'timeout' => max(60, (int) env('HH_FEED_VIDEO_TRANSCODING_TIMEOUT', 900)),
    ],

    'moment_video_transcoding' => [
        'enabled' => (bool) env('HH_MOMENT_VIDEO_TRANSCODING_ENABLED', true),
        'queue' => env('HH_VIDEO_TRANSCODING_QUEUE', env('HH_MOMENT_VIDEO_TRANSCODING_QUEUE', 'media')),
        'ffmpeg_binary' => env('HH_VIDEO_FFMPEG_BINARY', env('HH_MOMENT_VIDEO_FFMPEG_BINARY', env('HH_MEDIA_MODERATION_FFMPEG_BINARY', 'ffmpeg'))),
        'ffprobe_binary' => env('HH_VIDEO_FFPROBE_BINARY', env('HH_MOMENT_VIDEO_FFPROBE_BINARY', 'ffprobe')),
        'width' => max(360, (int) env('HH_MOMENT_VIDEO_WIDTH', 720)),
        'height' => max(640, (int) env('HH_MOMENT_VIDEO_HEIGHT', 1280)),
        'fps' => max(15, min(60, (int) env('HH_MOMENT_VIDEO_FPS', 30))),
        'crf' => max(18, min(35, (int) env('HH_MOMENT_VIDEO_CRF', 24))),
        'preset' => env('HH_MOMENT_VIDEO_PRESET', 'medium'),
        'audio_bitrate' => env('HH_MOMENT_VIDEO_AUDIO_BITRATE', '128k'),
        'thumbnail_width' => max(360, (int) env('HH_MOMENT_VIDEO_THUMBNAIL_WIDTH', 720)),
        'generate_thumbnail' => (bool) env('HH_MOMENT_VIDEO_GENERATE_THUMBNAIL', true),
        'delete_original' => (bool) env('HH_MOMENT_VIDEO_DELETE_ORIGINAL', true),
        'timeout' => max(60, (int) env('HH_MOMENT_VIDEO_TRANSCODING_TIMEOUT', 900)),
    ],

    'media_image_optimization' => [
        'enabled' => (bool) env('HH_MEDIA_IMAGE_OPTIMIZATION_ENABLED', true),
        'convert_to_webp' => (bool) env('HH_MEDIA_IMAGE_OPTIMIZATION_CONVERT_TO_WEBP', true),
        'max_width' => max(480, (int) env('HH_MEDIA_IMAGE_OPTIMIZATION_MAX_WIDTH', 1600)),
        'max_height' => max(480, (int) env('HH_MEDIA_IMAGE_OPTIMIZATION_MAX_HEIGHT', 1600)),
        'jpeg_quality' => max(50, min(95, (int) env('HH_MEDIA_IMAGE_OPTIMIZATION_JPEG_QUALITY', 78))),
        'webp_quality' => max(50, min(95, (int) env('HH_MEDIA_IMAGE_OPTIMIZATION_WEBP_QUALITY', 78))),
        'png_compression' => max(0, min(9, (int) env('HH_MEDIA_IMAGE_OPTIMIZATION_PNG_COMPRESSION', 7))),
        'min_savings_bytes' => max(0, (int) env('HH_MEDIA_IMAGE_OPTIMIZATION_MIN_SAVINGS_BYTES', 32768)),
        'max_source_pixels' => max(1000000, (int) env('HH_MEDIA_IMAGE_OPTIMIZATION_MAX_SOURCE_PIXELS', 32000000)),
        'excluded_contexts' => array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            explode(',', (string) env('HH_MEDIA_IMAGE_OPTIMIZATION_EXCLUDED_CONTEXTS', 'cups/screenshots'))
        ))),
    ],

    'media_moderation' => [
        'enabled' => (bool) env('HH_MEDIA_MODERATION_ENABLED', false),
        'images' => (bool) env('HH_MEDIA_MODERATION_IMAGES', true),
        'videos' => (bool) env('HH_MEDIA_MODERATION_VIDEOS', true),
        'texts' => (bool) env('HH_MEDIA_MODERATION_TEXTS', true),
        'block_on_error' => (bool) env('HH_MEDIA_MODERATION_BLOCK_ON_ERROR', false),
        'block_review' => (bool) env('HH_MEDIA_MODERATION_BLOCK_REVIEW', false),
        'block_confidence' => (float) env('HH_MEDIA_MODERATION_BLOCK_CONFIDENCE', 0.74),
        'model' => env('HH_MEDIA_MODERATION_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('HH_MEDIA_MODERATION_TIMEOUT', 30),
        'ffmpeg_binary' => env('HH_MEDIA_MODERATION_FFMPEG_BINARY', 'ffmpeg'),
        'video_sample_seconds' => (array_values(array_filter(array_map(
            static fn ($value): int => max(0, (int) trim($value)),
            explode(',', (string) env('HH_MEDIA_MODERATION_VIDEO_SAMPLE_SECONDS', '1,3,7'))
        ))) ?: [1, 3, 7]),
        'openai_api_key' => env('HH_MEDIA_OPENAI_API_KEY')
            ?: (env('HH_OPENAI_API_KEY')
            ?: (env('OPENAI_API_KEY')
            ?: env('HH_CUP_OPENAI_API_KEY'))),
    ],

    'upload_limits' => [
        // Alle Größenwerte werden in der .env in Megabyte gesetzt.
        // Laravel-Validatoren erwarten intern Kilobyte, deshalb werden sie hier umgerechnet.
        'profile_avatar_kb' => $uploadMb('HH_UPLOAD_PROFILE_AVATAR_MB', 2),
        'profile_cover_kb' => $uploadMb('HH_UPLOAD_PROFILE_COVER_MB', 4),
        'team_avatar_kb' => $uploadMb('HH_UPLOAD_TEAM_AVATAR_MB', 2),
        'team_cover_kb' => $uploadMb('HH_UPLOAD_TEAM_COVER_MB', 4),
        'feed_media_kb' => $uploadMb('HH_UPLOAD_FEED_MEDIA_MB', 100),
        'feed_media_count' => $uploadCount('HH_UPLOAD_FEED_MEDIA_COUNT', 12),
        'team_feed_media_kb' => $uploadMb('HH_UPLOAD_TEAM_FEED_MEDIA_MB', 100),
        'team_feed_media_count' => $uploadCount('HH_UPLOAD_TEAM_FEED_MEDIA_COUNT', 12),
        'media_library_file_kb' => $uploadMb('HH_UPLOAD_MEDIA_LIBRARY_FILE_MB', 50),
        'media_library_count' => $uploadCount('HH_UPLOAD_MEDIA_LIBRARY_COUNT', 10),
        'moment_video_kb' => $uploadMb('HH_UPLOAD_MOMENT_VIDEO_MB', 200),
        'moment_cover_kb' => $uploadMb('HH_UPLOAD_MOMENT_COVER_MB', 8),
        'cup_cover_kb' => $uploadMb('HH_UPLOAD_CUP_COVER_MB', 6),
        'cup_submission_screenshot_kb' => $uploadMb('HH_UPLOAD_CUP_SUBMISSION_SCREENSHOT_MB', 10),
        'gamification_icon_kb' => $uploadMb('HH_UPLOAD_GAMIFICATION_ICON_MB', 2),
        'loadout_challenge_media_kb' => $uploadMb('HH_UPLOAD_LOADOUT_CHALLENGE_MEDIA_MB', 100),
    ],
];
