<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\AttributeValueComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeValueController extends Controller
{
    public function index(Attribute $attribute)
    {
        return $attribute->values()->ordered()->with('componentValues')->get();
    }

    public function store(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'label'         => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50'],
            'hex'           => ['nullable', 'string', 'max:9'],
            'gradient'      => ['nullable', 'string', 'max:1000'],
            'image'         => ['nullable', 'string', 'max:255'],
            'tone'          => ['nullable', 'string', 'max:255'],
            'numeric_value' => ['nullable', 'numeric'],
            'unit'          => ['nullable', 'string', 'max:20'],
            'group_name'    => ['nullable', 'string', 'max:100'],
            'is_blend'      => ['nullable', 'boolean'],
            'sort_order'    => ['nullable', 'integer'],
            'is_active'     => ['nullable', 'boolean'],
            'components'    => ['nullable', 'array'],
            'components.*.component_value_id' => ['required_with:components', 'integer', 'exists:attribute_values,id'],
            'components.*.ratio'              => ['nullable', 'numeric'],
            'components.*.sort_order'         => ['nullable', 'integer'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['label']);
        if (AttributeValue::where('attribute_id', $attribute->id)->where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(4);
        }
        $validated['slug'] = $slug;
        $validated['attribute_id'] = $attribute->id;

        $components = $validated['components'] ?? [];
        unset($validated['components']);

        $value = AttributeValue::create($validated);

        if (! empty($components)) {
            $this->syncComponents($value, $components);
        }

        return response()->json($value->load('componentValues'), 201);
    }

    public function show(Attribute $attribute, AttributeValue $value)
    {
        return $value->load('componentValues');
    }

    public function update(Request $request, Attribute $attribute, AttributeValue $value)
    {
        $validated = $request->validate([
            'label'         => ['sometimes', 'required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50'],
            'hex'           => ['nullable', 'string', 'max:9'],
            'gradient'      => ['nullable', 'string', 'max:1000'],
            'image'         => ['nullable', 'string', 'max:255'],
            'tone'          => ['nullable', 'string', 'max:255'],
            'numeric_value' => ['nullable', 'numeric'],
            'unit'          => ['nullable', 'string', 'max:20'],
            'group_name'    => ['nullable', 'string', 'max:100'],
            'is_blend'      => ['nullable', 'boolean'],
            'sort_order'    => ['nullable', 'integer'],
            'is_active'     => ['nullable', 'boolean'],
            'components'    => ['nullable', 'array'],
            'components.*.component_value_id' => ['required_with:components', 'integer', 'exists:attribute_values,id'],
            'components.*.ratio'              => ['nullable', 'numeric'],
            'components.*.sort_order'         => ['nullable', 'integer'],
        ]);

        $components = $validated['components'] ?? null;
        unset($validated['components']);

        $value->update($validated);

        if ($components !== null) {
            $this->syncComponents($value, $components);
        }

        return response()->json($value->load('componentValues'));
    }

    public function destroy(Attribute $attribute, AttributeValue $value)
    {
        $value->delete();

        return response()->noContent();
    }

    protected function syncComponents(AttributeValue $blendValue, array $components): void
    {
        $blendValue->components()->delete();
        foreach ($components as $index => $comp) {
            AttributeValueComponent::create([
                'blend_value_id'     => $blendValue->id,
                'component_value_id' => $comp['component_value_id'],
                'ratio'              => $comp['ratio'] ?? 1.0,
                'sort_order'         => $comp['sort_order'] ?? ($index + 1),
            ]);
        }
    }
}
