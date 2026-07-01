<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAd extends CreateRecord
{
    protected static string $resource = AdResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function handleRecordCreation(array $data): Model
    {
        $model = static::getModel()::create($data);
        $model->setStatus('active');
        return $model;
    }
}
