<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | KLASSCI Master API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'API Master qui gère le paywall centralisé.
    | L'API Master vérifie les quotas d'abonnement et les limites d'usage
    | pour tous les tenants (établissements scolaires).
    |
    | Cache: 5 minutes pour limiter les appels API
    | Fallback: Système local si API Master indisponible
    |
    */
    'master' => [
        'api_url' => env('MASTER_API_URL'),
        'api_token' => env('MASTER_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Browserless.io Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour Browserless.io - Service cloud Chrome headless
    | pour génération PDF avec support CSS Grid complet.
    |
    | En développement local: utilise Puppeteer local (enabled=false)
    | En production: utilise Browserless.io (enabled=true)
    |
    */
    'browserless' => [
        'enabled' => env('BROWSERLESS_ENABLED', false),
        'api_key' => env('BROWSERLESS_API_KEY'),
        'endpoint' => env('BROWSERLESS_ENDPOINT', 'https://production-sfo.browserless.io'),
    ],

    'group_sso' => [
        'secret' => env('GROUP_SSO_SHARED_SECRET'),
    ],

    'mailpulse' => [
        'enabled' => env('MAILPULSE_ENABLED', true),
        'base_url' => env('MAILPULSE_BASE_URL', 'https://mailpulse-two.vercel.app'),
        'api_key' => env('MAILPULSE_API_KEY'),
        'timeout' => (int) env('MAILPULSE_TIMEOUT', 20),
        'contacts_endpoint' => env('MAILPULSE_CONTACTS_ENDPOINT', '/api/v1/contacts'),
        'messages_endpoint' => env('MAILPULSE_MESSAGES_ENDPOINT', '/api/v1/messages'),
        'sender_email' => env('MAILPULSE_SENDER_EMAIL'),
        'sender_name' => env('MAILPULSE_SENDER_NAME', 'KLASSCI'),
        'default_language' => env('MAILPULSE_DEFAULT_LANGUAGE', 'fr'),
        'real_workflows_enabled' => env('MAILPULSE_REAL_WORKFLOWS_ENABLED', false),
        'test_api_secret' => env('TEST_API_SECRET'),
        'test_notification_email' => env('TEST_NOTIFICATION_EMAIL'),
        'test_notification_phone' => env('TEST_NOTIFICATION_PHONE'),
        'test_notification_phones' => env('TEST_NOTIFICATION_PHONES'),
    ],

];
