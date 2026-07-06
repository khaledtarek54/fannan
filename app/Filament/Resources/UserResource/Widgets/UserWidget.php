<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Count in SQL instead of hydrating every completed-profile user into memory just to count them.
        return [
            Stat::make(
                __('app.active_clients'),
                User::query()->where('role', 'client')->where('completed_profile', 1)->count()
            )->icon('heroicon-o-identification'),
            Stat::make(
                __('app.active_artists'),
                User::query()->where('role', 'artist')->where('completed_profile', 1)->count()
            )->icon('entypo-users'),
        ];
    }
}
