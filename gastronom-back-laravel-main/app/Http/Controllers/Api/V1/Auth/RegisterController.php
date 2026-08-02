<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Events\CustomerCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerRegisterRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\ApiResource;
use App\Services\CustomerService;
use App\Services\OTPService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function __invoke(
        CustomerRegisterRequest $request,
        CustomerService $customerService,
        OTPService $OTPService
    ): ApiResource {
        try {
            return DB::transaction(function () use ($request, $customerService, $OTPService) {
                $customer = $customerService->create(
                    ...$request->safe(['name', 'email', 'password'])
                );

                $otpObject = $OTPService->generate($request->validated('email'));

                CustomerCreated::dispatch($customer, $otpObject->token);

                return ApiResponse::created([
                    'token' => $customer->createToken('register')->plainTextToken,
                    'customer' => CustomerResource::make($customer)->toArray($request),
                ]);
            });
        } catch (\Throwable $e) {
            \Log::error('Customer registration failed', [
                'exception' => $e,
                'email' => $request->input('email'),
            ]);

            return ApiResponse::laravelError('Failed to send OTP email.');
        }
    }
}
