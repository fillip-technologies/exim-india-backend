<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FieldValue extends Model
{
    protected $fillable = [
        'field_definition_id',
        'owner_type',
        'owner_id',
        'value_text',
        'value_number',
        'value_boolean',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_number'  => 'decimal:4',
            'value_boolean' => 'boolean',
            'value_json'    => 'array',
        ];
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(FieldDefinition::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypedValue(): mixed
    {
        $type = $this->fieldDefinition?->type ?? FieldDefinition::TYPE_TEXT;

        return match ($type) {
            FieldDefinition::TYPE_NUMBER                      => $this->value_number !== null ? (float) $this->value_number : null,
            FieldDefinition::TYPE_BOOLEAN                     => $this->value_boolean,
            FieldDefinition::TYPE_MULTISELECT,
            FieldDefinition::TYPE_FILE                        => $this->value_json,
            default                                           => $this->value_text,
        };
    }

    public function setTypedValue(mixed $value): self
    {
        $type = $this->fieldDefinition?->type ?? FieldDefinition::TYPE_TEXT;

        $this->value_text    = null;
        $this->value_number  = null;
        $this->value_boolean = null;
        $this->value_json    = null;

        match ($type) {
            FieldDefinition::TYPE_NUMBER => $this->value_number = is_numeric($value) ? $value : null,
            FieldDefinition::TYPE_BOOLEAN => $this->value_boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            FieldDefinition::TYPE_MULTISELECT,
            FieldDefinition::TYPE_FILE => $this->value_json = is_array($value) ? $value : json_decode((string) $value, true),
            default => $this->value_text = is_scalar($value) ? (string) $value : json_encode($value),
        };

        return $this;
    }
}
