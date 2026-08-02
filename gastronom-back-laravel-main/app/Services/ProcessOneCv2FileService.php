<?php

namespace App\Services;

use App\Factories\SyncLogFactory;
use App\Models\Product;
use App\Models\SyncLog;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use XMLReader;

class ProcessOneCv2FileService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository
    ) {}

    public function processFile(string $filename, string $storageDisk = 'local'): void
    {
        $filename = $this->sanitizeFilename($filename);

        if (! Storage::disk($storageDisk)->exists("onec_v2/{$filename}")) {
            throw new \Exception("File not found: onec_v2/{$filename}");
        }

        $syncLog = SyncLogFactory::createImportStart($filename);

        try {
            $fileType = $this->determineFileType($filename);

            switch ($fileType) {
                case 'import':
                    $this->processImportFile($filename, $syncLog, $storageDisk);
                    break;
                case 'offers':
                    $this->processOffersFile($filename, $syncLog, $storageDisk);
                    break;
                case 'prices':
                    $this->processPricesFile($filename, $syncLog, $storageDisk);
                    break;
                case 'rests':
                    $this->processRestsFile($filename, $syncLog, $storageDisk);
                    break;
            }

            $this->completeImport($syncLog);

        } catch (\Exception $e) {
            $this->failImport($syncLog, $e->getMessage());
            throw $e;
        }
    }

    protected function sanitizeFilename(string $filename): string
    {
        return ltrim($filename, '/');
    }

    private function updateSyncLogWithStats(SyncLog $syncLog, string $message, array $stats, string $fileType): void
    {
        $syncLog->update([
            'message' => $message,
            'data' => array_merge($syncLog->data ?? [], $stats, [
                'file_type' => $fileType,
                'completed_at' => now()->toISOString(),
            ]),
        ]);
    }

    private function logProcessingCompletion(string $filename, array $stats): void
    {
        Log::channel('onec')->info('File processing completed', [
            'filename' => $filename,
            ...$stats,
        ]);
    }

    private function createXmlReader(string $filename, string $storageDisk = 'local'): XMLReader
    {
        $reader = new XMLReader;
        $filePath = Storage::disk($storageDisk)->path("onec_v2/{$filename}");

        if (! $reader->open($filePath)) {
            throw new \Exception("Failed to open XML file: {$filename}");
        }

        return $reader;
    }

    private function determineFileType(string $filename): string
    {
        $filename = basename(strtolower($filename));

        if (str_starts_with($filename, 'import__')) {
            return 'import';
        }

        if (str_starts_with($filename, 'offers__')) {
            return 'offers';
        }

        if (str_starts_with($filename, 'prices__')) {
            return 'prices';
        }

        if (str_starts_with($filename, 'rests__')) {
            return 'rests';
        }

        throw new \Exception("Unknown file type: {$filename}");
    }

    /**
     * @throws \Exception
     */
    private function processImportFile(string $filename, SyncLog $syncLog, string $storageDisk): void
    {
        $reader = $this->createXmlReader($filename, $storageDisk);

        $processedCount = 0;
        $updatedCount = 0;
        $createdCount = 0;
        $categoriesProcessed = 0;

        while ($reader->read()) {

            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Группа') {
                $groupXml = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($groupXml) {
                    $this->processCategory($groupXml);
                    $categoriesProcessed++;
                }
            }
        }

        $reader->close();
        $reader = $this->createXmlReader($filename, $storageDisk);

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Товар') {
                $productXml = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($productXml) {
                    $result = $this->processProduct($productXml);
                    $processedCount++;

                    if ($result['is_new']) {
                        $createdCount++;
                    } else {
                        $updatedCount++;
                    }
                }
            }
        }

        $reader->close();

        $stats = [
            'products_processed' => $processedCount,
            'products_created' => $createdCount,
            'products_updated' => $updatedCount,
            'categories_processed' => $categoriesProcessed,
        ];

        $this->updateSyncLogWithStats($syncLog,
            "Import file processed: {$processedCount} products, {$createdCount} created, {$updatedCount} updated, {$categoriesProcessed} categories",
            $stats,
            'import'
        );

        $this->logProcessingCompletion($filename, [
            'processed_count' => $processedCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'categories_processed' => $categoriesProcessed,
        ]);
    }

    private function processOffersFile(string $filename, SyncLog $syncLog, string $storageDisk): void
    {
        $reader = $this->createXmlReader($filename, $storageDisk);

        $processedCount = 0;
        $updatedCount = 0;
        $deletedCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Предложение') {
                $offerXml = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($offerXml) {
                    $result = $this->processOffer($offerXml);
                    $processedCount++;

                    if ($result['is_new']) {
                        $updatedCount++;
                    }

                    if ($result['is_deleted']) {
                        $deletedCount++;
                    }
                }
            }
        }

        $reader->close();

        $stats = [
            'offers_processed' => $processedCount,
            'offers_updated' => $updatedCount,
            'offers_deleted' => $deletedCount,
        ];

        $this->updateSyncLogWithStats($syncLog,
            "Offers file processed: {$processedCount} offers, {$updatedCount} updated, {$deletedCount} deleted",
            $stats,
            'offers'
        );

        $this->logProcessingCompletion($filename, [
            'processed_count' => $processedCount,
            'updated_count' => $updatedCount,
            'deleted_count' => $deletedCount,
        ]);
    }

    private function processPricesFile(string $filename, SyncLog $syncLog, string $storageDisk): void
    {
        $reader = $this->createXmlReader($filename, $storageDisk);

        $processedCount = 0;
        $updatedCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Предложение') {
                $offerXml = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($offerXml) {
                    $result = $this->processPrice($offerXml);
                    $processedCount++;

                    if (! $result['is_new']) {
                        $updatedCount++;
                    }
                }
            }
        }

        $reader->close();

        $stats = [
            'prices_processed' => $processedCount,
            'prices_updated' => $updatedCount,
        ];

        $this->updateSyncLogWithStats($syncLog,
            "Prices file processed: {$processedCount} prices, {$updatedCount} updated",
            $stats,
            'prices'
        );

        $this->logProcessingCompletion($filename, [
            'processed_count' => $processedCount,
            'updated_count' => $updatedCount,
        ]);
    }

    private function processRestsFile(string $filename, SyncLog $syncLog, string $storageDisk): void
    {
        Log::channel('onec')->info('Processing rests file', [
            'filename' => $filename,
            'sync_log_id' => $syncLog->id,
        ]);

        $reader = $this->createXmlReader($filename, $storageDisk);

        $processedCount = 0;
        $updatedCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Предложение') {
                $restXml = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($restXml) {
                    $result = $this->processRest($restXml);
                    $processedCount++;

                    if ($result['product'] !== null) {
                        $updatedCount++;
                    }
                }
            }
        }

        $reader->close();

        $stats = [
            'rests_processed' => $processedCount,
            'rests_updated' => $updatedCount,
        ];

        $this->updateSyncLogWithStats($syncLog,
            "Rests file processed: {$processedCount} rests, {$updatedCount} updated",
            $stats,
            'rests'
        );

        $this->logProcessingCompletion($filename, [
            'processed_count' => $processedCount,
            'updated_count' => $updatedCount,
        ]);
    }

    private function processOffer(SimpleXMLElement $offerXml): array
    {
        $externalId = (string) $offerXml->Ид;
        $isDeleted = ((string) $offerXml->ПометкаУдаления) === 'true';

        if (empty($externalId)) {
            throw new \Exception('Offer external ID is missing');
        }

        $product = $this->productRepository->findByExternalId($externalId);

        if (! $product) {
            Log::channel('onec')->warning('Product not found for offer processing', [
                'external_id' => $externalId,
                'is_deleted' => $isDeleted,
            ]);

            return [
                'product' => null,
                'is_new' => false,
                'is_deleted' => $isDeleted,
                'external_id' => $externalId,
            ];
        }

        if (isset($offerXml->Наименование) && ! empty((string) $offerXml->Наименование)) {
            $product->name = (string) $offerXml->Наименование;
            $product->slug = $this->generateSlug($product->name, $product->external_id);
        }

        if ($isDeleted) {
            $product->is_active = false;
        } else {
            $product->is_active = true;
        }

        $product->save();

        return [
            'product' => $product,
            'is_new' => true, // считаем обновлением
            'is_deleted' => $isDeleted,
            'external_id' => $externalId,
        ];
    }

    private function processPrice(SimpleXMLElement $offerXml): array
    {
        $externalId = (string) $offerXml->Ид;

        if (empty($externalId)) {
            throw new \Exception('Offer external ID is missing');
        }

        $product = $this->productRepository->findByExternalId($externalId);

        if (! $product) {
            Log::channel('onec')->warning('Product not found for price update', [
                'external_id' => $externalId,
            ]);

            return [
                'product' => null,
                'is_new' => false,
                'external_id' => $externalId,
            ];
        }

        if (isset($offerXml->Цены->Цена)) {
            $collection = collect();

            foreach ($offerXml->Цены->Цена as $p) {
                $collection->push([
                    'price' => (float) $p->ЦенаЗаЕдиницу,
                    'presentation' => (string) $p->Представление,
                ]);
            }

            $sorted = $collection->sortBy('price');

            $minItem = $sorted->first();
            $maxItem = $sorted->last();

            $price = $minItem['price'];
            $pricePresentation = $minItem['presentation'];

            $oldPrice = $maxItem['price'];
            $oldPricePresentation = $maxItem['presentation'];

            if ($price > 0) {
                $product->price = $price;
                $product->price_representation = $pricePresentation;
            }

            if ($oldPrice > 0) {
                $product->old_price = $oldPrice;
                $product->old_price_representation = $oldPricePresentation;
            }

            if ($price > 0 || $oldPrice > 0) {
                $product->save();
            }
        }

        return [
            'product' => $product,
            'is_new' => false,
            'external_id' => $externalId,
        ];
    }

    private function processRest(SimpleXMLElement $restXml): array
    {
        $externalId = (string) $restXml->Ид;

        if (empty($externalId)) {
            throw new \Exception('Rest external ID is missing');
        }

        $product = $this->productRepository->findByExternalId($externalId);

        if (! $product) {
            Log::channel('onec')->warning('Product not found for rest update', [
                'external_id' => $externalId,
            ]);

            return [
                'product' => null,
                'external_id' => $externalId,
            ];
        }

        if (isset($restXml->Остатки->Остаток)) {
            $totalStock = 0;

            foreach ($restXml->Остатки->Остаток as $item) {

                if (isset($item->Склад->Количество)) {
                    $totalStock += (float) $item->Склад->Количество;
                }
            }

            $product->stock_quantity = max($totalStock, 0);
            $product->in_stock = $totalStock > 0;
            $product->save();
        }

        return [
            'product' => $product,
            'external_id' => $externalId,
        ];
    }

    private function processProduct(SimpleXMLElement $productXml): array
    {
        $externalId = (string) $productXml->Ид;

        if (empty($externalId)) {
            throw new \Exception('Product external ID is missing');
        }

        $product = $this->productRepository->findByExternalId($externalId);

        $isNew = false;

        if (! $product) {
            $product = new Product([
                'external_id' => $externalId,
                'price' => 0,
            ]);
            $isNew = true;
        }

        $this->updateProductFromXml($product, $productXml);

        $product->save();

        return [
            'product' => $product,
            'is_new' => $isNew,
            'external_id' => $externalId,
        ];
    }

    private function processCategory(SimpleXMLElement $groupXml): void
    {
        $groupId = (string) $groupXml->Ид;
        $groupName = (string) ($groupXml->Наименование ?? $groupXml->Имя ?? '');

        if (empty($groupId)) {
            return;
        }

        $categoryData = [
            'external_id' => $groupId,
            'name' => $groupName,
            'slug' => \Str::slug($groupName.'-'.$groupId),
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 0,
        ];

        $this->categoryRepository->createIfNotExists($categoryData);

        Log::channel('onec')->info('Category processed', [
            'group_id' => $groupId,
            'group_name' => $groupName,
        ]);
    }

    private function updateProductFromXml(Product $product, SimpleXMLElement $xml): void
    {
        if (isset($xml->Наименование) && ! empty((string) $xml->Наименование)) {
            $product->name = (string) $xml->Наименование;
            $product->slug = $this->generateSlug($product->name, $product->external_id);
        }

        if (isset($xml->Артикул) && ! empty((string) $xml->Артикул)) {
            $product->sku = (string) $xml->Артикул;
        }

        if (isset($xml->Описание) && ! empty((string) $xml->Описание)) {
            $product->description = (string) $xml->Описание;
        }

        if (isset($xml->Цены->Цена)) {
            $price = (float) $xml->Цены->Цена;
            $product->price = $price > 0 ? $price : $product->price;
        }

        if (isset($xml->Количество)) {
            $stock = (int) $xml->Количество;
            $product->stock_quantity = $stock;
            $product->in_stock = $stock > 0;
        }

        if (isset($xml->БазоваяЕдиница) && ! empty((string) $xml->БазоваяЕдиница)) {
            $unitCode = (string) $xml->БазоваяЕдиница;
            $product->unit = $this->getUnitNameByCode($unitCode);
        }

        if (isset($xml->Вес)) {
            $product->weight = (float) $xml->Вес;
        }

        if (isset($xml->ПометкаУдаления)) {
            $isDeleted = ((string) $xml->ПометкаУдаления) === 'true';
            $product->is_active = ! $isDeleted;
        }

        if (isset($xml->ЗначенияРеквизитов->ЗначениеРеквизита)) {
            foreach ($xml->ЗначенияРеквизитов->ЗначениеРеквизита as $requisite) {
                if ((string) $requisite->Наименование === 'ТипНоменклатуры') {
                    $typeValue = (string) $requisite->Значение;

                    if ($typeValue !== 'Товар') {
                        $product->is_active = false;
                    }
                    break;
                }
            }
        }

        if ($product->is_active === null) {
            $product->is_active = true;
        }
    }

    private function getUnitNameByCode(string $unitCode): string
    {
        $units = [
            '796' => 'шт',
            '166' => 'кг',
            '168' => 'т',
            '163' => 'г',
            '112' => 'л',
            '113' => 'дм³',
            '006' => 'м',
            '003' => 'мм',
            '004' => 'см',
            '055' => 'м²',
            '778' => 'уп',
            '359' => 'дн',
            '356' => 'ч',
            '355' => 'мин',
            '362' => 'мес',
        ];

        return $units[$unitCode] ?? 'шт';
    }

    private function generateSlug(string $name, string $externalId): string
    {
        $baseSlug = \Str::slug($name.'-'.$externalId);
        $slug = $baseSlug;
        $number = 1;

        // Проверяем только если товар уже существует в базе
        $existingProduct = $this->productRepository->findBySlug($slug);

        while ($existingProduct !== null) {
            $slug = $baseSlug.'-'.$number;
            $existingProduct = $this->productRepository->findBySlug($slug);
            $number++;
        }

        return $slug;
    }

    private function getCurrentProductId(string $externalId): ?int
    {
        return $this->productRepository->getIdByExternalId($externalId);
    }

    private function completeImport(SyncLog $syncLog): void
    {
        $syncLog->update([
            'message' => 'Импорт полностью завершен',
            'data' => array_merge($syncLog->data ?? [], [
                'import_status' => 'completed',
                'completed_at' => now()->toISOString(),
            ]),
        ]);

        Log::channel('onec')->info('Импорт полностью завершен', [
            'sync_log_id' => $syncLog->id,
            'filename' => $syncLog->filename,
        ]);
    }

    private function failImport(SyncLog $syncLog, string $errorMessage): void
    {
        $syncLog->update([
            'message' => "Import failed: {$errorMessage}",
            'data' => array_merge($syncLog->data ?? [], [
                'import_status' => 'failed',
                'error_message' => $errorMessage,
                'failed_at' => now()->toISOString(),
            ]),
        ]);

        Log::channel('onec')->error('Import failed', [
            'sync_log_id' => $syncLog->id,
            'filename' => $syncLog->filename,
            'error' => $errorMessage,
        ]);
    }
}
