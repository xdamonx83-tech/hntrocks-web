<?php

return [
    'enabled' => (bool) env('AI_COMMUNITY_ENABLED', false),
    'auto_generate' => (bool) env('AI_COMMUNITY_AUTO_GENERATE', false),
    'review_enabled' => (bool) env('AI_COMMUNITY_REVIEW_ENABLED', false),
    'key' => env('OPENAI_API_KEY') ?: env('HH_TRANSLATION_OPENAI_API_KEY'),
    'model' => env('AI_COMMUNITY_MODEL', 'gpt-4.1-mini'),
    'monthly_budget_microusd' => (int) round((float) env('AI_COMMUNITY_MONTHLY_BUDGET_USD', 5) * 1000000),
    'input_cost_per_million' => (float) env('AI_COMMUNITY_INPUT_COST_PER_MILLION_USD', 0.40),
    'output_cost_per_million' => (float) env('AI_COMMUNITY_OUTPUT_COST_PER_MILLION_USD', 1.60),
];
