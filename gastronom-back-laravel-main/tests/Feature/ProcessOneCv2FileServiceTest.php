<?php

namespace Tests\Feature;

use App\Services\ProcessOneCv2FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class ProcessOneCv2FileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProcessOneCv2FileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->service = app(ProcessOneCv2FileService::class);
    }

    public function test_rejects_parent_directory_traversal_in_filename(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->processFile('../import__evil.xml');
    }

    public function test_allows_nested_relative_1c_paths(): void
    {
        $filename = 'goods/1/import__test.xml';

        Storage::disk('local')->put(
            "onec_v2/{$filename}",
            '<?xml version="1.0" encoding="UTF-8"?><КоммерческаяИнформация />'
        );

        $this->service->processFile($filename);

        $syncLog = \App\Models\SyncLog::where('source', '1C')->first();

        $this->assertNotNull($syncLog);
        $this->assertSame($filename, $syncLog->data['filename']);
    }

    public function test_rejects_parent_directory_traversal_inside_nested_path(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->processFile('goods/../import__evil.xml');
    }

    public function test_rejects_windows_parent_directory_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->processFile('..\\import__evil.xml');
    }

    public function test_rejects_absolute_path(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->processFile('/import__evil.xml');
    }
}