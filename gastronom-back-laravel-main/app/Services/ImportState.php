<?php

namespace App\Services;

class ImportState
{
    public array $data = [
        'filename' => '',
        'started_at' => '',
        'products_processed' => 0,
        'products_created' => 0,
        'products_updated' => 0,
        'images_processed' => 0,
        'errors' => [],
    ];

    public function __construct(string $filename)
    {
        $this->data['filename'] = $filename;
        $this->data['started_at'] = now()->toISOString();
    }

    public function incrementProcessed(): void
    {
        $this->data['products_processed']++;
    }

    public function incrementCreated(): void
    {
        $this->data['products_created']++;
    }

    public function incrementUpdated(): void
    {
        $this->data['products_updated']++;
    }

    public function incrementImagesProcessed(int $count = 1): void
    {
        $this->data['images_processed'] += $count;
    }

    public function addError(string $productId, string $error): void
    {
        $this->data['errors'][] = [
            'product_id' => $productId,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
