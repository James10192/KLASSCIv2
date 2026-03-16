<?php

declare(strict_types=1);

return [

    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),

    'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/'),

    'request_timeout' => (int) env('ANTHROPIC_REQUEST_TIMEOUT', 30),

    'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 2048),

    'temperature' => (float) env('ANTHROPIC_TEMPERATURE', 0.2),

    'retry_attempts' => (int) env('ANTHROPIC_RETRY_ATTEMPTS', 2),

    'retry_delay_ms' => (int) env('ANTHROPIC_RETRY_DELAY_MS', 1000),

];
