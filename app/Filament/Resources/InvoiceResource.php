<?php

namespace App\Filament\Resources;

use App\Enums\OrderType;
use App\Filament\Actions\DownloadInvoiceAction;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Order;
use App\Models\User;
use App\Services\InvoiceService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Finance lens over orders: every order with its amount, payment status and a one-click invoice
 * PDF. Read-only — invoices are generated from orders (there is no invoices table). Also gives
 * admins the unified direct+bidding view and the paid/unpaid overview they previously lacked.
 */
class InvoiceResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.transactions');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.invoices');
    }

    public static function getModelLabel(): string
    {
        return __('app.invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.invoices');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // invoice_number is derived from the id, so sorting by id sorts by number too.
                Tables\Columns\TextColumn::make('id')
                    ->label(trans('app.invoice_number'))
                    ->formatStateUsing(fn (Order $record) => app(InvoiceService::class)->invoiceNumber($record))
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label(trans('app.number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label(trans('app.client'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('artist.name')
                    ->label(trans('app.artist'))
                    ->searchable(),
                // total_cost is a computed accessor — display only (no sort/search at SQL level).
                Tables\Columns\TextColumn::make('total_cost')
                    ->label(trans('app.total'))
                    ->formatStateUsing(fn (Order $record) => money($record->total_cost)),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label(trans('app.payment_status'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.issued_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_paid')
                    ->label(trans('app.payment_status'))
                    ->trueLabel(trans('app.paid'))
                    ->falseLabel(trans('app.unpaid')),
                SelectFilter::make('type')
                    ->label(trans('app.type'))
                    ->options([
                        OrderType::DIRECT->value => trans('app.direct_orders'),
                        OrderType::BIDDING->value => trans('app.bidding_orders'),
                    ]),
                SelectFilter::make('client_id')
                    ->label(trans('app.client'))
                    ->searchable()
                    ->options(fn () => User::client()->pluck('name', 'id')),
                SelectFilter::make('artist_id')
                    ->label(trans('app.artist'))
                    ->searchable()
                    ->options(fn () => User::artist()->pluck('name', 'id')),
            ])
            ->actions([
                DownloadInvoiceAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return Order::query()->with(['client', 'artist']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
