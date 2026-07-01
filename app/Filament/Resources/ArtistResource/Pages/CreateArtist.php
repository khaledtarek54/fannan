<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\ArtistResource;
use App\Http\Controllers\Controller;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateArtist extends CreateRecord
{
    protected static string $resource = ArtistResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function handleRecordCreation(array $data): Model
    {
        $data['completed_profile'] = true;
        $data['role'] = UserRole::ARTIST->value;
        $data['phone'] = str_replace(' ', '', $data['phone']);;
        return static::getModel()::create($data);
    }

}
