<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function orderData(): array
    {
        return $this->safe()->except('website');
    }
}
