<?php

return [
    'invitation_ttl_minutes' => (int) env('ARCADE_INVITATION_TTL_MINUTES', 30),
    // Web games are controlled content, never remotely downloaded native code.
    'trusted_web_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ARCADE_TRUSTED_WEB_HOSTS', 'games.hnt.rocks')),
    ))),
];
