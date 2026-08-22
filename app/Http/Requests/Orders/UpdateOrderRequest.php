<?php

namespace App\Http\Requests\Orders;

use App\Models\Orders;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    Orders::STATUS_PENDING,
                    Orders::STATUS_CONFIRMED,
                    Orders::STATUS_CANCELLED,
                    Orders::STATUS_COMPLETED,
                ]),
            ],
        ];
    }
}
