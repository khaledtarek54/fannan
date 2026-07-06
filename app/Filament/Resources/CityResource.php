<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Cities CRUD. Cities drive addresses, orders and artist coverage but were previously only
 * editable in the database. `name` is translatable (Spatie), edited via the en/ar widget.
 */
class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('app.configurations');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.cities');
    }

    public static function getModelLabel(): string
    {
        return __('app.city');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.cities');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(trans('app.name'))
                        ->translatable(true, ['ar' => trans('app.name_ar'), 'en' => trans('app.name_en')], [
                            'en' => ['required'],
                            'ar' => ['required'],
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(trans('app.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
