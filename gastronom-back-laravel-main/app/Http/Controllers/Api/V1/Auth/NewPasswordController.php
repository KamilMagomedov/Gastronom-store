<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NewPasswordRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\ApiResource;
use App\Repositories\CustomerRepository;
use App\Services\OTPService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;

class NewPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(NewPasswordRequest $request, CustomerRepository $customerRepository, OTPService $OTPService): ApiResource
    {
        return DB::transaction(function () use ($request, $customerRepository, $OTPService) {
            $customer = $customerRepository->findByEmail($request->validated('email'));

            if (! $customer) {
                return ApiResponse::unprocessableEntity('Invalid credentials');
            }

            $result = $OTPService->validate(
                identifier: $request->validated('email'),
                token: $request->validated('token')
            );

            if (! $result->status) {
                return ApiResponse::unprocessableEntity('Invalid or expired OTP');
            }

            $customer->update(['password' => $request->validated('password')]);

            return ApiResponse::success([
                'token' => $customer->createToken('auth_token')->plainTextToken,
                'customer' => CustomerResource::make($customer)->toArray($request),
            ]);
        });
    }
}
