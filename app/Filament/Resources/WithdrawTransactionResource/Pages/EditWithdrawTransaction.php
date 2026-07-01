<?php

namespace App\Filament\Resources\WithdrawTransactionResource\Pages;

use App\Filament\Resources\WithdrawTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWithdrawTransaction extends EditRecord
{
    protected static string $resource = WithdrawTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
