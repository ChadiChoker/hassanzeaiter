<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fieldData = [
            'field_key' => $this->field_key,
            'field_label' => $this->field_label,
            'field_type' => $this->field_type,
            'is_required' => $this->is_required,
            'is_searchable' => $this->is_searchable,
            'placeholder' => $this->placeholder,
            'help_text' => $this->help_text,
            'validation_rules' => $this->validation_rules,
            'order' => $this->order,
        ];

        if (in_array($this->field_type, ['select', 'radio']) && $this->relationLoaded('options')) {
            $fieldData['options'] = $this->options->map(function ($option) {
                return [
                    'value' => $option->option_value,
                    'label' => $option->option_label,
                    'is_default' => $option->is_default,
                ];
            });
        }

        return $fieldData;
    }
}
