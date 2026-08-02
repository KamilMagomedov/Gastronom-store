<?php

namespace App\Filament\Resources\SyncLogs\Pages;

use App\Filament\Resources\SyncLogs\SyncLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSyncLog extends CreateRecord
{
    protected static string $resource = SyncLogResource::class;
}
