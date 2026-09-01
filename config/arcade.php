<?php

return [
    'invitation_ttl_minutes' => (int) env('ARCADE_INVITATION_TTL_MINUTES', 30),
    'launch_ticket_ttl_seconds' => (int) env('ARCADE_LAUNCH_TICKET_TTL_SECONDS', 60),
    'dynamic_contract_version' => 1,
    // Web games are controlled content, never remotely downloaded native code.
    'trusted_web_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ARCADE_TRUSTED_WEB_HOSTS', 'games.hnt.rocks')),
    ))),
];
