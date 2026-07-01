<?php

namespace App\Filament\Resources\CompletedSupportResource\Pages;

use App\Filament\Resources\CompletedSupportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompletedSupport extends EditRecord
{
    protected static string $resource = CompletedSupportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
