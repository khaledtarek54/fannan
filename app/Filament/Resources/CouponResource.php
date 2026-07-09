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
                            ->live() // so the amount suffix/validation reacts to fixed vs percentage
                            ->searchable(),
                        // [DASH-P3] amount was ->required() only, so an admin could set a negative or
                        // >100% discount. Bound it: numeric, >= 0, and <= 100 for percentage coupons.
                        Forms\Components\TextInput::make('amount')
                            ->label(trans('app.amount'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->suffix(fn (Forms\Get $get) => $get('type') === CouponType::PERCENTAGE->value ? '%' : currency_code())
                            ->rule(fn (Forms\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($get('type') === CouponType::PERCENTAGE->value && (float) $value > 100) {
                                    $fail('A percentage coupon cannot exceed 100%.');
                                }
                            }),
                        Forms\Components\TextInput::make('code')
                            ->label(trans('app.coupon_code'))
                            ->required()
                            ->unique(ignoreRecord: true), // [DASH-P3] no more raw 500 on a duplicate code
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label(trans('app.start_date'))
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label(trans('app.end_date'))
                            ->required()
                            ->after('start_date'), // [DASH-P3] end must be after start
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
                    // [DASH-P3] show the amount with its unit (20% vs 20 EGP) so it isn't a bare number.
                    // NB: Coupon casts `type` to the CouponType enum, so compare enum-to-enum here
                    // (the form's $get('type') is a raw string — different path).
                    ->formatStateUsing(fn ($state, Coupon $record) => $record->type?->value === CouponType::PERCENTAGE->value
                        ? $state . '%'
                        : money((float) $state))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(trans('app.coupon_code'))
                    ->sortable()
                    ->searchable(),
                // [DASH-P3] at-a-glance validity + how many times the coupon has been redeemed.
                Tables\Columns\TextColumn::make('status')
                    ->label(trans('app.status'))
                    ->badge()
                    ->getStateUsing(fn (Coupon $record): string => self::validityStatus($record))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'scheduled' => 'info',
                        'expired' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => trans('app.' . $state)),
                Tables\Columns\TextColumn::make('coupon_users_count')
                    ->label(trans('app.uses'))
                    ->sortable(),
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

    public static function getEloquentQuery(): Builder
    {
        // [DASH-P3] load the redemption count for the "Uses" column in one query (no N+1).
        return parent::getEloquentQuery()->withCount('couponUsers');
    }

    /** [DASH-P3] Validity relative to now: scheduled (not started) / expired (ended) / active. */
    public static function validityStatus(Coupon $coupon): string
    {
        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return 'scheduled';
        }
        if ($coupon->end_date && $now->gt($coupon->end_date)) {
            return 'expired';
        }
        return 'active';
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CouponUsersRelationManager::class,
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
