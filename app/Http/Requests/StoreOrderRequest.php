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
            'product_id'         => ['nullable', 'required_without:items', 'integer', 'exists:products,id,deleted_at,NULL'],
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'max:255'],
            'phone'              => ['nullable', 'string', 'regex:/^\+\d{1,4} \d{10}$/'],
            'quantity'           => ['nullable', 'required_without:items', 'string', 'max:100'],
            'address'            => ['nullable', 'string', 'max:2000'],
            'message'            => ['nullable', 'string', 'max:5000'],
            'website'            => ['nullable', 'max:0'],
            'items'              => ['nullable', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id,deleted_at,NULL'],
            'items.*.productId'  => ['nullable', 'integer', 'exists:products,id,deleted_at,NULL'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id,deleted_at,NULL'],
            'items.*.variantId'  => ['nullable', 'integer', 'exists:product_variants,id,deleted_at,NULL'],
            'items.*.bundle_id'  => ['nullable', 'integer', 'exists:bundles,id'],
            'items.*.bundleId'   => ['nullable', 'integer', 'exists:bundles,id'],
            'items.*.quantity'   => ['nullable'],
            'items.*.note'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function orderData(): array
    {
        return $this->safe()->except('website');
    }
}
