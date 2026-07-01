<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use App\Filament\Resources\ArtistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditArtist extends EditRecord
{
    protected static string $resource = ArtistResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
//            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['phone'] = str_replace(' ', '', $data['phone']);
        $record->update($data);
        return $record;
    }
}
