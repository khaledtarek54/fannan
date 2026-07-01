<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use App\Filament\Resources\ArtistResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListArtists extends ListRecords
{
    protected static string $resource = ArtistResource::class;

    public function getTabs(): array
    {
        $users = User::withTrashed()->artist()->get();

        return [
            'Active' => Tab::make(__('app.active_artists'))
                ->modifyQueryUsing(fn($query) => $query->where('completed_profile', true)->whereNull('deleted_at'))
                ->badge($users->whereNull('deleted_at')->where('completed_profile', true)->count())
                ->badgeColor('primary'),
            'Deactivate' => Tab::make(__('app.deactivate_artists'))
                ->modifyQueryUsing(fn($query) => $query->withTrashed()->where('completed_profile', true)->whereNotNull('deleted_at'))
                ->badge($users->where('completed_profile', true)->whereNotNull('deleted_at')->count())
                ->badgeColor('success'),
            'Non Completed Profile' => Tab::make(__('app.non_complete_profile'))
                ->modifyQueryUsing(fn($query) => $query->where('completed_profile', 0))
                ->badge($users->where('completed_profile', false)->count())
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
