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

        if (($response['Success'] ?? null) !== true) {
          throw new PaymentException(
              'Invalid bank response'
          );
        }

        if (
            ! isset($response['OrderId'])
            || ! is_scalar($response['OrderId'])
            || (string) $response['OrderId'] !== (string) $current->gateway_order_id
        ) {
            throw new PaymentException(
                'Bank order ID mismatch'
            );
        }

        if (
          ! array_key_exists('Payments', $response)
          || ! is_array($response['Payments'])
          || ! array_is_list($response['Payments'])
        ) {
          throw new PaymentException(
              'Invalid bank payments response'
          );
        }

        $payments = $response['Payments'];

        if (
            $current->gateway_transaction_id === null
            && count($payments) > 1
        ) {
            throw new PaymentException(
                'Ambiguous bank payments'
            );
        }

        // Convert the decimal transaction amount to kopecks.
        $amount = (string) $current->amount;

        if (! preg_match('/^\d+\.\d{2}$/', $amount)) {
            throw new PaymentException(
                'Invalid transaction amount'
            );
        }

        [$rubles, $kopecks] = explode('.', $amount);

        $expectedAmount = ((int) $rubles * 100) + (int) $kopecks;

        $matchingPayments = [];

        if ($current->gateway_transaction_id !== null) {
            $expectedPaymentId = (string) $current->gateway_transaction_id;

            foreach ($payments as $payment) {
                if (! is_array($payment)) {
                    throw new PaymentException(
                        'Invalid bank payments response'
                    );
                }

                $paymentId = $payment['PaymentId'] ?? null;

                if (
                    ! is_int($paymentId)
                    && ! (is_string($paymentId) && ctype_digit($paymentId))
                ) {
                    throw new PaymentException(
                        'Bank payment ID mismatch'
                    );
                }

                if ((string) $paymentId === $expectedPaymentId) {
                    $matchingPayments[] = $payment;
                }
            }

            if (count($matchingPayments) !== 1) {
                throw new PaymentException(
                    'Bank payment ID mismatch'
                );
            }
        } else {
            // Multiple bank payments without a local PaymentId
            // cannot be matched unambiguously.
            $matchingPayments = $payments;
        }

        foreach ($matchingPayments as $payment) {
          if (
              ! is_array($payment)
              || ! isset($payment['Amount'])
              || ! is_int($payment['Amount'])
              || $payment['Amount'] !== $expectedAmount
          ) {
              throw new PaymentException(
                  'Bank payment amount mismatch'
              );
          }

          $status = $payment['Status'] ?? null;

          if (
              ! is_string($status)
              || trim($status) === ''
          ) {
              throw new PaymentException(
                  'Invalid bank payment status'
              );
          }

          $knownStatuses = [
            'NEW',
            'FORM_SHOWED',
            'AUTHORIZING',
            'AUTH_FAIL',
            '3DS_CHECKING',
            '3DS_CHECKED',
            'AUTHORIZED',
            'CONFIRMING',
            'CONFIRMED',
            'REVERSING',
            'REVERSED',
            'PARTIAL_REVERSED',
            'REFUNDING',
            'REFUNDED',
            'PARTIAL_REFUNDED',
            'CANCELED',
            'REJECTED',
          ];

          if (! in_array($status, $knownStatuses, true)) {
            throw new PaymentException(
                'Unknown bank payment status'
            );
          }
        }
        return $response;
    }

  public function inspectSummary(
    Transaction $transaction,
    TinkoffGateway $gateway
  ): array {
    // Capture the latest database state before contacting the bank.
    $initial = $transaction->fresh();

    if (! $initial) {
        throw new PaymentException(
            'Transaction changed during reconciliation'
        );
    }

    // Reuse the existing bank response validation.
    $response = $this->inspect($transaction, $gateway);

    // A webhook may have changed the transaction during the request.
    $current = $transaction->fresh();

    if (
        ! $current
        || $current->status !== TransactionStatus::PROCESSING
        || $current->status !== $initial->status
        || $current->gateway !== $initial->gateway
        || $current->gateway_order_id !== $initial->gateway_order_id
        || $current->gateway_transaction_id !== $initial->gateway_transaction_id
        || $current->order_id !== $initial->order_id
        || (string) $current->amount !== (string) $initial->amount
        || (string) $current->gateway_order_id !== (string) $response['OrderId']
    ) {
        throw new PaymentException(
            'Transaction changed during reconciliation'
        );
    }

    $payments = $response['Payments'];

    if ($payments === []) {
        return [
            'payment_id' => null,
            'bank_status' => null,
            'classification' => 'unknown',
        ];
    }

    // Find the payment belonging to this transaction.
    if ($current->gateway_transaction_id !== null) {
        $payments = array_values(array_filter(
            $payments,
            fn (array $payment): bool =>
                (string) $payment['PaymentId']
                    === (string) $current->gateway_transaction_id
        ));
    }

    if (count($payments) !== 1) {
        throw new PaymentException(
            'Bank payment ID mismatch'
        );
    }

    $payment = $payments[0];

    $classifier = new TinkoffPaymentStatusClassifier();

    return [
        'payment_id' => (string) $payment['PaymentId'],
        'bank_status' => $payment['Status'],
        'classification' => $classifier->classify(
            $payment['Status']
        ),
    ];
  }
}
