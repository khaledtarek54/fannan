<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $users = User::query()->where('completed_profile', 1)->get();
        return [
            Stat::make(__('app.active_clients'), $users->where('role', 'client')->count()),
            Stat::make(__('app.active_artists'), $users->where('role', 'artist')->count()),
        ];
    }
}
