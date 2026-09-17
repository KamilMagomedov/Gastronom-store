<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentManager;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentManager $paymentManager
    ) {}

    public function initiate(Request $request, Order $order)
    {
        if ($order->customer_id !== auth('customers')->id()) {
            return ApiResponse::laravelError('Access denied', statusCode: 403);
        }

        if ($order->payment_status === 'paid') {
            return ApiResponse::laravelError('Order is already paid', statusCode: 422);
        }

        $validated = $request->validate([
            'payment_method_type' => ['nullable', 'string', 'in:bank_card,sbp'],
        ]);

        try {
            $result = $this->paymentManager->initiateOnlinePayment(
                $order,
                $validated['payment_method_type'] ?? null
            );

            if (! $result) {
                return ApiResponse::laravelError('This payment method does not support online payment', statusCode: 422);
            }

            return ApiResponse::success([
                'payment_url' => $result['payment_url'],
                'transaction_id' => $result['transaction_id'],
                'payment_method_type' => $result['payment_method_type'] ?? 'bank_card',
            ]);
        } catch (PaymentException $e) {
            Log::error('Payment initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::laravelError($e->getMessage(), statusCode: 422);
        }
    }

    public function webhook(Request $request, string $gateway)
    {
        $payload = $request->all();

        Log::info('Payment webhook received', [
            'gateway' => $gateway,
            'order_id' => $payload['OrderId'] ?? null,
            'payment_id' => $payload['PaymentId'] ?? null,
            'status' => $payload['Status'] ?? null,
        ]);

        try {
            $paymentGateway = $this->paymentManager->resolve($gateway);

            $paymentGateway->handleWebhook($payload);

            return response('OK', 200);
        } catch (PaymentException|\RuntimeException $e) {
            Log::warning('Payment webhook rejected', [
                'gateway' => $gateway,
                'order_id' => $payload['OrderId'] ?? null,
                'payment_id' => $payload['PaymentId'] ?? null,
                'status' => $payload['Status'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response('ERROR', 400);
        } catch (\Throwable $e) {
            Log::error('Payment webhook processing failed', [
                'gateway' => $gateway,
                'order_id' => $payload['OrderId'] ?? null,
                'payment_id' => $payload['PaymentId'] ?? null,
                'status' => $payload['Status'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response('ERROR', 500);
        }
    }

    public function callback(Order $order, Request $request)
    {
        return ApiResponse::success([
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
        ]);
    }
}
