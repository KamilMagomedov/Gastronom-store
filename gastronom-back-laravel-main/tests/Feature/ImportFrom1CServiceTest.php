<?php

namespace Tests\Feature;

use App\Models\SyncLog;
use App\Services\ImportFrom1CService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use XMLWriter;

class ImportFrom1CServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImportFrom1CService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImportFrom1CService;
        Storage::fake('local');
    }

    public function test_import_from_file_throws_exception_when_file_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('XML file not found: non_existent_file.xml');

        $this->service->importFromFile('non_existent_file.xml');
    }

    public function test_count_products_throws_exception_when_file_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('XML file not found: non_existent_file.xml');

        $this->service->countProductsInXml('non_existent_file.xml');
    }

    public function test_count_products_in_xml()
    {
        $xmlContent = $this->createTestXmlFile(15);
        Storage::disk('local')->put('onec/test_products.xml', $xmlContent);

        $count = $this->service->countProductsInXml('test_products.xml');

        $this->assertEquals(15, $count);
    }

    public function test_import_from_file_creates_sync_log()
    {
        $xmlContent = $this->createTestXmlFile(5);
        Storage::disk('local')->put('onec/test_import.xml', $xmlContent);

        $this->service->importFromFile('test_import.xml');

        $syncLog = SyncLog::where('source', '1C')
            ->where('entity_type', 'products')
            ->first();

        $this->assertNotNull($syncLog);
        $this->assertEquals('1C', $syncLog->source);
        $this->assertEquals('products', $syncLog->entity_type);
        $this->assertEquals('import', $syncLog->operation);
        $this->assertEquals(5, $syncLog->data['total_products']);

        $this->assertContains($syncLog->data['import_status'], ['processing', 'completed']);
    }

    public function test_process_batch_updates_sync_log()
    {
        $syncLog = SyncLog::create([
            'source' => '1C',
            'entity_type' => 'products',
            'operation' => 'sync',
            'status' => 'success',
            'message' => 'Test batch processing',
            'data' => ['products_processed' => 5],
        ]);

        $productBatch = [
            [
                'external_id' => 'test_1',
                'article' => 'ART001',
                'name' => 'Test Product 1',
                'price' => 100,
                'stock' => 10,
            ],
            [
                'external_id' => 'test_2',
                'article' => 'ART002',
                'name' => 'Test Product 2',
                'price' => 200,
                'stock' => 20,
            ],
        ];

        $this->service->processBatch($productBatch, $syncLog);

        $freshSyncLog = $syncLog->fresh();
        $this->assertEquals(7, $freshSyncLog->data['products_processed']);
        $this->assertEquals(2, $freshSyncLog->data['products_created']);
        $this->assertEquals(0, $freshSyncLog->data['products_updated']);
        $this->assertArrayHasKey('last_batch_processed_at', $freshSyncLog->data);
    }

    private function createTestXmlFile(int $productCount): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('КоммерческаяИнформация');
        $xml->startElement('Каталог');
        $xml->startElement('Товары');

        for ($i = 1; $i <= $productCount; $i++) {
            $xml->startElement('Товар');
            $xml->writeElement('Ид', "product_{$i}");
            $xml->writeElement('Артикул', "ART_{$i}");
            $xml->writeElement('Наименование', "Товар {$i}");
            $xml->writeElement('Описание', "Описание товара {$i}");

            $xml->startElement('Цены');
            $xml->startElement('Цена');
            $xml->writeElement('ЦенаЗаЕдиницу', $i * 100);
            $xml->endElement();
            $xml->endElement();

            $xml->writeElement('Количество', $i * 10);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
