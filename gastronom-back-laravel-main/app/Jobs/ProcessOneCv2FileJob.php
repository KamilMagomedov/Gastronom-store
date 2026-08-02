<?php

namespace App\Jobs;

use App\Services\ProcessOneCv2FileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOneCv2FileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        private string $filename,
        private string $storageDisk = 'local'
    ) {}

    public function handle(ProcessOneCv2FileService $service): void
    {
        try {
            $service->processFile($this->filename, $this->storageDisk);
        } catch (\Exception $e) {
            Log::channel('onec')->error('1C v2 file processing failed', [
                'filename' => $this->filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'job_id' => $this->job->getJobId(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('onec')->error('1C v2 file job failed permanently', [
            'filename' => $this->filename,
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId(),
        ]);
    }
}
