<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    public function getTabs(): array
    {
        $users = User::withTrashed()->client()->get();

        return [
            'Active' => Tab::make(__('app.active_clients'))
                ->modifyQueryUsing(fn($query) => $query->whereNull('deleted_at'))
                ->badge($users->whereNull('deleted_at')->count())
                ->badgeColor('primary'),
            'Deactivate' => Tab::make(__('app.deactivate_clients'))
                ->modifyQueryUsing(fn($query) => $query->withTrashed()->whereNotNull('deleted_at'))
                ->badge($users->whereNotNull('deleted_at')->count())
                ->badgeColor('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
