<?php

namespace App\Filament\Resources\DirectOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans('app.order_offers');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_id')
            ->columns([
                Tables\Columns\TextColumn::make('artist.name')
                ->label(trans('app.artist')),
                Tables\Columns\TextColumn::make('counter_to')
                    ->label(trans('app.counter_to')),
                Tables\Columns\TextColumn::make('cost')
                    ->label(trans('app.cost')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
