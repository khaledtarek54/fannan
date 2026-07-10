<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistGalleryResource\Pages;
use App\Models\ArtistGallery;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;

class ArtistGalleryResource extends Resource
{
    protected static ?string $model = ArtistGallery::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('app.users');
    }

    // Gallery items are uploaded by artists from the mobile app (ArtistGallery::setVideoAttribute
    // expects an UploadedFile, which a Filament FileUpload does not provide). Admin only moderates
    // them here, so creation is disabled and the panel is list + delete only.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        // Unused (create/edit disabled) but required by the Resource contract.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(trans('app.artist'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(trans('app.type')),
                ImageColumn::make('video_url')
                    ->label(trans('app.file'))
                    ->checkFileExistence(false)
                    ->url(fn($record) => url($record->video))
                    ->openUrlInNewTab()
                    ->square(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(trans('app.artist'))
                    ->searchable()
                    ->options(fn () => User::query()->artist()->pluck('name', 'id')->toArray())
            ])
            ->actions([
//                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListArtistGalleries::route('/'),
            // create/edit intentionally omitted — moderation only (see canCreate()).
        ];
    }
}
