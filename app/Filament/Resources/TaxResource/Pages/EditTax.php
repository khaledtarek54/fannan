<?php

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditTax extends EditRecord
{
    protected static string $resource = TaxResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['value'] = [
            'en' => $data['value_ar'],
            'ar' => $data['value_ar'],
        ];
        unset($data['value_ar']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
