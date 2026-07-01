<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public static function getBaseUrlForFiles(): string
    {
        return config('app.url') . "/storage/";
    }

    public static function createVerificationCode(): int
    {
        if (config('app.env') == "local")
            return 1234;
        else
            return rand(0000, 9999);
    }

    public function cities(): array
    {
        $data['cities'] = City::all()->pluck('name', 'id')->toArray();
        return $data;
    }

    public function updateLang(): JsonResponse
    {
        $user = auth()->user();
        $user->lang = request()->header('lang');
        $user->save();
        return response()->json([
            'status' => true,
            'message' => trans('app.done')
        ]);
    }

    public static function convertArabicDate($arabicDate)
    {
        $westernDate = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $arabicDate
        );
        return $westernDate;
    }

    public static function separateCountryPrefix($phoneNumber)
    {
        // Regular expression to match the country code
        if (preg_match('/^(\+\d{1,3})(\d{7,})$/', $phoneNumber, $matches)) {
            $countryCode = $matches[1];  // Country code
            $number = $matches[2];        // Phone number without country code
            return [
                'country_code' => $countryCode,
                'number' => $number
            ];
        }
        return null; // Return null if the format is incorrect
    }
}
