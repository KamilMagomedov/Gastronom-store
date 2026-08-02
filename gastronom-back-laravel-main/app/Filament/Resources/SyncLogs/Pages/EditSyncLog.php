<?php

namespace App\Filament\Resources\SyncLogs\Pages;

use App\Filament\Resources\SyncLogs\SyncLogResource;
use Filament\Resources\Pages\EditRecord;

class EditSyncLog extends EditRecord
{
    protected static string $resource = SyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
