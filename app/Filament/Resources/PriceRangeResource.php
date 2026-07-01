<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceRangeResource\Pages;
use App\Models\PriceRange;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceRangeResource extends Resource
{
    protected static ?string $model = PriceRange::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.configurations');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.price_ranges');
    }

    public static function getModelLabel(): string
    {
        return __('app.price_range');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.price_ranges');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('from')
                            ->label(trans('app.from_range'))
                            ->required(),
                        TextInput::make('to')
                            ->label(trans('app.to_range'))
                            ->required(),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from')
                    ->label(trans('app.from_range')),
                TextColumn::make('to')
                    ->label(trans('app.to_range')),
            ])
            ->filters([
                //
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
            'index' => Pages\ListPriceRanges::route('/'),
            'create' => Pages\CreatePriceRange::route('/create'),
            'edit' => Pages\EditPriceRange::route('/{record}/edit'),
        ];
    }
}
