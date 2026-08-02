<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'entity_type',
        'entity_id',
        'operation',
        'status',
        'message',
        'data',
        'synced_at',
    ];

    protected $casts = [
        'data' => 'array',
        'synced_at' => 'datetime',
        'entity_id' => 'integer',
    ];

    public $timestamps = false;

    /**
     * Log successful sync
     */
    public static function logSuccess(string $source, string $entityType, ?int $entityId, string $operation, ?string $message = null, ?array $data = null): self
    {
        return static::create([
            'source' => $source,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Log sync error
     */
    public static function logError(string $source, string $entityType, ?int $entityId, string $operation, string $errorMessage, ?array $data = null): self
    {
        return static::create([
            'source' => $source,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'status' => 'error',
            'message' => $errorMessage,
            'data' => $data,
        ]);
    }

    /**
     * Scope for successful syncs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope for specific source
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for specific entity type
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Check if sync was successful
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if sync failed
     */
    public function isError(): bool
    {
        return $this->status === 'error';
    }
}
