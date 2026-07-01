<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Iban implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $iban = strtoupper(str_replace(' ', '', $value));

        $countryCode = substr($iban, 0, 2);
        $ibanLengths = [
            'AL' => 28, 'AD' => 24, 'AT' => 20, 'AZ' => 28, 'BH' => 22, 'BY' => 28, 'BE' => 16, 'BA' => 20, 'BR' => 29,
            'BG' => 22, 'CR' => 22, 'HR' => 21, 'CY' => 28, 'CZ' => 24, 'DK' => 18, 'DO' => 28, 'EG' => 29, 'EE' => 20,
            'FO' => 18, 'FI' => 18, 'FR' => 27, 'GE' => 22, 'DE' => 22, 'GI' => 23, 'GR' => 27, 'GL' => 18, 'GT' => 28,
            'HU' => 28, 'IQ' => 23, 'JO' => 30, 'KW' => 30, 'LV' => 21, 'LB' => 28, 'QA' => 29, 'SM' => 27, 'SA' => 24,
            'ES' => 24, 'TN' => 24, 'TR' => 26, 'AE' => 23, 'GB' => 22, 'VG' => 24, 'ML' => 28, 'MA' => 28, 'MZ' => 25,
            'NI' => 32, 'NE' => 28, 'SN' => 28, 'TG' => 28,
        ];

        if (!isset($ibanLengths[$countryCode]) || strlen($iban) !== $ibanLengths[$countryCode]) {
            return false;
        }

        $iban = substr($iban, 4) . substr($iban, 0, 4);

        $iban = preg_replace_callback('/[A-Z]/', function ($match) {
            return ord($match[0]) - 55;
        }, $iban);

        return bcmod($iban, 97) == 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute field contains an invalid IBAN.';
    }
}
