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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'docuseal' => [
        'endpoint' => env('DOCUSEAL_ENDPOINT'),
        'api_key' => env('DOCUSEAL_API_KEY'),
    ],

    'openweathermap' => [
        'api_key' => env('OPENWEATHERMAP_API_KEY'),
    ],

    'bridge' => [
        'base_url' => env('BRIDGE_BASE_URL', 'https://api.bridgeapi.io/v3'),
        'client_id' => env('BRIDGE_CLIENT_ID'),
        'client_secret' => env('BRIDGE_CLIENT_SECRET'),
        'version' => '2025-01-15',
        'payments' => [
            'base_url' => env('BRIDGE_PAYMENTS_BASE_URL', 'https://api.bridgeapi.io/v3/payment'),
            'client_id' => env('BRIDGE_PAYMENTS_CLIENT_ID'),
            'client_secret' => env('BRIDGE_PAYMENTS_CLIENT_SECRET'),
            'version' => env('BRIDGE_PAYMENTS_VERSION', '2025-01-15'),
            'callback_url' => env('BRIDGE_PAYMENTS_CALLBACK_URL'),
            'sandbox' => env('BRIDGE_PAYMENTS_SANDBOX', true),
        ],
    ],

    'api_entreprise' => [
        'token' => env('API_ENTREPRISE_TOKEN', ''),
        'base_url' => env('API_ENTREPRISE_BASE_URL', 'https://entreprise.api.gouv.fr'),
    ],

    'github' => [
        'repo' => env('GITHUB_REPO', null),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'digiposte' => [
        'base_url' => env('DIGIPOSTE_URL'),
        'api_key' => env('DIGIPOSTE_API_KEY', ''),
        'client_id' => env('DIGIPOSTE_CLIENT_ID', ''),
        'client_secret' => env('DIGIPOSTE_CLIENT_SECRET', ''),
        'partner_id' => env('DIGIPOSTE_PARTNER_ID', ''),
    ],
];
