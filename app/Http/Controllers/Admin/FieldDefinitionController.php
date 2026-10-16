<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldAssignment;
use App\Models\FieldDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FieldDefinitionController extends Controller
{
    public function index()
    {
        return FieldDefinition::ordered()
            ->with(['assignments.category:id,name,slug', 'assignments.product:id,name,slug'])
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'          => ['required', 'string', 'max:50', 'unique:field_definitions,code'],
            'label'         => ['required', 'string', 'max:100'],
            'type'          => ['required', Rule::in(['text', 'textarea', 'number', 'select', 'multiselect', 'boolean', 'url', 'file', 'color'])],
            'options'       => ['nullable', 'array'],
            'unit'          => ['nullable', 'string', 'max:20'],
            'group_name'    => ['nullable', 'string', 'max:100'],
            'applies_to'    => ['nullable', Rule::in(['product', 'variant'])],
            'is_filterable' => ['nullable', 'boolean'],
            'is_public'     => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'sort_order'    => ['nullable', 'integer'],
        ]);

        $field = FieldDefinition::create($validated);

        return response()->json($field, 201);
    }

    public function show(FieldDefinition $field)
    {
        return $field->load(['assignments.category:id,name,slug', 'assignments.product:id,name,slug']);
    }

    public function update(Request $request, FieldDefinition $field)
    {
        $validated = $request->validate([
            'code'          => ['sometimes', 'required', 'string', 'max:50', Rule::unique('field_definitions', 'code')->ignore($field->id)],
            'label'         => ['sometimes', 'required', 'string', 'max:100'],
            'type'          => ['sometimes', 'required', Rule::in(['text', 'textarea', 'number', 'select', 'multiselect', 'boolean', 'url', 'file', 'color'])],
            'options'       => ['nullable', 'array'],
            'unit'          => ['nullable', 'string', 'max:20'],
            'group_name'    => ['nullable', 'string', 'max:100'],
            'applies_to'    => ['nullable', Rule::in(['product', 'variant'])],
            'is_filterable' => ['nullable', 'boolean'],
            'is_public'     => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'sort_order'    => ['nullable', 'integer'],
        ]);

        $field->update($validated);

        return response()->json($field);
    }

    public function destroy(FieldDefinition $field)
    {
        $field->delete();

        return response()->noContent();
    }

    public function assignments(Request $request, FieldDefinition $field)
    {
        $validated = $request->validate([
            'assignments'                 => ['required', 'array'],
            'assignments.*.category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'assignments.*.product_id'    => ['nullable', 'integer', 'exists:products,id'],
            'assignments.*.is_required'   => ['nullable', 'boolean'],
            'assignments.*.default_value' => ['nullable', 'string'],
            'assignments.*.sort_order'    => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($field, $validated) {
            $field->assignments()->delete();
            foreach ($validated['assignments'] as $idx => $assign) {
                if (empty($assign['category_id']) && empty($assign['product_id'])) {
                    continue;
                }
                FieldAssignment::create([
                    'field_definition_id' => $field->id,
                    'category_id'         => $assign['category_id'] ?? null,
                    'product_id'          => $assign['product_id'] ?? null,
                    'is_required'         => $assign['is_required'] ?? false,
                    'default_value'       => $assign['default_value'] ?? null,
                    'sort_order'          => $assign['sort_order'] ?? ($idx + 1),
                ]);
            }
        });

        return response()->json([
            'message'     => 'Assignments updated successfully.',
            'assignments' => $field->fresh('assignments.category:id,name,slug', 'assignments.product:id,name,slug')->assignments,
        ]);
    }
}
