<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'amount',
        'currency',
        'payment_method',
        'gateway_transaction_id',
        'gateway',
        'status',
        'gateway_response',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
        'status' => TransactionStatus::class,
        'currency' => Currency::class,
        'payment_method' => PaymentMethod::class,
        'gateway' => 'string',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getStatusLabel(): string
    {
        return $this->status?->getLabel() ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return $this->status?->getColor() ?? 'default';
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING;
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => TransactionStatus::COMPLETED,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => TransactionStatus::FAILED,
            'processed_at' => now(),
            'notes' => $reason ? ($this->notes.'; '.$reason) : $this->notes,
        ]);
    }
}
