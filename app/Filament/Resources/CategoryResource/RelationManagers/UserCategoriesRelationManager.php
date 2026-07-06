<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Models\SubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UserCategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'userCategories';

    public function form(Form $form): Form
    {
        // category_id is set automatically by the relationship (this RM lives under a Category).
        // The editable fields are the user and their subcategory — Selects, not a raw FK text box.
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label(trans('app.artist'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('subcategory_id')
                    ->label(trans('app.subcategory'))
                    ->options(fn () => SubCategory::where('category_id', $this->getOwnerRecord()->id)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('category_id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label(trans('app.artist')),
                Tables\Columns\TextColumn::make('subcategory.name')->label(trans('app.subcategory')),
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
