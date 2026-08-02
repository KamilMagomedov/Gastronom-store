<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Events\ResendOTPCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ResendOTPRequest;
use App\Repositories\CustomerRepository;
use App\Services\OTPService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResendOTPController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @throws \Throwable
     */
    public function __invoke(
        ResendOTPRequest $request,
        CustomerRepository $customerRepository,
        OTPService $OTPService
    ) {
        $customer = $customerRepository->findByEmail($request->validated('email'));

        if (! $customer) {
            return ApiResponse::notFound([
                'message' => 'Customer not found',
            ]);
        }

        try {
            return DB::transaction(function () use ($OTPService, $customer) {
                $OTPService->deleteOtps($customer->email);

                $otpObject = $OTPService->generate($customer->email);

                ResendOTPCreated::dispatch($customer, $otpObject->token);

                return ApiResponse::success([]);
            });
        } catch (\Throwable $e) {
            Log::error('Error resend OTP', [
                'error' => $e->getMessage(),
                'customer' => $customer->id,
            ]);

            return ApiResponse::serverError();
        }
    }
}
