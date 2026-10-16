<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldDefinition;
use App\Models\FieldValue;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductFieldController extends Controller
{
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'fields' => ['required'],
        ]);

        $fields = $validated['fields'];

        DB::transaction(function () use ($product, $fields) {
            if (is_array($fields) && isset($fields[0]) && is_array($fields[0])) {
                // Format: [{ field_definition_id: 1, value: "..." }]
                foreach ($fields as $item) {
                    $defId = $item['field_definition_id'] ?? null;
                    if (! $defId) {
                        continue;
                    }
                    $def = FieldDefinition::find($defId);
                    if (! $def) {
                        continue;
                    }

                    $fieldVal = FieldValue::firstOrNew([
                        'field_definition_id' => $def->id,
                        'owner_type'          => Product::class,
                        'owner_id'            => $product->id,
                    ]);
                    $fieldVal->setTypedValue($item['value'] ?? null);
                    $fieldVal->save();
                }
            } elseif (is_array($fields)) {
                // Format: { "e_number": "E102", "1": "value" }
                foreach ($fields as $key => $val) {
                    $def = is_numeric($key)
                        ? FieldDefinition::find($key)
                        : FieldDefinition::where('code', $key)->first();

                    if (! $def) {
                        continue;
                    }

                    $fieldVal = FieldValue::firstOrNew([
                        'field_definition_id' => $def->id,
                        'owner_type'          => Product::class,
                        'owner_id'            => $product->id,
                    ]);
                    $fieldVal->setTypedValue($val);
                    $fieldVal->save();
                }
            }
        });

        return response()->json([
            'message' => 'Product fields updated successfully.',
            'fields'  => $product->fresh('fieldValues.fieldDefinition')->fieldValues->map(fn ($fv) => [
                'code'  => $fv->fieldDefinition?->code,
                'label' => $fv->fieldDefinition?->label,
                'type'  => $fv->fieldDefinition?->type,
                'value' => $fv->getTypedValue(),
                'unit'  => $fv->fieldDefinition?->unit,
            ]),
        ]);
    }
}
