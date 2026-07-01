<?php

namespace App\Filament\Resources\BiddingOrderResource\Pages;

use App\Filament\Resources\BiddingOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBiddingOrders extends ListRecords
{
    protected static string $resource = BiddingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }
}
