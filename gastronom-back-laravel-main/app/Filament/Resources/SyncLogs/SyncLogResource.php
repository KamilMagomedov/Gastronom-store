<?php

namespace App\Filament\Resources\SyncLogs;

use App\Filament\Resources\SyncLogs\Pages\ListSyncLogs;
use App\Filament\Resources\SyncLogs\Pages\ViewSyncLog;
use App\Filament\Resources\SyncLogs\Tables\SyncLogsTable;
use App\Models\SyncLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Синхронизация';

    protected static ?string $modelLabel = 'Лог синхронизации';

    protected static ?string $pluralModelLabel = 'Логи синхронизации';

    protected static string|UnitEnum|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return SyncLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncLogs::route('/'),
            'view' => ViewSyncLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Sync logs are created programmatically
    }

    public static function canEdit($record): bool
    {
        return false; // Sync logs should not be edited
    }

    public static function canDelete($record): bool
    {
        return false; // Sync logs should not be deleted
    }

    public static function canDeleteAny(): bool
    {
        return false; // Sync logs should not be deleted
    }
}
