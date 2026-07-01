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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'google_id' => env('GOOGLE_CLIENT_ID'),
        'google_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => 'http://example.com/callback-url',
    ],

    'apple' => [
        'apple_id' => env('APPLE_CLIENT_ID'),
        'apple_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => 'http://example.com/callback-url',
    ],

    'facebook' => [
        'facebook_id' => env('FACEBOOK_CLIENT_ID'),
        'facebook_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => 'http://example.com/callback-url',
    ],
    'easykash' => [
        'api_key' => env('EASYKASH_API_KEY'),
        'secret_key' => env('EASYKASH_HMAC_SECRET'),
        'redirect_url' => env('EASYKASH_REDIRECT_URL'),
    ],

    'map_api_key' => env('GOOGLE_MAP_API_KEY'),

];
