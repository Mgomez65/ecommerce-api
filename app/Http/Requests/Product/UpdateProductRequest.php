<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string'
            ],
            'price' => [
                'required',
                'numeric',
                'min:0'
            ],
            'stock' => [
                'required',
                'integer',
                'min:0'
            ],
            'stock_minimo' => [
                'required',
                'integer',
                'min:0'
            ],
            'category_id' => [
                'required',
                'exists:categories,id'
            ],
            'active' => [
                'required',
                'boolean'
            ]
        ];
    }
}
