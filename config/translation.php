<?php

return [
    // openai = bisheriges Verhalten
    // local_first = lokaler Argos-Dienst, bei Fehler optional OpenAI
    // local = lokaler Argos-Dienst, OpenAI nur wenn fallback=true
    'provider' => env('HH_TRANSLATION_PROVIDER', 'openai'),

    'local_url' => env('HH_TRANSLATION_LOCAL_URL', 'http://127.0.0.1:8787/translate'),
    'local_timeout' => max(1, min(30, (int) env('HH_TRANSLATION_LOCAL_TIMEOUT', 12))),
    'local_token' => env('HH_TRANSLATION_LOCAL_TOKEN', ''),

    'openai_fallback' => (bool) env('HH_TRANSLATION_OPENAI_FALLBACK', true),
    'openai_model' => env('HH_TRANSLATION_OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_timeout' => max(5, min(60, (int) env('HH_TRANSLATION_OPENAI_TIMEOUT', 20))),
    'openai_api_key' => env('HH_TRANSLATION_OPENAI_API_KEY')
        ?: (env('HH_OPENAI_API_KEY')
        ?: (env('OPENAI_API_KEY')
        ?: (env('HH_MEDIA_OPENAI_API_KEY')
        ?: env('HH_CUP_OPENAI_API_KEY')))),
];
