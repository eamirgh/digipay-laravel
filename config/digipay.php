<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => env('DIGIPAY_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox / Staging Mode
    |--------------------------------------------------------------------------
    |
    | When set to true, requests will be routed to Digipay UAT staging servers.
    |
    */
    'sandbox' => env('DIGIPAY_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | API Base URLs
    |--------------------------------------------------------------------------
    */
    'base_url' => env('DIGIPAY_BASE_URL'),
    'staging_base_url' => env('DIGIPAY_STAGING_BASE_URL', 'https://uat.mydigipay.info/digipay/api'),
    'production_base_url' => env('DIGIPAY_PRODUCTION_BASE_URL', 'https://api.mydigipay.com/digipay/api'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Credentials
    |--------------------------------------------------------------------------
    */
    'client_id' => env('DIGIPAY_CLIENT_ID'),
    'client_secret' => env('DIGIPAY_CLIENT_SECRET'),
    'username' => env('DIGIPAY_USERNAME'),
    'password' => env('DIGIPAY_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Default Ticket Type
    |--------------------------------------------------------------------------
    |
    | 0: IPG
    | 5: CREDIT
    | 11: WALLET / general UPG ticket (default)
    | 13: BNPL
    | 24: CREDIT-CARD
    |
    */
    'default_type' => env('DIGIPAY_DEFAULT_TYPE', 11),

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    */
    'callback_url' => env('DIGIPAY_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Token Cache Settings
    |--------------------------------------------------------------------------
    |
    | High-performance scalable caching for Digipay OAuth bearer tokens.
    |
    */
    'cache' => [
        'store' => env('DIGIPAY_CACHE_STORE'), // null uses default cache store
        'prefix' => env('DIGIPAY_CACHE_PREFIX', 'digipay_oauth_token_'),
        'ttl_buffer' => env('DIGIPAY_TOKEN_TTL_BUFFER', 60), // seconds before expiry to refresh
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => env('DIGIPAY_TIMEOUT', 30),
    'connect_timeout' => env('DIGIPAY_CONNECT_TIMEOUT', 10),
    'retry' => [
        'times' => env('DIGIPAY_RETRY_TIMES', 2),
        'sleep_milliseconds' => env('DIGIPAY_RETRY_SLEEP', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Digipay API Version & Agent Headers
    |--------------------------------------------------------------------------
    */
    'agent' => env('DIGIPAY_AGENT', 'WEB'),
    'version' => env('DIGIPAY_API_VERSION', '2022-02-02'),

    /*
    |--------------------------------------------------------------------------
    | Multiple Merchant Connections
    |--------------------------------------------------------------------------
    |
    | Define custom merchant configurations for multi-store or multi-tenant apps.
    |
    */
    'connections' => [
        // 'store_2' => [
        //     'sandbox' => false,
        //     'client_id' => env('DIGIPAY_STORE2_CLIENT_ID'),
        //     'client_secret' => env('DIGIPAY_STORE2_CLIENT_SECRET'),
        //     'username' => env('DIGIPAY_STORE2_USERNAME'),
        //     'password' => env('DIGIPAY_STORE2_PASSWORD'),
        //     'default_type' => 11,
        //     'callback_url' => env('DIGIPAY_STORE2_CALLBACK_URL'),
        // ],
    ],
];
