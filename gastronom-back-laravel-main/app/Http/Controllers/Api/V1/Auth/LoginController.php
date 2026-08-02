<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\ApiResource;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, CustomerRepository $customerRepository): ApiResource
    {
        /**
         * @var Customer $customer
         */
        $customer = $customerRepository->findByEmail($request->validated('email'));

        if (! $customer || ! Hash::check($request->validated('password'), $customer->password)) {
            return ApiResponse::unprocessableEntity('Invalid credentials');
        }

        return ApiResponse::success([
            'token' => $customer->createToken('auth_token')->plainTextToken,
            'customer' => CustomerResource::make($customer)->toArray($request),
        ]);
    }
}
