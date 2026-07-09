<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Filament\Filters\CreatedBetweenFilter;
use App\Filament\Resources\BiddingOrderResource\Pages;
use App\Filament\Resources\BiddingOrderResource\RelationManagers\BiddingOrderArtistsRelationManager;
use App\Filament\Resources\DirectOrderResource\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\DirectOrderResource\RelationManagers\DatesRelationManager;
use App\Filament\Resources\DirectOrderResource\RelationManagers\SupportsRelationManager;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BiddingOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.orders');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.bidding_orders');
    }

    public static function getModelLabel(): string
    {
        return __('app.bidding_order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.bidding_orders');
    }

    public static function getNavigationBadge(): ?string
    {
        return Order::query()->where('type', OrderType::BIDDING->value)
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns()
                    ->schema([
                        TextInput::make('number')
                            ->label(trans('app.number'))
                            ->visibleOn(['view']),
                        Select::make('client_id')
                            ->label(trans('app.client'))
                            ->searchable()
                            ->required()
                            ->options(User::client()->pluck('name', 'id')),
                        Select::make('address_id')
                            ->label(trans('app.address'))
                            ->searchable()
                            ->required()
                            ->options(function (callable $get) {
                                $userId = $get('client_id');
                                if ($userId) {
                                    return Address::where('user_id', $userId)->get()->pluck('name', 'id');
                                }
                                return Address::all()->pluck('name', 'id');
                            }),
                        Textarea::make('description')
                            ->label(trans('app.description'))
                            ->required(),
                        DateTimePicker::make('start_date')
                            ->label(trans('app.start_date'))
                            ->required()
                            ->hiddenOn(['view']),
                        DateTimePicker::make('end_date')
                            ->label(trans('app.end_date'))
                            ->required()
                            ->hiddenOn(['view']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(trans('app.number'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label(trans('app.client'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address.city.name')
                    ->label(trans('app.city'))
                    ->searchable()
                    ->sortable(),
                // `subcategories_text` is a computed accessor (Order::getSubcategoriesTextAttribute),
                // not a DB column — ->searchable()/->sortable() would throw "Unknown column". Display only.
                Tables\Columns\TextColumn::make('subcategories_text')
                    ->label(trans('app.categories')),
                // [DASH-P3] status_value is a computed accessor (current status name) — display only.
                Tables\Columns\TextColumn::make('status_value')
                    ->label(trans('app.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? ucwords(str_replace('_', ' ', $state)) : '—'),
            ])
            ->filters([
                // [DASH-P3] give the bidding list the filters it lacked: current status, client, date.
                Tables\Filters\SelectFilter::make('status')
                    ->label(trans('app.status'))
                    ->options(collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))])
                        ->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->currentStatus($data['value'])
                        : $query),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label(trans('app.client'))
                    // scope the dropdown to clients (matches DirectOrderResource) rather than listing every user
                    ->options(fn () => User::client()->pluck('name', 'id'))
                    ->searchable(),
                CreatedBetweenFilter::make(),
            ])
            ->actions([
//                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()->icon(null),
                \App\Filament\Actions\DownloadInvoiceAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(trans('app.relations'), [
                BiddingOrderArtistsRelationManager::class,
                CategoriesRelationManager::class,
                DatesRelationManager::class,
                SupportsRelationManager::class
            ]),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Order::query()->where('type', OrderType::BIDDING)
            ->orderByDesc('id')->with(['biddingOrderArtists.artist', 'client', 'address.city']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBiddingOrders::route('/'),
            // [DASH-P1] No 'create' route: a hand-built bidding order came out corrupt (Order::create
            // defaulted type='direct', so it vanished from this list and polluted direct orders, with
            // no number/status/date rows). Bidding orders are created by clients via the app.
            'view' => Pages\ViewBiddingOrder::route('/{record}'),
            'edit' => Pages\EditBiddingOrder::route('/{record}/edit'),
        ];
    }
}
