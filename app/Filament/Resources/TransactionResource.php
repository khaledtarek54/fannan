<?php

namespace App\Filament\Resources;

use App\Enums\TransactionType;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Tables;
use App\Filament\Actions\ExportCsvAction;
use App\Filament\Filters\CreatedBetweenFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Full wallet ledger (income + withdrawals) — read-only. WithdrawTransactionResource stays the
 * place to create/complete withdrawal requests; this is the complete money trail per user.
 */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.transactions');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.transactions_ledger');
    }

    public static function getModelLabel(): string
    {
        return __('app.transactions_ledger');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.transactions_ledger');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(trans('app.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(trans('app.type'))
                    ->badge()
                    ->color(fn ($state) => $state === TransactionType::INCOME->value ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => trans('app.' . $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(trans('app.amount'))
                    ->formatStateUsing(fn ($state) => money($state))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label(trans('app.completed'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(trans('app.type'))
                    ->options([
                        TransactionType::INCOME->value => trans('app.income'),
                        TransactionType::WITHDRAW->value => trans('app.withdraw'),
                    ]),
                TernaryFilter::make('is_completed')
                    ->label(trans('app.completed')),
                // [DASH-P3] filter the ledger by user + date range (queried on demand, not preloaded).
                SelectFilter::make('user_id')
                    ->label(trans('app.name'))
                    ->relationship('user', 'name')
                    ->searchable(),
                CreatedBetweenFilter::make(),
            ])
            ->headerActions([
                // [DASH-P3] export the currently filtered ledger to CSV.
                ExportCsvAction::make([
                    trans('app.name') => fn ($r) => $r->user?->name,
                    trans('app.type') => fn ($r) => $r->type,
                    trans('app.amount') => fn ($r) => $r->amount,
                    trans('app.completed') => fn ($r) => $r->is_completed ? 1 : 0,
                    trans('app.created_at') => fn ($r) => (string) $r->created_at,
                ], 'transactions'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return Transaction::query()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }
}
