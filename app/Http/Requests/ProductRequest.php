<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'category_id' => [$required, 'integer', 'exists:categories,id'],
            'slug' => [$required, 'string', 'max:255', 'alpha_dash', $this->uniqueSlug()],
            'name' => [$required, 'string', 'max:255'],
            'group_key' => ['nullable', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color_hex' => ['nullable', 'string', 'max:9'],
            'image' => ['nullable', 'string', 'max:255'],
            'moq' => ['nullable', 'string', 'max:255'],
            'supply_ability' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'string', 'max:255'],
            'cas_no' => ['nullable', 'string', 'max:255'],
            'other_names' => ['nullable', 'string'],
            'mf' => ['nullable', 'string', 'max:255'],
            'einecs_no' => ['nullable', 'string', 'max:255'],
            'fema_no' => ['nullable', 'string', 'max:255'],
            'place_of_origin' => ['nullable', 'string', 'max:255'],
            'types' => ['nullable', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'color_desc' => ['nullable', 'string', 'max:255'],
            'application_summary' => ['nullable', 'string'],
            'purity' => ['nullable', 'string', 'max:255'],
            'shelf_life' => ['nullable', 'string', 'max:255'],
            'packaging_details' => ['nullable', 'string'],
            'delivery_detail' => ['nullable', 'string', 'max:255'],
            'storage' => ['nullable', 'string'],
            'analysis' => ['nullable', 'array'],
            'analysis.*.characteristic' => ['required_with:analysis', 'string'],
            'analysis.*.requirement' => ['required_with:analysis', 'string'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.label' => ['required_with:attributes', 'string', 'max:255'],
            'attributes.*.value' => ['required_with:attributes', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** Slug must be unique within the product's category. */
    private function uniqueSlug()
    {
        $product = $this->route('product');
        $categoryId = $this->input('category_id', $product?->category_id);

        return Rule::unique('products', 'slug')
            ->where('category_id', $categoryId)
            ->ignore($product?->id);
    }
}
