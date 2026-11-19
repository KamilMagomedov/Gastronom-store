<?php

namespace Tests\Unit;

use App\Services\Payment\TinkoffPaymentStatusClassifier;
use PHPUnit\Framework\TestCase;

class TinkoffPaymentStatusClassifierTest extends TestCase
{
    public function test_it_classifies_payment_statuses_safely(): void
    {
        $classifier = new TinkoffPaymentStatusClassifier();

        $this->assertSame(
            'confirmed',
            $classifier->classify('CONFIRMED')
        );

        $this->assertSame(
            'pending',
            $classifier->classify('AUTHORIZED')
        );

        $this->assertSame(
            'failed',
            $classifier->classify('REJECTED')
        );

        $this->assertSame(
            'unknown',
            $classifier->classify('REFUNDED')
        );

        $this->assertSame(
            'unknown',
            $classifier->classify('UNKNOWN_STATUS')
        );
    }

    public function test_intermediate_statuses_remain_pending(): void
    {
        $classifier = new TinkoffPaymentStatusClassifier();

        $statuses = [
            'NEW',
            'FORM_SHOWED',
            'AUTHORIZING',
            '3DS_CHECKING',
            '3DS_CHECKED',
            'AUTHORIZED',
            'CONFIRMING',
        ];

        foreach ($statuses as $status) {
            $this->assertSame(
                'pending',
                $classifier->classify($status),
                "Status {$status} must remain pending"
            );
        }
    }

    public function test_failed_statuses_are_classified(): void
    {
        $classifier = new TinkoffPaymentStatusClassifier();

        $statuses = [
            'REJECTED',
            'CANCELED',
            'REVERSED',
            'AUTH_FAIL',
        ];

        foreach ($statuses as $status) {
            $this->assertSame(
                'failed',
                $classifier->classify($status)
            );
        }
    }

    public function test_refunds_and_unhandled_statuses_remain_unknown(): void
    {
        $classifier = new TinkoffPaymentStatusClassifier();

        $statuses = [
            'REFUNDED',
            'PARTIAL_REFUNDED',
            'REFUNDING',
            'REVERSING',
            'PARTIAL_REVERSED',
            'UNKNOWN_STATUS',
            '',
        ];

        foreach ($statuses as $status) {
            $this->assertSame(
                'unknown',
                $classifier->classify($status)
            );
        }
    }
}
