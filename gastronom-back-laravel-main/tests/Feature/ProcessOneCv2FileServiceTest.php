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
}