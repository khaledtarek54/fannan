<?php

namespace App\Filament\Resources;

use App\Enums\SettingKey;
use App\Filament\Resources\TaxResource\Pages;
use App\Models\Setting;
use App\Models\Tax;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class TaxResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.configurations');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.tax');
    }

    public static function getModelLabel(): string
    {
        return __('app.tax');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.tax');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('type_string')
                            ->label(trans('app.name'))
                            ->default(trans('app.name'))
                            ->disabled(),
                        TextInput::make('value_ar')
                            ->label(trans('app.value'))
                            ->default(function ($record) {
                                return $record ? $record->value_ar : '';
                            })
                            ->required()
                            // [DASH-P1] For percentage RATE settings (tax/vat/platform_fees) enforce a
                            // number 0–100. A non-numeric string used to (float)-cast to 0 and silently
                            // zero the charge applied to live orders. call_center stays free text.
                            ->rule(function ($record) {
                                return function (string $attribute, $value, \Closure $fail) use ($record) {
                                    if ($record && in_array($record->type, self::RATE_TYPES, true)
                                        && (! is_numeric($value) || $value < 0 || $value > 100)) {
                                        $fail('This rate must be a number between 0 and 100.');
                                    }
                                };
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('type_string')
                    ->label(trans('app.type')),
                Tables\Columns\TextColumn::make('value')
                    ->label(trans('app.value')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /** [DASH-P1] Setting types shown here that are numeric percentage RATES (call_center is a phone, not a rate). */
    protected const RATE_TYPES = [
        SettingKey::TAX->value,
        SettingKey::VAT->value,
        SettingKey::PLATFORM_FEES->value,
    ];

    public static function getEloquentQuery(): Builder
    {
        // [DASH-P1] Include SettingKey::TAX — OrderPricingService charges it on every order, but it
        // was missing from this whitelist, so admins had no way to change the real tax rate.
        return Setting::whereIn("type", [
            SettingKey::TAX->value,
            SettingKey::VAT->value,
            SettingKey::PLATFORM_FEES->value,
            SettingKey::CALL_CENTER->value,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
