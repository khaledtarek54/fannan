<?php

namespace App\Filament\Resources\DirectOrderResource\Pages;

use App\Filament\Resources\DirectOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDirectOrders extends ListRecords
{
    protected static string $resource = DirectOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }
}
