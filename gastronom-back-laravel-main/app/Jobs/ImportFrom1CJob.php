<?php

namespace App\Jobs;

use App\Services\ImportFrom1CService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportFrom1CJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $filename,
        public string $storageDisk = 'local'
    ) {}

    public function handle(ImportFrom1CService $service): void
    {
        $service->importFromFile($this->filename, $this->storageDisk);
    }
}
