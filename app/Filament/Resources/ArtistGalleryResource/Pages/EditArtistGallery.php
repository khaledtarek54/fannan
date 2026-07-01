<?php

namespace App\Filament\Resources\ArtistGalleryResource\Pages;

use App\Filament\Resources\ArtistGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArtistGallery extends EditRecord
{
    protected static string $resource = ArtistGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\DeleteAction::make(),
        ];
    }
}
