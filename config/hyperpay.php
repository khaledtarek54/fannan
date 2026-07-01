<?php


if (config('app.env') == "local")
    return [
        'base_url' => env('HYPERPAY_TEST_URL'),

        'entity_id' => env('HYPERPAY_TEST_ENTITY_ID'),

        'mada_entity_id' => env('HYPERPAY_TEST_MADA_ENTITY_ID'),

        'apple_pay_entity_id' => env('HYPERPAY_TEST_APPLE_PAY_ENTITY_ID'),

        'access_token' => env('HYPERPAY_TEST_ACCESS_TOKEN'),
    ];

return [
    'base_url' => env('HYPERPAY_LIVE_URL'),

    'entity_id' => env('HYPERPAY_LIVE_ENTITY_ID'),

    'mada_entity_id' => env('HYPERPAY_LIVE_MADA_ENTITY_ID'),

    'apple_pay_entity_id' => env('HYPERPAY_APPLE_PAY_ENTITY_ID'),

    'access_token' => env('HYPERPAY_LIVE_ACCESS_TOKEN'),

];
