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

    public function webhook(Request $request)
    {
        $payload = $request->all();

        Log::info('Payment webhook received', ['payload' => $payload]);

        $gatewayName = $payload['gateway'] ?? $request->header('X-Gateway', '');

        try {
            $gateway = $this->paymentManager->resolve($gatewayName);

            $gateway->handleWebhook($payload);

            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('Payment webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response('OK', 200);
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
