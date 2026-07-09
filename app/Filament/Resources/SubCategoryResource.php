<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubCategoryResource\Pages;
use App\Filament\Resources\SubCategoryResource\RelationManagers;
use App\Models\SubCategory;
use App\Models\UserCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SubCategoryResource extends Resource
{
    protected static ?string $model = SubCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        // Subcategories are normally managed via the Category resource's repeater, but this resource
        // is still reachable by URL, so give it a working form instead of a blank one. `name` is
        // translatable (Spatie), edited with the same en/ar widget CategoryResource uses.
        return $form
            ->schema([
                Forms\Components\Section::make()->columns(2)->schema([
                    Forms\Components\Select::make('category_id')
                        ->label(trans('app.category'))
                        ->relationship('category', 'name')
                        ->searchable()
                        ->required(),
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
                TextColumn::make('category.name')->label("Category Name"),
                TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // [DASH-P1 review] SubCategory has no soft-deletes and user_categories.subcategory_id is
                // onDelete('set null'), so deleting an in-use subcategory silently wipes the
                // specialization of every artist tied to it. The Category repeater already blocks
                // deletion (deletable(false)); this closes the other door — block the delete when the
                // subcategory is still referenced by any artist, instead of only warning.
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('This permanently deletes the subcategory. This cannot be undone.')
                    ->before(function (Tables\Actions\DeleteAction $action, SubCategory $record) {
                        if (static::usageCount([$record->id]) > 0) {
                            Notification::make()
                                ->title('This subcategory is in use by one or more artists and cannot be deleted. Reassign those artists first.')
                                ->danger()->send();
                            $action->halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalDescription('This permanently deletes the selected subcategories. This cannot be undone.')
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            if (static::usageCount($records->pluck('id')->all()) > 0) {
                                Notification::make()
                                    ->title('One or more selected subcategories are in use by artists and cannot be deleted. Deselect or reassign them first.')
                                    ->danger()->send();
                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    /** [DASH-P1 review] How many artist specializations point at the given subcategory ids. */
    protected static function usageCount(array $subcategoryIds): int
    {
        return UserCategory::whereIn('subcategory_id', $subcategoryIds)->count();
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
            'index' => Pages\ListSubCategories::route('/'),
            'create' => Pages\CreateSubCategory::route('/create'),
            'edit' => Pages\EditSubCategory::route('/{record}/edit'),
        ];
    }
}
