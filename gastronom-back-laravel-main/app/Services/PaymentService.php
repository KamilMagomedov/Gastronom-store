<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function createTransaction(
        Order $order,
        string $paymentMethod,
        ?string $gateway = null
    ): Transaction {
        return DB::transaction(function () use (
            $order,
            $paymentMethod,
            $gateway
        ): Transaction {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array(
                $lockedOrder->getRawOriginal('payment_status'),
                [
                    PaymentStatus::PAID->value,
                    PaymentStatus::PARTIALLY_REFUNDED->value,
                ],
                true
            )) {
                throw new \DomainException('Order is already paid');
            }

            $transaction = Transaction::create([
                'order_id' => $lockedOrder->id,
                'customer_id' => $lockedOrder->customer_id,
                'amount' => $lockedOrder->total_amount,
                'currency' => 'RUB',
                'payment_method' => $paymentMethod,
                'gateway' => $gateway,
                'status' => TransactionStatus::PENDING,
            ]);

            $lockedOrder->update([
                'payment_status' => PaymentStatus::PENDING->value,
            ]);

            return $transaction;
        });
    }

    public function processPayment(
        Transaction $transaction,
        array $gatewayResponse
    ): Transaction {
        return DB::transaction(function () use (
            $transaction,
            $gatewayResponse
        ): Transaction {
            $currentTransaction = Transaction::query()
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $currentTransaction->status,
                [
                    TransactionStatus::PENDING,
                    TransactionStatus::PROCESSING,
                ],
                true
            )) {
                return $currentTransaction;
            }

            $incomingPaymentId = $gatewayResponse['transaction_id'] ?? null;

            if (
                $incomingPaymentId !== null
                && $currentTransaction->gateway_transaction_id !== null
                && (string) $currentTransaction->gateway_transaction_id
                    !== (string) $incomingPaymentId
            ) {
                throw new \DomainException(
                    'Payment transaction ID mismatch'
                );
            }

            $success = $gatewayResponse['success'] ?? false;

            $currentTransaction->update([
                'gateway_response' => $gatewayResponse,
                'gateway_transaction_id' =>
                    $incomingPaymentId
                    ?? $currentTransaction->gateway_transaction_id,
                'status' => $success
                    ? TransactionStatus::COMPLETED
                    : TransactionStatus::FAILED,
                'processed_at' => now(),
                'notes' => $success
                    ? $currentTransaction->notes
                    : ($gatewayResponse['message'] ?? 'Payment failed'),
            ]);

            $currentTransaction->order()->update([
                'payment_status' => $success
                    ? PaymentStatus::PAID->value
                    : PaymentStatus::FAILED->value,
            ]);

            return $currentTransaction->refresh();
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
