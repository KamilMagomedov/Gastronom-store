<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerUpdateRequest;
use App\Http\Resources\Api\V1\CustomerProfileResource;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    /**
     * Display the authenticated customer's profile.
     */
    public function show(Request $request)
    {
        return ApiResponse::success(CustomerProfileResource::make($request->user())->toArray($request));
    }

    /**
     * Update the authenticated customer's profile.
     */
    public function update(CustomerUpdateRequest $request)
    {
        $customer = $request->user();

        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $customer->update($validated);

        return ApiResponse::success(CustomerProfileResource::make($customer->fresh())->toArray($request));
    }
}
