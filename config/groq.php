<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Groq API Key
    |--------------------------------------------------------------------------
    |
    | Utilise la clé API Groq pour authentifier les requêtes.
    */
    'api_key' => env('GROQ_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Modèles Groq
    |--------------------------------------------------------------------------
    |
    | Modèle principal + fallback en cas de timeout/erreur réseau.
    */
    'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
    'fallback_model' => env('GROQ_FALLBACK_MODEL', 'llama-3.1-8b-instant'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),

    /*
    |--------------------------------------------------------------------------
    | Paramètres de requête
    |--------------------------------------------------------------------------
    */
    'request_timeout' => (int) env('GROQ_REQUEST_TIMEOUT', 20),
    'temperature' => (float) env('GROQ_TEMPERATURE', 0.2),
    'top_p' => (float) env('GROQ_TOP_P', 0.9),
    'max_tokens' => (int) env('GROQ_MAX_TOKENS', 600),
];
