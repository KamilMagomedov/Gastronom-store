<?php

namespace App\Http\Requests\Api\V1;

use App\DTO\Cart\GetCartData;
use Illuminate\Foundation\Http\FormRequest;

class GetCartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_id' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'session_id.string' => 'Session ID must be a string.',
            'session_id.max' => 'Session ID may not be greater than 50 characters.',
        ];
    }

    /**
     * Get the DTO representation of the request data.
     */
    public function toDto(): GetCartData
    {
        return new GetCartData(
            sessionId: $this->validated('session_id')
        );
    }
}
