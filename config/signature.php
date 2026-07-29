<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Signature Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default signature "driver" that will be used on
    | requests. By default, we will use the "local" driver.
    |
    | Supported: "local", "docuseal"
    |
    */
    'default' => env('SIGNATURE_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Signature Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the signature drivers used by your app.
    |
    */
    'providers' => [
        'local' => [
            'driver' => 'local',
        ],

        'docuseal' => [
            'driver' => 'docuseal',
            'api_url' => env('DOCUSEAL_API_URL', 'https://api.docuseal.com'),
            'api_token' => env('DOCUSEAL_API_TOKEN'),
            'webhook_url' => env('DOCUSEAL_WEBHOOK_URL'),
        ],
    ],
];
