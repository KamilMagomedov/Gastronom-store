<?php

namespace Database\Seeders;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing customers and orders, or create some if none exist
        $customers = Customer::all();
        $orders = Order::all();

        if ($customers->isEmpty()) {
            $this->command->info('No customers found. Please run CustomerSeeder first.');

            return;
        }

        if ($orders->isEmpty()) {
            $this->command->info('No orders found. Please run OrderSeeder first.');

            return;
        }

        // Create 10 transactions
        $gateways = ['sberbank', 'tinkoff', 'stripe'];
        $statuses = [
            TransactionStatus::COMPLETED,
            TransactionStatus::COMPLETED,
            TransactionStatus::REFUNDED,
            TransactionStatus::FAILED,
            TransactionStatus::PROCESSING,
            TransactionStatus::COMPLETED,
            TransactionStatus::FAILED,
            TransactionStatus::COMPLETED,
            TransactionStatus::PENDING,
            TransactionStatus::COMPLETED,
        ];

        foreach (range(0, 9) as $index) {
            $order = $orders->random();
            $customer = $customers->random();

            $status = $statuses[$index];
            $processedAt = in_array($status, [TransactionStatus::COMPLETED, TransactionStatus::REFUNDED, TransactionStatus::FAILED])
                ? now()->subMinutes(rand(10, 1440))
                : ($status === TransactionStatus::PROCESSING ? now()->subMinutes(5) : null);

            $gateway = $gateways[array_rand($gateways)];

            Transaction::create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'amount' => $order->total_amount,
                'currency' => 'RUB',
                'payment_method' => 'card',
                'gateway_transaction_id' => substr($gateway, 0, 4).'_'.uniqid(),
                'gateway' => $gateway,
                'status' => $status,
                'gateway_response' => [
                    'success' => $status === TransactionStatus::COMPLETED,
                    'message' => match ($status) {
                        TransactionStatus::COMPLETED => 'Payment successful',
                        TransactionStatus::FAILED => 'Card declined',
                        TransactionStatus::REFUNDED => 'Refund processed',
                        TransactionStatus::PROCESSING => 'Payment processing',
                        default => 'Unknown status',
                    },
                    'code' => match ($status) {
                        TransactionStatus::COMPLETED => '200',
                        TransactionStatus::FAILED => '401',
                        TransactionStatus::REFUNDED => '200',
                        TransactionStatus::PROCESSING => '202',
                        default => '400',
                    },
                    'terminal_id' => '12345678',
                    'payment_id' => uniqid(),
                ],
                'notes' => match ($status) {
                    TransactionStatus::FAILED => 'Card expired or invalid CVV',
                    TransactionStatus::REFUNDED => 'Customer requested refund',
                    default => null,
                },
                'processed_at' => $processedAt,
            ]);
        }

        $this->command->info('Created 10 transactions');
    }
}
