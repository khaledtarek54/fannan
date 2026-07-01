<?php

namespace App\Filament\Resources\DirectOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DatesRelationManager extends RelationManager
{
    protected static string $relationship = 'dates';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans('app.order_dates');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('start_date')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('start_date')
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label(trans('app.start_date')),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(trans('app.end_date')),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(trans('app.start_time')),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(trans('app.end_time')),
                IconColumn::make('is_completed')
                    ->label(trans('app.completed'))
                    ->boolean()
                    ->trueColor('info')
                    ->falseColor('warning'),
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
