<?php

return [
    'cookie_name' => 'hnt_view_mode',
    'cookie_minutes' => 60 * 24 * 180,
    'default_preference' => 'auto',

    /*
     * Optional route-specific mobile views. Add entries here when a mobile
     * template should not mirror the desktop view path.
     */
    'route_views' => [
        'feed.index' => 'themes.hnt_mobile.feed.index',
        'feed.show' => 'themes.hnt_mobile.feed.show',
        'hall-of-fame.index' => 'themes.hnt_mobile.cups.hall-of-fame',
    ],

    /* Desktop theme prefixes are replaced with the mobile theme prefix. */
    'desktop_view_prefixes' => [
        'themes.hnt_preview.',
        'themes.socialite.',
        'themes.rework.',
    ],

    'mobile_view_prefix' => 'themes.hnt_mobile.',

    /* Requests matching these route names keep their original response. */
    'excluded_routes' => [
        'admin.*',
        'design.*',
        'socialite.header.*',
    ],
];
