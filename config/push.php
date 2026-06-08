<?php

return [
    'fcm' => [
        'project_id' => env('HNT_PUSH_FCM_PROJECT_ID'),
        'service_account_path' => env('HNT_PUSH_FCM_SERVICE_ACCOUNT_PATH'),
        'service_account_json' => env('HNT_PUSH_FCM_SERVICE_ACCOUNT_JSON'),
        'timeout' => (int) env('HNT_PUSH_FCM_TIMEOUT', 15),
    ],
];
