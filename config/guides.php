<?php

return [
    'upload_disk' => env('HH_GUIDES_UPLOAD_DISK', 'local'),
    'upload_max_kb' => max(1024, (int) env('HH_GUIDES_UPLOAD_MAX_KB', 8192)),
    'cleanup' => [
        'hours' => max(1, (int) env('HH_GUIDES_MEDIA_CLEANUP_HOURS', 48)),
        'limit' => max(1, min(500, (int) env('HH_GUIDES_MEDIA_CLEANUP_LIMIT', 100))),
    ],
    'reputation' => [
        'published' => (int) env('HH_GUIDES_REPUTATION_PUBLISHED', 10),
        'helpful' => (int) env('HH_GUIDES_REPUTATION_HELPFUL', 1),
    ],
];
