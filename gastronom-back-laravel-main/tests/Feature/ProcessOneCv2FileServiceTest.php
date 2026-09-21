<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
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

    public function test_reimporting_same_product_keeps_same_product_and_slug(): void
    {
        $filename = 'import__slug_idempotency.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Товары>
                <Товар>
                    <Ид>product-slug-001</Ид>
                    <Артикул>ART-SLUG-001</Артикул>
                    <Наименование>Тестовый товар</Наименование>
                </Товар>
            </Товары>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $firstProduct = Product::where('external_id', 'product-slug-001')->firstOrFail();
        $firstProductId = $firstProduct->id;
        $firstSlug = $firstProduct->slug;

        $this->service->processFile($filename);

        $secondProduct = Product::where('external_id', 'product-slug-001')->firstOrFail();

        $this->assertSame(1, Product::where('external_id', 'product-slug-001')->count());
        $this->assertSame($firstProductId, $secondProduct->id);
        $this->assertSame($firstSlug, $secondProduct->slug);
    }

    public function test_reimporting_same_offer_keeps_same_product_and_slug(): void
    {
        $importFilename = 'import__offer_slug.xml';

        $importXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Товары>
                <Товар>
                    <Ид>product-offer-001</Ид>
                    <Артикул>ART-OFFER-001</Артикул>
                    <Наименование>Исходный товар</Наименование>
                </Товар>
            </Товары>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$importFilename}", $importXml);

        $this->service->processFile($importFilename);

        $product = Product::where('external_id', 'product-offer-001')->firstOrFail();
        $productId = $product->id;

        $offersFilename = 'offers__slug_idempotency.xml';

        $offersXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>product-offer-001</Ид>
                    <Наименование>Обновленный товар</Наименование>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$offersFilename}", $offersXml);

        $this->service->processFile($offersFilename);

        $firstOfferProduct = Product::where('external_id', 'product-offer-001')->firstOrFail();
        $firstOfferSlug = $firstOfferProduct->slug;

        $this->service->processFile($offersFilename);

        $secondOfferProduct = Product::where('external_id', 'product-offer-001')->firstOrFail();

        $this->assertSame(1, Product::where('external_id', 'product-offer-001')->count());
        $this->assertSame($productId, $secondOfferProduct->id);
        $this->assertSame($firstOfferSlug, $secondOfferProduct->slug);
    }

    public function test_reimporting_category_updates_existing_category(): void
    {
        $filename = 'import__category_update.xml';

        $firstXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Группы>
                <Группа>
                    <Ид>category-001</Ид>
                    <Наименование>Напитки</Наименование>
                </Группа>
            </Группы>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $firstXml);

        $this->service->processFile($filename);

        $firstCategory = Category::where('external_id', 'category-001')->firstOrFail();
        $firstCategoryId = $firstCategory->id;
        $firstSlug = $firstCategory->slug;

        $secondXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Группы>
                <Группа>
                    <Ид>category-001</Ид>
                    <Наименование>Напитки и соки</Наименование>
                </Группа>
            </Группы>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $secondXml);

        $this->service->processFile($filename);

        $secondCategory = Category::where('external_id', 'category-001')->firstOrFail();

        $this->assertSame(1, Category::where('external_id', 'category-001')->count());
        $this->assertSame($firstCategoryId, $secondCategory->id);
        $this->assertSame('Напитки и соки', $secondCategory->name);
        $this->assertNotSame($firstSlug, $secondCategory->slug);
    }

    public function test_import_assigns_product_to_category_from_1c_group(): void
    {
        $filename = 'import__product_category.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Группы>
                <Группа>
                    <Ид>category-drinks-001</Ид>
                    <Наименование>Напитки</Наименование>
                </Группа>
            </Группы>

            <Товары>
                <Товар>
                    <Ид>product-water-001</Ид>
                    <Артикул>ART-WATER-001</Артикул>
                    <Наименование>Минеральная вода</Наименование>
                    <Группы>
                        <Ид>category-drinks-001</Ид>
                    </Группы>
                </Товар>
            </Товары>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $category = Category::where('external_id', 'category-drinks-001')->firstOrFail();
        $product = Product::where('external_id', 'product-water-001')->firstOrFail();

        $this->assertSame($category->id, $product->category_id);
    }

    public function test_reimporting_product_moves_it_to_new_category(): void
    {
        $filename = 'import__product_category_move.xml';

        $firstXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Группы>
                <Группа>
                    <Ид>category-drinks-001</Ид>
                    <Наименование>Напитки</Наименование>
                </Группа>
                <Группа>
                    <Ид>category-water-001</Ид>
                    <Наименование>Вода</Наименование>
                </Группа>
            </Группы>

            <Товары>
                <Товар>
                    <Ид>product-water-move-001</Ид>
                    <Артикул>ART-WATER-MOVE-001</Артикул>
                    <Наименование>Минеральная вода</Наименование>
                    <Группы>
                        <Ид>category-drinks-001</Ид>
                    </Группы>
                </Товар>
            </Товары>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $firstXml);

        $this->service->processFile($filename);

        $firstCategory = Category::where('external_id', 'category-drinks-001')->firstOrFail();
        $product = Product::where('external_id', 'product-water-move-001')->firstOrFail();

        $productId = $product->id;

        $this->assertSame($firstCategory->id, $product->category_id);

        $secondXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Группы>
                <Группа>
                    <Ид>category-drinks-001</Ид>
                    <Наименование>Напитки</Наименование>
                </Группа>
                <Группа>
                    <Ид>category-water-001</Ид>
                    <Наименование>Вода</Наименование>
                </Группа>
            </Группы>

            <Товары>
                <Товар>
                    <Ид>product-water-move-001</Ид>
                    <Артикул>ART-WATER-MOVE-001</Артикул>
                    <Наименование>Минеральная вода</Наименование>
                    <Группы>
                        <Ид>category-water-001</Ид>
                    </Группы>
                </Товар>
            </Товары>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $secondXml);

        $this->service->processFile($filename);

        $secondCategory = Category::where('external_id', 'category-water-001')->firstOrFail();
        $updatedProduct = Product::where('external_id', 'product-water-move-001')->firstOrFail();

        $this->assertSame($productId, $updatedProduct->id);
        $this->assertSame($secondCategory->id, $updatedProduct->category_id);
        $this->assertNotSame($firstCategory->id, $updatedProduct->category_id);
    }

    public function test_rests_file_updates_stock_from_direct_quantity(): void
    {
        $importFilename = 'import__rest_product.xml';

        $importXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <Каталог>
            <Товары>
                <Товар>
                    <Ид>product-rest-001</Ид>
                    <Артикул>ART-REST-001</Артикул>
                    <Наименование>Товар с остатком</Наименование>
                </Товар>
            </Товары>
        </Каталог>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$importFilename}", $importXml);

        $this->service->processFile($importFilename);

        $restsFilename = 'rests__direct_quantity.xml';

        $restsXml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>product-rest-001</Ид>
                    <Остатки>
                        <Остаток>
                            <Количество>10</Количество>
                        </Остаток>
                    </Остатки>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$restsFilename}", $restsXml);

        $this->service->processFile($restsFilename);

        $product = Product::where('external_id', 'product-rest-001')->firstOrFail();

        $this->assertSame(10, $product->stock_quantity);
        $this->assertTrue($product->in_stock);
    }

    public function test_rests_file_updates_stock_from_nested_warehouse_quantity(): void
    {
        Product::create([
            'external_id' => 'product-rest-warehouse-001',
            'name' => 'Товар со складскими остатками',
            'slug' => 'product-rest-warehouse-001',
            'price' => 100,
            'stock_quantity' => 0,
            'in_stock' => false,
        ]);

        $filename = 'rests__warehouse_quantity.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>product-rest-warehouse-001</Ид>
                    <Остатки>
                        <Остаток>
                            <Склад>
                                <Количество>4</Количество>
                            </Склад>
                        </Остаток>
                        <Остаток>
                            <Склад>
                                <Количество>6</Количество>
                            </Склад>
                        </Остаток>
                    </Остатки>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $product = Product::where(
            'external_id',
            'product-rest-warehouse-001'
        )->firstOrFail();

        $this->assertSame(10, $product->stock_quantity);
        $this->assertTrue($product->in_stock);
    }

    public function test_rests_file_sums_multiple_direct_quantities(): void
    {
        Product::create([
            'external_id' => 'product-rest-multiple-001',
            'name' => 'Товар с несколькими остатками',
            'slug' => 'product-rest-multiple-001',
            'price' => 100,
            'stock_quantity' => 0,
            'in_stock' => false,
        ]);

        $filename = 'rests__multiple_direct_quantities.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>product-rest-multiple-001</Ид>
                    <Остатки>
                        <Остаток>
                            <Количество>4</Количество>
                        </Остаток>
                        <Остаток>
                            <Количество>6</Количество>
                        </Остаток>
                    </Остатки>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $product = Product::where(
            'external_id',
            'product-rest-multiple-001'
        )->firstOrFail();

        $this->assertSame(10, $product->stock_quantity);
        $this->assertTrue($product->in_stock);
    }

    public function test_prices_file_updates_single_price_and_representation(): void
    {
        Product::create([
          'external_id' => 'product-price-001',
          'name' => 'Товар с ценой',
          'slug' => 'product-price-001',
          'price' => 50,
          'old_price' => 70,
          'old_price_representation' => '70 RUB за шт',
      ]);

        $filename = 'prices__single_price.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>product-price-001</Ид>
                    <Цены>
                        <Цена>
                            <Представление>86,18 RUB за шт</Представление>
                            <ЦенаЗаЕдиницу>86.18</ЦенаЗаЕдиницу>
                            <Валюта>RUB</Валюта>
                        </Цена>
                    </Цены>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $product = Product::where('external_id', 'product-price-001')->firstOrFail();

        $this->assertSame('86.18', $product->price);
        $this->assertSame('86,18 RUB за шт', $product->price_representation);
        $this->assertNull($product->old_price);
        $this->assertNull($product->old_price_representation);
    }

    public function test_prices_file_does_not_count_missing_product_as_updated(): void
    {
        $filename = 'prices__missing_product.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>missing-product-001</Ид>
                    <Цены>
                        <Цена>
                            <Представление>100 RUB за шт</Представление>
                            <ЦенаЗаЕдиницу>100</ЦенаЗаЕдиницу>
                            <Валюта>RUB</Валюта>
                        </Цена>
                    </Цены>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $syncLog = \App\Models\SyncLog::where('source', '1C')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(1, $syncLog->data['prices_processed']);
        $this->assertSame(0, $syncLog->data['prices_updated']);
    }

    public function test_failed_import_marks_sync_log_as_error(): void
    {
        $filename = 'unknown__broken.xml';

        Storage::disk('local')->put(
            "onec_v2/{$filename}",
            '<?xml version="1.0" encoding="UTF-8"?><КоммерческаяИнформация />'
        );

        try {
            $this->service->processFile($filename);

            $this->fail('Expected import exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Unknown file type', $e->getMessage());
        }

        $syncLog = \App\Models\SyncLog::where('source', '1C')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('error', $syncLog->status);
        $this->assertSame('failed', $syncLog->data['import_status']);
    }

    public function test_successful_import_marks_sync_log_as_completed(): void
    {
        $filename = 'import__successful_status.xml';

        Storage::disk('local')->put(
            "onec_v2/{$filename}",
            '<?xml version="1.0" encoding="UTF-8"?><КоммерческаяИнформация />'
        );

        $this->service->processFile($filename);

        $syncLog = \App\Models\SyncLog::where('source', '1C')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('success', $syncLog->status);
        $this->assertSame('completed', $syncLog->data['import_status']);
    }

    public function test_missing_deleted_offer_is_not_counted_as_deleted(): void
    {
        $filename = 'offers__missing_deleted.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>missing-product-delete-001</Ид>
                    <ПометкаУдаления>true</ПометкаУдаления>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $syncLog = \App\Models\SyncLog::where('source', '1C')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(1, $syncLog->data['offers_processed']);
        $this->assertSame(0, $syncLog->data['offers_updated']);
        $this->assertSame(0, $syncLog->data['offers_deleted']);
    }

    public function test_existing_deleted_offer_is_counted_as_updated_and_deleted(): void
    {
        Product::create([
            'external_id' => 'product-delete-001',
            'name' => 'Товар для удаления',
            'slug' => 'product-delete-001',
            'price' => 100,
            'is_active' => true,
        ]);

        $filename = 'offers__existing_deleted.xml';

        $xml = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <КоммерческаяИнформация>
        <ПакетПредложений>
            <Предложения>
                <Предложение>
                    <Ид>product-delete-001</Ид>
                    <ПометкаУдаления>true</ПометкаУдаления>
                </Предложение>
            </Предложения>
        </ПакетПредложений>
    </КоммерческаяИнформация>
    XML;

        Storage::disk('local')->put("onec_v2/{$filename}", $xml);

        $this->service->processFile($filename);

        $product = Product::where('external_id', 'product-delete-001')->firstOrFail();

        $syncLog = \App\Models\SyncLog::where('source', '1C')
            ->latest('id')
            ->firstOrFail();

        $this->assertFalse($product->is_active);
        $this->assertSame(1, $syncLog->data['offers_processed']);
        $this->assertSame(1, $syncLog->data['offers_updated']);
        $this->assertSame(1, $syncLog->data['offers_deleted']);
    }
}