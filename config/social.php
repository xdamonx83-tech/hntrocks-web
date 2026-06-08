<?php

return [
    'enabled' => env('HH_SOCIAL_LOGIN_ENABLED', true),
    'auto_link_verified_email' => env('HH_SOCIAL_AUTO_LINK_VERIFIED_EMAIL', true),
    'auto_link_existing_email' => env('HH_SOCIAL_AUTO_LINK_EXISTING_EMAIL', true),
    'auto_link_email_providers' => array_values(array_filter(array_map('trim', explode(',', (string) env('HH_SOCIAL_AUTO_LINK_EMAIL_PROVIDERS', 'google,discord,twitch,microsoft,facebook'))))),
    'synthetic_email_domain' => env('HH_SOCIAL_SYNTHETIC_EMAIL_DOMAIN', 'social-login.hnt.rocks'),
    'timeout' => (int) env('HH_SOCIAL_HTTP_TIMEOUT', 20),
    'mobile' => [
        'deep_link_url' => env('HH_SOCIAL_MOBILE_DEEP_LINK_URL', 'hntrocks://auth/social'),
        'code_ttl_minutes' => max(1, (int) env('HH_SOCIAL_MOBILE_CODE_TTL_MINUTES', 5)),
    ],

    'providers' => [
        'google' => [
            'enabled' => env('HH_GOOGLE_LOGIN_ENABLED', false),
            'client_id' => env('HH_GOOGLE_CLIENT_ID'),
            'client_secret' => env('HH_GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('HH_GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'https://hnt.rocks'), '/') . '/auth/google/callback'),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'user_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
            'tokeninfo_url' => 'https://oauth2.googleapis.com/tokeninfo',
            'scopes' => ['openid', 'email', 'profile'],
            'native_enabled' => env('HH_GOOGLE_NATIVE_LOGIN_ENABLED', env('HH_GOOGLE_LOGIN_ENABLED', false)),
            'native_allowed_client_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env(
                'HH_GOOGLE_NATIVE_ALLOWED_CLIENT_IDS',
                implode(',', array_filter([
                    (string) env('HH_GOOGLE_CLIENT_ID', ''),
                    (string) env('HH_GOOGLE_ANDROID_CLIENT_ID', ''),
                ]))
            ))))),
        ],

        'discord' => [
            'enabled' => env('HH_DISCORD_LOGIN_ENABLED', false),
            'client_id' => env('HH_DISCORD_CLIENT_ID'),
            'client_secret' => env('HH_DISCORD_CLIENT_SECRET'),
            'redirect_uri' => env('HH_DISCORD_REDIRECT_URI', rtrim((string) env('APP_URL', 'https://hnt.rocks'), '/') . '/auth/discord/callback'),
            'authorize_url' => 'https://discord.com/oauth2/authorize',
            'token_url' => 'https://discord.com/api/oauth2/token',
            'user_url' => 'https://discord.com/api/users/@me',
            'scopes' => ['identify', 'email'],
        ],

        'twitch' => [
            'enabled' => env('HH_TWITCH_LOGIN_ENABLED', false),
            'client_id' => env('HH_TWITCH_CLIENT_ID'),
            'client_secret' => env('HH_TWITCH_CLIENT_SECRET'),
            'redirect_uri' => env('HH_TWITCH_REDIRECT_URI', rtrim((string) env('APP_URL', 'https://hnt.rocks'), '/') . '/auth/twitch/callback'),
            'authorize_url' => 'https://id.twitch.tv/oauth2/authorize',
            'token_url' => 'https://id.twitch.tv/oauth2/token',
            'user_url' => 'https://api.twitch.tv/helix/users',
            'scopes' => ['user:read:email'],
        ],

        'steam' => [
            'enabled' => env('HH_STEAM_LOGIN_ENABLED', false),
            'web_api_key' => env('HH_STEAM_WEB_API_KEY'),
        ],


        'microsoft' => [
            'enabled' => env('HH_MICROSOFT_LOGIN_ENABLED', false),
            'client_id' => env('HH_MICROSOFT_CLIENT_ID'),
            'client_secret' => env('HH_MICROSOFT_CLIENT_SECRET'),
            'redirect_uri' => env('HH_MICROSOFT_REDIRECT_URI', rtrim((string) env('APP_URL', 'https://hnt.rocks'), '/') . '/auth/microsoft/callback'),
            'authorize_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'user_url' => 'https://graph.microsoft.com/v1.0/me',
            'scopes' => ['openid', 'email', 'profile', 'User.Read'],
        ],

        'facebook' => [
            'enabled' => env('HH_FACEBOOK_LOGIN_ENABLED', false),
            'client_id' => env('HH_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('HH_FACEBOOK_CLIENT_SECRET'),
            'redirect_uri' => env('HH_FACEBOOK_REDIRECT_URI', rtrim((string) env('APP_URL', 'https://hnt.rocks'), '/') . '/auth/facebook/callback'),
            'authorize_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
            'user_url' => 'https://graph.facebook.com/v19.0/me',
            'scopes' => ['email', 'public_profile'],
        ],
    ],
];
