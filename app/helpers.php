<?php

if (! function_exists('currency_code')) {
    /**
     * The app-wide display currency label (see config/fannan.php). Used by the admin panel
     * and invoices so Egypt (EGP) / Saudi (SAR) is a single switch instead of scattered
     * hard-coded "SAR"/"EGP" literals.
     */
    function currency_code(): string
    {
        return config('fannan.currency', 'EGP');
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount with the display currency, e.g. "1,500 EGP".
     * Whole numbers show no decimals; fractional amounts show two.
     */
    function money($amount): string
    {
        $amount = (float) $amount;
        $formatted = fmod($amount, 1.0) === 0.0
            ? number_format($amount, 0)
            : number_format($amount, 2);

        return $formatted . ' ' . currency_code();
    }
}
