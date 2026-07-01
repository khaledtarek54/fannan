<?php

namespace App\Filament\Resources\BiddingOrderResource\Pages;

use App\Filament\Resources\BiddingOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBiddingOrder extends EditRecord
{
    protected static string $resource = BiddingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
