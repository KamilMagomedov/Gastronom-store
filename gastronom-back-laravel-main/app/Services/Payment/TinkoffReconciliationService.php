<?php

namespace App\Services\Payment;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Gateways\TinkoffGateway;

class TinkoffReconciliationService
{
    public function inspect(
        Transaction $transaction,
        TinkoffGateway $gateway
    ): array {
        $current = $transaction->fresh();

        if (
            ! $current
            || $current->gateway !== 'tinkoff'
            || $current->status !== TransactionStatus::PROCESSING
        ) {
            throw new PaymentException(
                'Transaction is not eligible for reconciliation'
            );
        }

        if (! $current->gateway_order_id) {
            throw new PaymentException(
                'Gateway order ID is missing'
            );
        }

        $response = $gateway->checkOrderStatus(
          (string) $current->gateway_order_id
        );

        if (
          ! isset($response['OrderId'])
          || ! is_scalar($response['OrderId'])
          || (string) $response['OrderId']
              !== (string) $current->gateway_order_id
        ) {
          throw new PaymentException(
              'Bank order ID mismatch'
          );
        }
      return $response;
    }
}
