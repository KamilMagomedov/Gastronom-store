<?php

namespace App\Jobs;

use App\Models\SyncLog;
use App\Services\ImportFrom1CService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProductBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private array $productBatch,
        private SyncLog $syncLog,
        private string $storageDisk = 'local'
    ) {}

    public function handle(ImportFrom1CService $service): void
    {
        $service->processBatch($this->productBatch, $this->syncLog, $this->storageDisk);
    }
}
