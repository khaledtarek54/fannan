<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::create([
            'type' => 'artist_acknowledgement',
            'value' => [
                'en' => 'artist acknowledgement',
                'ar' => 'اقرار الفنان',
            ],
        ]);
    }
}
