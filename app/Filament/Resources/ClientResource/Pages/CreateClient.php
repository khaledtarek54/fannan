<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\ClientResource;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function handleRecordCreation(array $data): Model
    {
        unset($data['country_code']);
        $data['completed_profile'] = true;
        $data['is_verified'] = true;
        $data['role'] = UserRole::CLIENT->value;
        $data['phone'] = str_replace(' ', '', $data['phone']);;
        return static::getModel()::create($data);
    }
}
