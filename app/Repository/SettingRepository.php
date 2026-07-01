<?php

namespace App\Repository;

use App\Enums\SettingKey;
use App\Models\Setting;

class SettingRepository
{

    public function getAll()
    {
        $settingsList = [SettingKey::CALL_CENTER->value];
        return Setting::whereIn('type', $settingsList)->first();
    }

    public function getWhatsappNumber(): string
    {
        $whatsapp = Setting::where('type', SettingKey::CALL_CENTER->value)->first()?->value;
        return substr($whatsapp, 2);
    }

    public function getSettingByKey(string $key)
    {
        return Setting::where('type', $key)->first()?->value;
    }
}
