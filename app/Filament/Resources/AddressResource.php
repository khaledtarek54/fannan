<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddressResource\Pages;
use App\Filament\Resources\AddressResource\RelationManagers;
use App\Models\Address;
use App\Models\City;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('app.users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->columns(2)->schema([
                    Select::make('user_id')
                        ->label(trans('app.client'))
                        ->searchable()
                        ->required()
                        ->options(User::client()->get()->pluck('name', 'id')),
                    Select::make('city_id')
                        ->label(trans('app.city'))
                        ->searchable()
                        ->required()
                        ->options(City::query()->get()->pluck('name', 'id')->toArray()),
                    TextInput::make('name')
                        ->label(trans('app.name'))
                        ->required(),
                    TextInput::make('latitude')
                        ->label(trans('app.latitude'))
                        ->required(),
                    TextInput::make('longitude')
                        ->label(trans('app.longitude'))
                        ->required(),
                    Textarea::make('description')
                        ->label(trans('app.description'))
                        ->required(),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(trans('app.client'))
                    ->searchable(),
                TextColumn::make('city.name')
                    ->label(trans('app.city'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(trans('app.name'))
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(trans('app.client'))
                    ->options(User::client()->get()->pluck('name', 'id'))
                    ->multiple(),
                SelectFilter::make('city_id')
                    ->label(trans('app.city'))
                    ->options(City::all()->pluck('name', 'id'))
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAddresses::route('/'),
            'create' => Pages\CreateAddress::route('/create'),
            'edit' => Pages\EditAddress::route('/{record}/edit'),
        ];
    }
}
