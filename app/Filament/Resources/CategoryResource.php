<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers\UserCategoriesRelationManager;
use App\Models\Category;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;


class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('app.configurations');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->columns(2)->schema([
                    TextInput::make('name')
                        ->label(trans('app.name'))
                        ->translatable(true, ['ar' => trans('app.name_ar'), 'en' => trans('app.name_en'),], [
                            'en' => ['required'],
                            'ar' => ['required'],
                        ]),
                    FileUpload::make('photo')
                        ->label(trans('app.photo'))
                        ->required()
                        ->directory("categories"),
                    Repeater::make('subCategory')
                        ->label(trans('app.subcategory'))
                        ->relationship()
                        ->schema([
                            TextInput::make('name')
                                ->label(trans('app.name'))
                                ->translatable(true, ['ar' => trans('app.name_ar'), 'en' => trans('app.name_en'),], [
                                    'en' => ['required'],
                                    'ar' => ['required'],
                                ]),
                        ])
                        ->defaultItems(1)
                        // [DASH-P1] Removing a repeater row hard-deletes the SubCategory (no SoftDeletes),
                        // and user_categories.subcategory_id is onDelete('set null') — so deleting one here
                        // silently wipes the specialization of every artist tied to it. Disallow deletion
                        // from this form; subcategories can still be added/renamed. Deletion needs a
                        // dedicated, usage-checked action (a later phase).
                        ->deletable(false)
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(trans('app.name'))
                    ->searchable(),
                ImageColumn::make('photo')->circular(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(trans('app.relations'), [
                UserCategoriesRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
