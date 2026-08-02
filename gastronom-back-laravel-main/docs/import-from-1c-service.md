# ImportFrom1CService Documentation

## Overview

`ImportFrom1CService` provides memory-efficient XML import functionality using XMLReader and Laravel Bus Batches.

## Key Features

### 1. File Existence Validation
- Checks if XML file exists before processing
- Throws descriptive exceptions for missing files

### 2. Memory-Efficient Processing
- Uses XMLReader for streaming XML parsing
- Processes products in configurable batches
- Prevents memory overflow with large files

### 3. Dynamic Batch Creation
- Creates batch jobs dynamically during XML parsing
- Adds jobs to batch immediately when batch size is reached
- Prevents memory accumulation by not storing all jobs in array

### 4. Progress Tracking
- Counts total products before import starts
- Updates SyncLog with progress information
- Tracks created/updated product counts
- Provides completion status through batch callbacks

## Usage

### Basic Import

```php
use App\Services\ImportFrom1CService;

$service = new ImportFrom1CService();
$service->importFromFile('products.xml', 'local');
```

### Count Products

```php
$service = new ImportFrom1CService();
$totalProducts = $service->countProductsInXml('products.xml', 'local');
```

### Process Batch (used by jobs)

```php
$service->processBatch($productBatch, $syncLog);
```

## Error Handling

The service throws exceptions for:
- Missing XML files: `"XML file not found: {filename}"`
- Failed to open XML files: `"Failed to open XML file: {filename}"`
- Invalid XML format

## SyncLog Structure

```json
{
    "source": "1C",
    "entity_type": "products",
    "operation": "import",
    "status": "success|error",
    "data": {
        "filename": "products.xml",
        "total_products": 150,
        "products_processed": 150,
        "products_created": 120,
        "products_updated": 30,
        "import_status": "processing|completed|failed",
        "last_batch_processed_at": "2026-02-16T22:28:15.123456Z",
        "completed_at": "2026-02-16T22:28:15.123456Z"
    }
}
```

## Architecture

- **Service**: Contains all business logic
- **Jobs**: Simple dispatchers that delegate to service
- **XMLReader**: Streams XML without loading entire document
- **Dynamic Batching**: Creates jobs during parsing to minimize memory usage
- **Bus Batches**: Manages job completion and error handling

## Memory Optimization

The service uses a memory-efficient approach:

1. **XMLReader**: Streams XML instead of loading entire document
2. **Dynamic Batch Creation**: Jobs are added to batch immediately during parsing
3. **No Job Accumulation**: Prevents storing all jobs in memory array

```php
// Instead of this (memory intensive):
$jobs = [];
while ($reader->read()) {
    // ... parse product
    if (count($batch) >= $batchSize) {
        $jobs[] = new ProcessProductBatchJob($batch, $syncLog); // Stores in memory
        $batch = [];
    }
}
Bus::batch($jobs)->dispatch();

// We use this (memory efficient):
$batchJobs = Bus::batch([]);
while ($reader->read()) {
    // ... parse product  
    if (count($batch) >= $batchSize) {
        $batchJobs->add(new ProcessProductBatchJob($batch, $syncLog)); // Adds immediately
        $batch = [];
    }
}
$batchJobs->dispatch();
```

## Testing

Run tests with:

```bash
php artisan test tests/Feature/ImportFrom1CServiceTest.php
```

Tests cover:
- File existence validation
- Product counting
- SyncLog creation and updates
- Batch processing
- Error handling
