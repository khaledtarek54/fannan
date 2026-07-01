<?php

namespace App\Filament\Resources;

use App\Enums\CouponType;
use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function getNavigationGroup(): ?string
    {
        return __('app.promotions');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.coupons');
    }

    public static function getModelLabel(): string
    {
        return __('app.coupon');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.coupons');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(CouponType::class)
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('amount')
                            ->label(trans('app.amount'))
                            ->required(),
                        Forms\Components\TextInput::make('code')
                            ->label(trans('app.coupon_code'))
                            ->required(),
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label(trans('app.start_date'))
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label(trans('app.end_date'))
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label(trans('app.type'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(trans('app.amount'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(trans('app.coupon_code'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->label(trans('app.start_date'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->label(trans('app.end_date'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(trans('app.type'))
                    ->searchable()
                    ->options(CouponType::class),
                Filter::make('validity')
                    ->label(trans('app.validity'))
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label(trans('app.start_date')),
                        Forms\Components\DatePicker::make('end_date')
                            ->label(trans('app.end_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                isset($data['start_date']),
                                fn (Builder $query) => $query->whereDate('start_date', '>=', $data['start_date']),
                            )
                            ->when(
                                isset($data['end_date']),
                                fn (Builder $query) => $query->whereDate('end_date', '<=', $data['end_date']),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
