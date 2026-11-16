<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Console\Command;

class InspectStaleTinkoffPayments extends Command
{
    protected $signature = 'payments:inspect-stale-tinkoff
                            {--minutes=10 : Minimum age of payment attempt in minutes}';

    protected $description = 'Inspect Tinkoff payments stuck in processing without changing payment states';

    public function handle(): int
    {
        $minutes = filter_var(
            $this->option('minutes'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($minutes === false) {
            $this->error('Minutes must be a positive integer.');

            return self::FAILURE;
        }

        $cutoff = now()->subMinutes($minutes);

        $count = 0;

        Transaction::query()
            ->where('gateway', 'tinkoff')
            ->where('status', TransactionStatus::PROCESSING->value)
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use (&$count) {
                foreach ($transactions as $transaction) {
                    $count++;

                    $this->line(sprintf(
                        'Transaction #%s | Order #%s | Gateway Order ID: %s | Status: processing',
                        $transaction->id,
                        $transaction->order_id,
                        $transaction->gateway_order_id ?? 'missing'
                    ));
                }
            });

        $this->info("Found {$count} stale Tinkoff payment attempts.");

        return self::SUCCESS;
    }
}
