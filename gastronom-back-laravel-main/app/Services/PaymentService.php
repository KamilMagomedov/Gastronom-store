<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function createTransaction(Order $order, string $paymentMethod, ?string $gateway = null): Transaction
    {
        return DB::transaction(function () use ($order, $paymentMethod, $gateway) {
            $transaction = Transaction::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'amount' => $order->total_amount,
                'currency' => 'RUB',
                'payment_method' => $paymentMethod,
                'gateway' => $gateway,
                'status' => TransactionStatus::PENDING,
            ]);

            $order->update(['payment_status' => 'pending']);

            return $transaction;
        });
    }

    public function processPayment(Transaction $transaction, array $gatewayResponse): Transaction
    {
        return DB::transaction(function () use ($transaction, $gatewayResponse) {
            $transaction->update([
                'gateway_response' => $gatewayResponse,
                'gateway_transaction_id' => $gatewayResponse['transaction_id'] ?? null,
            ]);

            if ($gatewayResponse['success'] ?? false) {
                $transaction->markAsCompleted();
                $transaction->order->update(['payment_status' => 'paid']);
            } else {
                $transaction->markAsFailed($gatewayResponse['message'] ?? 'Payment failed');
                $transaction->order->update(['payment_status' => 'failed']);
            }

            return $transaction;
        });
    }

    public function refundTransaction(Transaction $transaction, ?float $amount = null, ?string $reason = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $amount, $reason) {
            $refundAmount = $amount ?? $transaction->amount;

            if ($refundAmount > $transaction->amount) {
                throw new \InvalidArgumentException('Refund amount cannot exceed transaction amount');
            }

            $transaction->update([
                'status' => TransactionStatus::REFUNDED,
                'notes' => $reason ? "Refunded: {$reason}" : 'Refunded',
            ]);

            $transaction->order->update(['payment_status' => 'refunded']);

            return $transaction;
        });
    }

    public function getCustomerTransactions(Customer $customer, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $customer->transactions();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->get();
    }

    public function getTransactionStats(?Customer $customer = null): array
    {
        $query = Transaction::query();

        if ($customer) {
            $query->where('customer_id', $customer->id);
        }

        return [
            'total_transactions' => (clone $query)->count(),
            'completed_transactions' => (clone $query)->where('status', TransactionStatus::COMPLETED)->count(),
            'failed_transactions' => (clone $query)->where('status', TransactionStatus::FAILED)->count(),
            'pending_transactions' => (clone $query)->where('status', TransactionStatus::PENDING)->count(),
            'total_amount' => (clone $query)->where('status', TransactionStatus::COMPLETED)->sum('amount'),
        ];
    }

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
