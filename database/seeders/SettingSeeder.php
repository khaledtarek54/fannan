<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'type' => 'privacy_policy',
                'value' => [
                    'en' => 'This is the English version of the Privacy Policy.',
                    'ar' => 'سياسة الخصوصية',
                ],
            ],
            [
                'type' => 'terms_and_conditions',
                'value' => [
                    'en' => 'This is the English version of the Terms and Conditions.',
                    'ar' => 'شروط الاستخدام',
                ],
            ],
            [
                'type' => 'help_and_support',
                'value' => [
                    'en' => 'This is the English version of the Terms and Conditions.',
                    'ar' => 'شروط الاستخدام',
                ],
            ],
            [
                'type' => 'platform_fees',
                'value' => [
                    'en' => 15,
                    'ar' => 15,
                ],
            ],
            [
                'type' => 'vat',
                'value' => [
                    'en' => 15,
                    'ar' => 15,
                ],
            ],
            [
                'type' => 'about_us',
                'value' => [
                    'en' => 'This is the English version of the About Us.',
                    'ar' => 'من نحن',
                ],
            ],
            [
                'type' => 'call_center_call',
                'value' => [
                    'en' => '009639365421',
                    'ar' => '009639365421',
                ],
            ],
            [
                'type' => 'tax',
                'value' => [
                    'en' => 10,
                    'ar' => 10,
                ],
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
