<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index()
    {
        return Attribute::ordered()->withCount('values')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'       => ['required', 'string', 'max:50', 'unique:attributes,code'],
            'name'       => ['required', 'string', 'max:100'],
            'input_type' => ['required', Rule::in(['swatch', 'text', 'image', 'pack'])],
            'unit'       => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $attribute = Attribute::create($validated);

        return response()->json($attribute, 201);
    }

    public function show(Attribute $attribute)
    {
        return $attribute->load(['values' => fn ($q) => $q->ordered()->with('componentValues')]);
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'code'       => ['sometimes', 'required', 'string', 'max:50', Rule::unique('attributes', 'code')->ignore($attribute->id)],
            'name'       => ['sometimes', 'required', 'string', 'max:100'],
            'input_type' => ['sometimes', 'required', Rule::in(['swatch', 'text', 'image', 'pack'])],
            'unit'       => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $attribute->update($validated);

        return response()->json($attribute);
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return response()->noContent();
    }
}
