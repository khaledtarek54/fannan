<?php

namespace App\Filament\Resources\WithdrawTransactionResource\Pages;

use App\Enums\TransactionType;
use App\Filament\Resources\WithdrawTransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawTransactions extends ListRecords
{
    protected static string $resource = WithdrawTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // [DASH-P1] Count in SQL instead of hydrating the whole withdrawals table into memory
        // on every list render (eases DB pressure on the constrained prod host).
        $base = fn () => Transaction::query()->where('type', TransactionType::WITHDRAW->value);

        return [
            'Pending' => Tab::make("pending")
                ->modifyQueryUsing(fn($query) => $query->where('is_completed', 0))
                ->badge($base()->where('is_completed', 0)->count())
                ->badgeColor('success'),
            'Completed' => Tab::make('completed')
                ->modifyQueryUsing(fn($query) => $query->where('is_completed', 1))
                ->badge($base()->where('is_completed', 1)->count())
                ->badgeColor('primary'),
        ];
    }
}
