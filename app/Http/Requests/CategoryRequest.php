<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'slug' => [$required, 'string', 'max:255', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($this->route('category'))],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'analysis_specs' => ['nullable', 'array'],
            'analysis_specs.*.characteristic' => ['required_with:analysis_specs', 'string'],
            'analysis_specs.*.requirement' => ['required_with:analysis_specs', 'string'],
            'sections' => ['nullable', 'array'],
            'sections.*.key' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.title' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.items' => ['nullable', 'array'],
            'sections.*.items.*.title' => ['required', 'string', 'max:255'],
            'sections.*.items.*.subtitle' => ['nullable', 'string', 'max:255'],
            'sections.*.items.*.color_hex' => ['nullable', 'string', 'max:9'],
            'sections.*.items.*.attributes' => ['nullable', 'array'],
            'sections.*.items.*.attributes.*.label' => ['required', 'string', 'max:255'],
            'sections.*.items.*.attributes.*.value' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
