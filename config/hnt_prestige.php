<?php

return [
    // Keep reward-changing requests off until the final UI/QA rollout is ready.
    'enabled' => (bool) env('HNT_PRESTIGE_ENABLED', true),
];
