<?php

namespace App\Services;

use App\Models\SyncLog;
use Carbon\Carbon;

class SyncLogService
{
    public static function updateProgress(SyncLog $syncLog, array $progressData): void
    {
        $syncLog->update([
            'message' => "Импорт в процессе: обработано {$progressData['products_processed']} товаров",
            'data' => array_merge($syncLog->data, $progressData),
        ]);
    }

    public static function complete(SyncLog $syncLog, array $resultData): void
    {
        $syncLog->update([
            'status' => 'success',
            'message' => "Импорт завершен: обработано {$resultData['products_processed']} товаров (создано: {$resultData['products_created']}, обновлено: {$resultData['products_updated']})",
            'data' => array_merge($syncLog->data, $resultData, [
                'finished_at' => Carbon::now()->toISOString(),
            ]),
        ]);
    }

    public static function fail(SyncLog $syncLog, string $errorMessage, array $additionalData = []): void
    {
        $syncLog->update([
            'status' => 'error',
            'message' => $errorMessage,
            'data' => array_merge($syncLog->data, $additionalData, [
                'error' => $errorMessage,
                'finished_at' => Carbon::now()->toISOString(),
            ]),
        ]);
    }
}
