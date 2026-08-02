<?php

namespace Tests\Feature;

use App\Jobs\ImportFrom1CJob;
use App\Models\Product;
use App\Models\User;
use App\Services\ImportFrom1CService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OneCImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => '1C Integration',
            'email' => '1c@integration.local',
            'password' => '1C_Secret_Password_2024',
        ]);
    }

    public function test_onec_exchange_route_creates_sync_log()
    {
        Queue::fake();

        $response = $this->get('/integration/1c/exchange?type=catalog&mode=import&filename=test.xml', [
            'PHP_AUTH_USER' => '1c@integration.local',
            'PHP_AUTH_PW' => '1C_Secret_Password_2024',
        ]);

        $response->assertStatus(200);
        $response->assertSee('success');

        Queue::assertPushed(ImportFrom1CJob::class, function ($job) {
            return $job->filename === 'test.xml';
        });
    }

    public function test_onec_checkauth_route()
    {
        $response = $this->get('/integration/1c/exchange?type=catalog&mode=checkauth', [
            'PHP_AUTH_USER' => '1c@integration.local',
            'PHP_AUTH_PW' => '1C_Secret_Password_2024',
        ]);

        $response->assertStatus(200);
        $response->assertSee('success');
        $response->assertSee('SESSION_ID');
    }

    public function test_onec_init_route()
    {
        $response = $this->get('/integration/1c/exchange?type=catalog&mode=init', [
            'PHP_AUTH_USER' => '1c@integration.local',
            'PHP_AUTH_PW' => '1C_Secret_Password_2024',
        ]);

        $response->assertStatus(200);
        $response->assertSee('zip=no');
        $response->assertSee('file_limit=');
    }

    public function test_import_from_1c_job_creates_logs()
    {
        Storage::disk('local')->delete('onec/test.xml');

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<КоммерческаяИнформация>
    <Каталог>
        <Товары>
            <Товар>
                <Ид>test-123</Ид>
                <Артикул>ART-001</Артикул>
                <Наименование>Тестовый продукт</Наименование>
                <Цены>
                    <Цена>
                        <ЦенаЗаЕдиницу>1000.50</ЦенаЗаЕдиницу>
                    </Цена>
                </Цены>
                <Количество>50</Количество>
                <БазоваяЕдиница>шт</БазоваяЕдиница>
            </Товар>
        </Товары>
    </Каталог>
</КоммерческаяИнформация>';

        Storage::disk('local')->makeDirectory('onec');
        Storage::disk('local')->put('onec/test.xml', $xmlContent);

        $job = new ImportFrom1CJob('test.xml');
        $job->handle(app(ImportFrom1CService::class));

        $this->artisan('queue:work --once');

        $this->assertDatabaseHas('products', [
            'external_id' => 'test-123',
            'sku' => 'ART-001',
        ]);

        $this->assertDatabaseHas('sync_logs', [
            'source' => '1C',
            'entity_type' => 'products',
            'operation' => 'import',
            'status' => 'success',
        ]);

        Storage::disk('local')->delete('onec/test.xml');
    }

    public function test_product_model_compatibility()
    {
        $product = Product::create([
            'name' => 'Тестовый товар',
            'slug' => 'test-product',
            'external_id' => 'test-456',
            'sku' => 'ART-002',
            'price' => 1500.75,
            'stock_quantity' => 25,
            'description' => 'Тестовое описание',
            'is_active' => true,
        ]);

        $product->in_stock = $product->stock_quantity > 0;
        $product->save();

        $this->assertEquals(25, $product->stock_quantity);
        $this->assertTrue($product->in_stock);

        $this->assertDatabaseHas('products', [
            'sku' => 'ART-002',
            'external_id' => 'test-456',
        ]);
    }
}
