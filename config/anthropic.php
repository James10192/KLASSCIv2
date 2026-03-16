<?php

declare(strict_types=1);

return [

    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),

    'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/'),

    'request_timeout' => (int) env('ANTHROPIC_REQUEST_TIMEOUT', 30),

];
