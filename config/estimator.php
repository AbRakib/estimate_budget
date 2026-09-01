<?php

return [
    'default_hourly_rate' => env('ESTIMATOR_DEFAULT_HOURLY_RATE', 75),
    'currency' => env('ESTIMATOR_CURRENCY', 'USD'),
    'default_country' => env('ESTIMATOR_DEFAULT_COUNTRY', 'BD'),
    'hours_per_day' => env('ESTIMATOR_HOURS_PER_DAY', 8),

    'countries' => [
        'BD' => ['name' => 'Bangladesh', 'currency' => 'BDT'],
        'US' => ['name' => 'United States', 'currency' => 'USD'],
        'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP'],
        'EU' => ['name' => 'European Union', 'currency' => 'EUR'],
        'CA' => ['name' => 'Canada', 'currency' => 'CAD'],
        'AU' => ['name' => 'Australia', 'currency' => 'AUD'],
        'IN' => ['name' => 'India', 'currency' => 'INR'],
        'SG' => ['name' => 'Singapore', 'currency' => 'SGD'],
        'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED'],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 4096),
    ],
];
