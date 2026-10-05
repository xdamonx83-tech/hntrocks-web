<?php

return [
    // Separate from social login: Steam here is solely a linked game account.
    'steam' => [
        'enabled' => (bool) env('HH_HUNT_STEAM_LINK_ENABLED', false),
        'api_key' => env('HH_STEAM_WEB_API_KEY'),
        'app_id' => 594650, // Hunt: Showdown 1896 — never request other games.
        'timeout' => 12,
        'sync_hours' => 24,
    ],
];
