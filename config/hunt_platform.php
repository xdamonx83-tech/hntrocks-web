<?php

return [
    // Separate from social login: Steam here is solely a linked game account.
    // Xbox is deliberately OFF until Microsoft grants Xbox-service access,
    // explicit permission to persist XUIDs, and approved Hunt data access.
    'xbox' => [
        'enabled' => (bool) env('HH_HUNT_XBOX_LINK_ENABLED', false),
        'client_id' => env('HH_HUNT_XBOX_CLIENT_ID'),
        'client_secret' => env('HH_HUNT_XBOX_CLIENT_SECRET'),
        'xuid_storage_authorized' => (bool) env('HH_HUNT_XBOX_XUID_STORAGE_AUTHORIZED', false),
        'hunt_stats_authorized' => (bool) env('HH_HUNT_XBOX_HUNT_STATS_AUTHORIZED', false),
    ],
    'playstation' => [
        // PSN has no generally supported third-party OAuth/statistics API.
        'enabled' => false,
    ],
    'steam' => [
        'enabled' => (bool) env('HH_HUNT_STEAM_LINK_ENABLED', false),
        'api_key' => env('HH_STEAM_WEB_API_KEY'),
        'app_id' => 594650, // Hunt: Showdown 1896 — never request other games.
        'timeout' => 12,
        'sync_hours' => 24,
    ],
];
