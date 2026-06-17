<?php

return [
    'app_key' => config('broadcasting.connections.reverb.key'),
    'host' => env('REVERB_CLIENT_HOST'),
    'port' => (int) env('REVERB_CLIENT_PORT', 443),
    'scheme' => env('REVERB_CLIENT_SCHEME', 'https'),
    'auth_endpoint' => '/broadcasting/auth',
];
