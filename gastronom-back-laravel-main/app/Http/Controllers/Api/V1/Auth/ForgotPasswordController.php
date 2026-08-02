<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Events\PasswordResetOtpGenerated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Resources\ApiResource;
use App\Repositories\CustomerRepository;
use App\Services\OTPService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    /**
     * Handle the forgot password request.
     *
     * @throws \Throwable
     */
    public function __invoke(
        ForgotPasswordRequest $request,
        CustomerRepository $customerRepository,
        OTPService $OTPService
    ): ApiResource {
        return DB::transaction(function () use ($request, $customerRepository, $OTPService) {
            $email = $request->validated('email');

            $customer = $customerRepository->findByEmail($email);

            if (! $customer) {
                return ApiResponse::notFound([
                    'message' => 'Customer not found',
                ]);
            }

            $otpObject = $OTPService->generate($email);

            PasswordResetOtpGenerated::dispatch($customer, $otpObject->token);

            return ApiResponse::success();
        });
    }
}
