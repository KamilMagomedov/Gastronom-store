<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Concerns\WithTranslateMessage;
use App\Http\Requests\Api\V1\CustomerVerifyOtpRequest;
use App\Http\Resources\ApiResource;
use App\Repositories\CustomerRepository;
use App\Services\OTPService;
use App\Support\ApiResponse;

class VerifyOtpController
{
    use WithTranslateMessage;

    public function __invoke(
        CustomerVerifyOtpRequest $request,
        CustomerRepository $customerRepository,
        OTPService $OTPService
    ): ApiResource {
        $email = $request->validated('email');
        $code = $request->validated('code');

        $customer = $customerRepository->findByEmail($email);

        if (! $customer) {
            return ApiResponse::notFound([
                'message' => 'Customer not found',
            ]);
        }

        $result = $OTPService->validate($email, $code);

        if ($result->status) {
            return ApiResponse::success([
                'message' => 'Email verified successfully',
                'token' => $OTPService->generate($customer->email),
                'customer' => [
                    'name' => $customer->name,
                    'email' => $customer->email,
                ],
            ]);
        }

        return ApiResponse::unprocessableEntity($this->getTranslatedOtpMessage($result->message));
    }
}
