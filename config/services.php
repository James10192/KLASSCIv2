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
        // External-application rail: carries the parent chatbot conversation.
        // Notifications keep using the api_key rail above.
        'external_application_key' => env('MAILPULSE_EXTERNAL_APPLICATION_KEY'),
        'external_organization_id' => env('MAILPULSE_EXTERNAL_ORGANIZATION_ID'),
        'external_command_key_id' => env('MAILPULSE_EXTERNAL_COMMAND_KEY_ID'),
        'external_command_secret' => env('MAILPULSE_EXTERNAL_COMMAND_SECRET'),
        'external_callback_key_id' => env('MAILPULSE_EXTERNAL_CALLBACK_KEY_ID'),
        'external_callback_secret' => env('MAILPULSE_EXTERNAL_CALLBACK_SECRET'),
        'parent_chatbot_service_secret' => env('MAILPULSE_PARENT_CHATBOT_SERVICE_SECRET'),
        'parent_chatbot_webhook_secret' => env('MAILPULSE_PARENT_CHATBOT_WEBHOOK_SECRET'),
        'parent_chatbot_code_pepper' => env('MAILPULSE_PARENT_CHATBOT_CODE_PEPPER'),
        'parent_chatbot_phone_hash_key' => env('MAILPULSE_PARENT_CHATBOT_PHONE_HASH_KEY'),
        'parent_chatbot_signature_ttl' => (int) env('MAILPULSE_PARENT_CHATBOT_SIGNATURE_TTL', 300),
        'parent_chatbot_link_code_ttl' => (int) env('MAILPULSE_PARENT_CHATBOT_LINK_CODE_TTL', 15),
        'parent_chatbot_report_card_ttl_hours' => (int) env('MAILPULSE_PARENT_CHATBOT_REPORT_CARD_TTL_HOURS', 48),
        'parent_chatbot_inbound_response_retention_hours' => (int) env('MAILPULSE_PARENT_CHATBOT_INBOUND_RESPONSE_RETENTION_HOURS', 168),
        'parent_chatbot_link_template_name' => env('MAILPULSE_PARENT_CHATBOT_LINK_TEMPLATE_NAME'),
        'parent_chatbot_link_template_language' => env('MAILPULSE_PARENT_CHATBOT_LINK_TEMPLATE_LANGUAGE', 'fr'),
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
