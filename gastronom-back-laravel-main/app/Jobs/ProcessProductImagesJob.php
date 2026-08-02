<?php

namespace App\Jobs;

use App\Models\SyncLog;
use App\Services\ImportFrom1CService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $productId,
        public string $imagePath,
        public string $collection = 'images',
        public string $storageDisk = 'local',
        public ?SyncLog $syncLog = null
    ) {}

    public function handle(ImportFrom1CService $service): void
    {
        $service->processSingleImage($this->productId, $this->imagePath, $this->collection, $this->storageDisk, $this->syncLog);
    }
}
