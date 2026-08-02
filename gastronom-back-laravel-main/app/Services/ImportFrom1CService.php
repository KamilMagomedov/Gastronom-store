<?php

namespace App\Services;

use App\Factories\SyncLogFactory;
use App\Jobs\ProcessProductBatchJob;
use App\Jobs\ProcessProductImagesJob;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use XMLReader;

class ImportFrom1CService
{
    public function importFromFile(string $filename, string $storageDisk = 'local'): void
    {
        if (! Storage::disk($storageDisk)->exists("onec/{$filename}")) {
            throw new \Exception("XML file not found: {$filename}");
        }

        $syncLog = SyncLogFactory::createImportStart($filename);

        try {
            $this->processXmlAndDispatchBatches($filename, $syncLog, $storageDisk);

        } catch (\Exception $e) {
            SyncLogService::fail($syncLog, $e->getMessage());
            throw $e;
        }
    }

    private function processXmlAndDispatchBatches(
        string $filename,
        SyncLog $syncLog,
        string $storageDisk = 'local',
        int $batchSize = 10
    ): void {
        $totalProducts = $this->countProductsInXml($filename, $storageDisk);

        $syncLog->update([
            'data' => array_merge($syncLog->data ?? [], [
                'total_products' => $totalProducts,
                'products_processed' => 0,
                'import_status' => 'processing',
            ]),
        ]);

        $reader = new XMLReader;
        $filePath = Storage::disk($storageDisk)->path("onec/{$filename}");

        if (! $reader->open($filePath)) {
            throw new \Exception("Failed to open XML file: {$filename}");
        }

        $batch = [];

        $batchJobs = Bus::batch([])
            ->then(function () use ($syncLog) {
                $this->finalizeImport($syncLog);
            })
            ->catch(function () use ($syncLog) {
                $syncLog->update([
                    'message' => 'Import failed with errors',
                    'data' => array_merge($syncLog->data ?? [], [
                        'import_status' => 'failed',
                    ]),
                ]);
            })
            ->dispatch();

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Товар') {
                $productXml = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($productXml) {
                    $productData = $this->extractProductData($productXml);
                    $batch[] = $productData;

                    if (count($batch) >= $batchSize) {
                        $batchJobs->add(new ProcessProductBatchJob($batch, $syncLog, $storageDisk));
                        $batch = [];
                    }
                }
            }
        }

        if (! empty($batch)) {
            $batchJobs->add(new ProcessProductBatchJob($batch, $syncLog));
        }

        $reader->close();
    }

    public function countProductsInXml(string $filename, string $storageDisk = 'local'): int
    {
        if (! Storage::disk($storageDisk)->exists("onec/{$filename}")) {
            throw new \Exception("XML file not found: {$filename}");
        }

        $reader = new XMLReader;
        $filePath = Storage::disk($storageDisk)->path("onec/{$filename}");

        if (! $reader->open($filePath)) {
            throw new \Exception("Failed to open XML file: {$filename}");
        }

        $productCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Товар') {
                $productCount++;
            }
        }

        $reader->close();

        return $productCount;
    }

    private function finalizeImport(SyncLog $syncLog): void
    {
        $processedCount = $this->getProcessedProductsCount($syncLog);
        $totalProducts = $syncLog->data['total_products'] ?? 0;

        if ($processedCount >= $totalProducts) {
            $syncLog->update([
                'message' => "Import completed successfully: processed {$processedCount} products",
                'data' => array_merge($syncLog->data ?? [], [
                    'products_processed' => $processedCount,
                    'import_status' => 'completed',
                    'completed_at' => now()->toISOString(),
                ]),
            ]);
        } else {
            $syncLog->update([
                'data' => array_merge($syncLog->data ?? [], [
                    'products_processed' => $processedCount,
                    'import_status' => 'processing',
                ]),
            ]);
        }
    }

    private function getProcessedProductsCount(SyncLog $syncLog): int
    {
        $freshSyncLog = $syncLog->fresh();

        return $freshSyncLog->data['products_processed'] ?? 0;
    }

    public function processBatch(array $batch, SyncLog $syncLog, string $storageDisk = 'local'): void
    {
        $processedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($batch as $productData) {
            try {
                $result = $this->importProduct($productData);

                $processedCount++;
                if ($result['is_new']) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }

                if (! empty($result['images'])) {
                    $this->processProductImages($result['product'], $result['images'], $storageDisk, $syncLog);
                }
            } catch (\Exception $e) {
                Log::channel('onec')->error('Failed to import product in batch', [
                    'product_data' => $productData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Обновляем счетчики в SyncLog
        $currentData = $syncLog->data ?? [];
        $syncLog->update([
            'data' => array_merge($currentData, [
                'products_processed' => ($currentData['products_processed'] ?? 0) + $processedCount,
                'products_created' => ($currentData['products_created'] ?? 0) + $createdCount,
                'products_updated' => ($currentData['products_updated'] ?? 0) + $updatedCount,
                'last_batch_processed_at' => now()->toISOString(),
            ]),
        ]);

        gc_collect_cycles();
    }

    private function extractProductData(\SimpleXMLElement $product): array
    {
        return [
            'external_id' => (string) $product->Ид,
            'article' => (string) $product->Артикул,
            'name' => (string) $product->Наименование,
            'description' => (string) $product->Описание,
            'price' => (float) ($product->Цены->Цена ?? 0),
            'stock' => (int) ($product->Количество ?? 0),
            'unit' => (string) ($product->БазоваяЕдиница ?? 'шт'),
            'images' => $this->extractProductImages($product),
        ];
    }

    private function extractProductImages(\SimpleXMLElement $product): array
    {
        $images = [];

        if (isset($product->Картинка)) {
            foreach ($product->Картинка as $image) {
                $images[] = (string) $image;
            }
        }

        return $images;
    }

    private function importProduct(array $productData): array
    {
        $product = $this->createOrUpdateProduct($productData);

        return [
            'product' => $product,
            'is_new' => ! isset($product->id) || $product->wasRecentlyCreated,
            'images' => $productData['images'] ?? [],
        ];
    }

    private function createOrUpdateProduct(array $productData): Product
    {
        $product = Product::where('external_id', $productData['external_id'])->first();

        if (! $product) {
            $product = new Product;
            $product->external_id = $productData['external_id'];
            $product->name = $productData['name'];
            $product->slug = $this->generateSlug($productData['name'], $productData['external_id']);
            $product->is_active = true;
            $product->price = $productData['price'] ?? 0;
            $product->stock_quantity = $productData['stock'] ?? 0;
        } else {
            if (isset($productData['name'])) {
                $product->name = $productData['name'];
                $product->slug = $this->generateSlug($productData['name'], $productData['external_id']);
            }

            if (isset($productData['price'])) {
                $product->price = $productData['price'];
            }

            if (isset($productData['stock'])) {
                $product->stock_quantity = $productData['stock'];
            }
        }

        $product->sku = $productData['article'];
        $product->in_stock = $product->stock_quantity > 0;
        $product->save();

        return $product;
    }

    protected function generateSlug(string $name, ?string $code = null): string
    {
        $baseSlug = \Str::slug($code ? $name.'-'.$code : $name);
        $slug = $baseSlug;
        $number = 1;

        while (
            Product::query()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$number;
            $number++;
        }

        return $slug;
    }

    public function processSingleImage(
        int $productId,
        string $imagePath,
        string $collection = 'images',
        string $storageDisk = 'local',
        ?SyncLog $syncLog = null
    ): void {
        try {
            $product = Product::findOrFail($productId);
            $fullPath = Storage::disk($storageDisk)->path($imagePath);

            if (! file_exists($fullPath)) {
                Log::channel('onec')->error('Image file not found', [
                    'product_id' => $productId,
                    'image_path' => $imagePath,
                    'full_path' => $fullPath,
                ]);

                if ($syncLog) {
                    $this->incrementImagesProcessed($syncLog);
                }

                return;
            }

            $imageInfo = getimagesize($fullPath);

            if (! $imageInfo) {
                Log::channel('onec')->error('Invalid image file', [
                    'product_id' => $productId,
                    'image_path' => $imagePath,
                ]);

                if ($syncLog) {
                    $this->incrementImagesProcessed($syncLog);
                }

                return;
            }

            $extension = $this->getImageExtension($fullPath);
            $fileName = \Str::uuid().'.'.$extension;

            $product->addMedia($fullPath)
                ->usingFileName($fileName)
                ->toMediaCollection($collection);

            Log::channel('onec')->info('Image processed successfully', [
                'product_id' => $productId,
                'image_path' => $imagePath,
                'collection' => $collection,
            ]);

            if ($syncLog) {
                $this->incrementImagesProcessed($syncLog);
            }

        } catch (\Exception $e) {
            Log::channel('onec')->error('Failed to process image', [
                'product_id' => $productId,
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function incrementImagesProcessed(SyncLog $syncLog): void
    {
        $currentData = $syncLog->data ?? [];
        $syncLog->update([
            'data' => array_merge($currentData, [
                'images_processed' => ($currentData['images_processed'] ?? 0) + 1,
            ]),
        ]);
    }

    private function getImageExtension(string $filePath): string
    {
        $imageInfo = getimagesize($filePath);

        if (! $imageInfo) {
            throw new \Exception("Invalid image file: {$filePath}");
        }

        $mime = $imageInfo['mime'];

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg'
        };
    }

    private function processProductImages(Product $product, array $images = [], string $storageDisk = 'local', ?SyncLog $syncLog = null): void
    {
        if (empty($images)) {
            return;
        }

        $uniqueImages = collect($images)->unique()->toArray();

        $product->clearMediaCollection('images');

        foreach ($uniqueImages as $imagePath) {
            try {
                $correctImagePath = "onec/{$imagePath}";

                ProcessProductImagesJob::dispatch($product->id, $correctImagePath, 'images', $storageDisk, $syncLog);
            } catch (\Exception $e) {
                Log::channel('onec')->error('Failed to dispatch product image job', [
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
