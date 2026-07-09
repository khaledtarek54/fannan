<?php

namespace App\Filament\Resources\AdminActivityLogResource\Pages;

use App\Filament\Resources\AdminActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminActivityLogs extends ListRecords
{
    protected static string $resource = AdminActivityLogResource::class;

    // Read-only log: creation is already blocked by AdminActivityLogResource::canCreate() and the
    // index-only getPages(), so no header actions are needed.
}
