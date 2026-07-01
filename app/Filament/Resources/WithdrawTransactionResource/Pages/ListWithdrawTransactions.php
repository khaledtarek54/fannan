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
        $transactions = Transaction::query()
            ->where('type', TransactionType::WITHDRAW->value)->get();
        return [
            'Pending' => Tab::make("pending")
                ->modifyQueryUsing(fn($query) => $query->where('is_completed', 0))
                ->badge($transactions->where('is_completed', 0)->count())
                ->badgeColor('success'),
            'Completed' => Tab::make('completed')
                ->modifyQueryUsing(fn($query) => $query->where('is_completed', 1))
                ->badge($transactions->where('is_completed', 1)->count())
                ->badgeColor('primary'),
        ];
    }
}
