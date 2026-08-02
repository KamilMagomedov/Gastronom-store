<?php

namespace App\Jobs;

use App\Services\GoogleImageSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SearchProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(
        public int $productId,
        public string $productName
    ) {}

    public function handle(GoogleImageSearchService $imageSearchService): void
    {
        $imageSearchService->searchAndDownloadImage($this->productId, $this->productName);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("SearchProductImageJob failed for product {$this->productId}: ".$exception->getMessage());
    }
}
