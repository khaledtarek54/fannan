<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Display currency
    |--------------------------------------------------------------------------
    |
    | The currency label shown for money across the admin panel and on invoices.
    | Fannan serves both Egypt (EasyKash / EGP) and Saudi Arabia (HyperPay / SAR);
    | this controls the *display* label only — it does NOT change the currency
    | codes sent to the payment gateways (those stay gateway-correct).
    |
    | Switch it per deployment with APP_CURRENCY in .env, e.g.:
    |   APP_CURRENCY=EGP   (Egypt)
    |   APP_CURRENCY=SAR   (Saudi Arabia)
    |
    */

    'currency' => env('APP_CURRENCY', 'EGP'),

];
