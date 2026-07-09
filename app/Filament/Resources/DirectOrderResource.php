<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Filament\Resources\DirectOrderResource\Pages;
use App\Filament\Resources\DirectOrderResource\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\DirectOrderResource\RelationManagers\DatesRelationManager;
use App\Filament\Resources\DirectOrderResource\RelationManagers\OffersRelationManager;
use App\Filament\Resources\DirectOrderResource\RelationManagers\SupportsRelationManager;
use App\Models\Address;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\SubCategory;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DirectOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.orders');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.direct_orders');
    }

    public static function getModelLabel(): string
    {
        return __('app.direct_order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.direct_orders');
    }

    public static function getNavigationBadge(): ?string
    {
        return Order::query()->where('type', OrderType::DIRECT->value)
            ->whereHas('statuses', fn($query) => $query->where('name', OrderStatus::ARTIST_PENDING->value))
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
                        Select::make('artist_id')
                            ->label(trans('app.artist'))
                            ->searchable()
                            ->required()
                            ->options(User::artist()->pluck('name', 'id')),
                        // category_id is form-only (used to filter subcategories); the order stores the
                        // chosen subcategory as an OrderCategory row in CreateDirectOrder. Create-only:
                        // editing an existing order's categories is done via the Categories relation manager.
                        Select::make('category_id')
                            ->label(trans('app.category'))
                            ->searchable()
                            ->required(fn(string $context) => $context === 'create')
                            ->dehydrated(false)
                            ->options(Category::query()->pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('subcategory_id', null))
                            ->visibleOn(['create']),
                        Select::make('subcategory_id')
                            ->label(trans('app.subcategory'))
                            ->searchable()
                            ->required(fn(string $context) => $context === 'create')
                            ->options(function (callable $get) {
                                $categoryId = $get('category_id');
                                if ($categoryId) {
                                    return SubCategory::where('category_id', $categoryId)->pluck('name', 'id');
                                }
                                return SubCategory::query()->pluck('name', 'id');
                            })
                            ->visibleOn(['create']),
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
                        // Dates are read in CreateDirectOrder to build an OrderDate row (they are not
                        // Order columns, so handleRecordCreation consumes them). Create-only — per-date
                        // editing lives in the Dates relation manager.
                        DateTimePicker::make('start_date')
                            ->label(trans('app.start_date'))
                            ->required()
                            ->visibleOn(['create']),
                        DateTimePicker::make('end_date')
                            ->label(trans('app.end_date'))
                            ->required()
                            ->visibleOn(['create']),
                        TextInput::make('cost')
                            ->label(trans('app.cost'))
                            ->numeric()
                            ->minValue(0)
                            ->required(fn(string $context) => $context === 'create')
                            ->suffix(currency_code()),
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
                Tables\Columns\TextColumn::make('artist.name')
                    ->label(trans('app.artist'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address.city.name')
                    ->label(trans('app.city'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label(trans('app.cost'))
                    ->searchable()
                    ->suffix(' ' . currency_code())
                    ->sortable(),

                // `status_value` is a computed accessor (Order::getStatusValueAttribute), NOT a DB
                // column, so ->searchable()/->sortable() emit `WHERE/ORDER BY status_value` and throw
                // "Unknown column 'status_value'". It can be displayed but not sorted/searched at SQL level.
                BadgeColumn::make('status_value')
                    ->label(trans('app.status'))
                    ->colors([
                        'danger' => OrderStatus::REJECTED->value,
                        'warning' => OrderStatus::ARTIST_PENDING->value,
                        'success' => OrderStatus::ACCEPTED->value,
                        'secondary' => OrderStatus::COMPLETED->value,
                        'info' => OrderStatus::IN_PAYMENT->value,
                    ])
                    ->formatStateUsing(function (string $state) {
                        return match ($state) {
                            OrderStatus::REJECTED->value => 'Rejected',
                            OrderStatus::ARTIST_PENDING->value => 'Artist Pending',
                            OrderStatus::ACCEPTED->value => 'Accepted',
                            OrderStatus::COMPLETED->value => 'Completed',
                            OrderStatus::IN_PAYMENT->value => 'In Payment',
                            default => ucfirst($state),
                        };
                    })
            ])
            ->filters([
                // [DASH-P3] Filter by the order's CURRENT status — the single most useful order filter,
                // previously missing. Uses spatie's currentStatus scope (matches the status_value column).
                SelectFilter::make('status')
                    ->label(trans('app.status'))
                    ->options(collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))])
                        ->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->currentStatus($data['value'])
                        : $query),
                // orders has no city_id column — the city lives on the related address. Filter through
                // the relationship (matches Order::scopeCity) instead of a nonexistent orders.city_id.
                // [DASH-P3] lean pluck (SELECT name,id) instead of ::all()->pluck / ->get()->pluck,
                // which hydrated whole tables on every list render.
                SelectFilter::make('city_id')
                    ->label(trans('app.city'))
                    ->searchable()
                    ->options(City::pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereHas('address', fn (Builder $q) => $q->where('city_id', $data['value']))
                        : $query),
                SelectFilter::make('client_id')
                    ->label(trans('app.client'))
                    ->searchable()
                    ->options(User::client()->pluck('name', 'id')),
                SelectFilter::make('artist_id')
                    ->label(trans('app.artist'))
                    ->searchable()
                    ->options(User::artist()->pluck('name', 'id')),
            ])
            ->actions([

                Tables\Actions\ViewAction::make(),
                \App\Filament\Actions\DownloadInvoiceAction::make(),
//                Tables\Actions\Action::make('complete_payment')
//                    ->label(trans('app.payment_complete'))
//                    ->requiresConfirmation()
//                    ->action(function (Order $order) {
//                        $order->update(['is_paid' => 1]);
//                    })
//                    ->visible(fn(Order $order) => !$order->is_paid),
//                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('Relations', [
                OffersRelationManager::class,
                CategoriesRelationManager::class,
                DatesRelationManager::class,
                SupportsRelationManager::class
            ]),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Order::query()->where('type', OrderType::DIRECT->value)
            ->with(['client', 'artist', 'address.city']) // avoid N+1 on the list's name/city columns
            ->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDirectOrders::route('/'),
            'create' => Pages\CreateDirectOrder::route('/create'),
            'view' => Pages\ViewDirectOrder::route('/{record}'),
            'edit' => Pages\EditDirectOrder::route('/{record}/edit'),
        ];
    }
}
