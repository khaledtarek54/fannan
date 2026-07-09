<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Http\Controllers\Controller;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function handleRecordCreation(array $data): Model
    {
        unset($data['country_code']);
        $data['phone'] = str_replace(' ', '', $data['phone']);

        $user = static::getModel()::create($data);

        // [DASH-P1] The panel gate is User::canAccessPanel() => is_admin, and is_admin is
        // intentionally NOT mass-assignable (R2-C4). Without setting it here, every "admin"
        // created through this screen had is_admin=false and could never log in. Set it (and
        // keep role in sync) explicitly via forceFill so the account actually works.
        $user->forceFill(['is_admin' => true, 'role' => 'admin'])->save();

        return $user;
    }
}
