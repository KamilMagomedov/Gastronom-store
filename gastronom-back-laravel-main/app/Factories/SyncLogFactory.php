<?php

namespace App\Factories;

use App\Models\SyncLog;
use Carbon\Carbon;

class SyncLogFactory
{
    public static function createImportStart(string $filename): SyncLog
    {
        return SyncLog::create([
            'source' => '1C',
            'entity_type' => 'products',
            'entity_id' => null,
            'operation' => 'import',
            'status' => 'success',
            'message' => 'Импорт начат',
            'data' => [
                'filename' => $filename,
                'started_at' => Carbon::now()->toISOString(),
                'products_processed' => 0,
                'products_created' => 0,
                'products_updated' => 0,
                'images_processed' => 0,
                'errors' => [],
            ],
        ]);
    }

    public static function createEmptyImport(string $filename): SyncLog
    {
        return SyncLog::create([
            'source' => '1C',
            'entity_type' => 'products',
            'entity_id' => null,
            'operation' => 'import',
            'status' => 'success',
            'message' => "Импорт завершен: товары не найдены в файле {$filename}",
            'data' => [
                'filename' => $filename,
                'started_at' => Carbon::now()->toISOString(),
                'finished_at' => Carbon::now()->toISOString(),
                'products_processed' => 0,
                'products_created' => 0,
                'products_updated' => 0,
                'images_processed' => 0,
                'errors' => ['No products found in XML'],
            ],
        ]);
    }

    public static function createErrorImport(string $filename, string $errorMessage, array $additionalData = []): SyncLog
    {
        return SyncLog::create([
            'source' => '1C',
            'entity_type' => 'products',
            'entity_id' => null,
            'operation' => 'import',
            'status' => 'error',
            'message' => $errorMessage,
            'data' => array_merge([
                'filename' => $filename,
                'started_at' => Carbon::now()->toISOString(),
                'finished_at' => Carbon::now()->toISOString(),
                'products_processed' => 0,
                'products_created' => 0,
                'products_updated' => 0,
                'images_processed' => 0,
                'errors' => [$errorMessage],
            ], $additionalData),
        ]);
    }
}
