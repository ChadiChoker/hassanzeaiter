<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dynamicFields = [];
        foreach ($this->fieldValues as $fieldValue) {
            $field = $fieldValue->categoryField;

            $fieldData = [
                'label' => $field->field_label,
                'value' => $fieldValue->value,
                'type' => $field->field_type,
            ];

            if (in_array($field->field_type, ['select', 'radio'])) {
                $option = $field->options()
                    ->where('option_value', $fieldValue->value)
                    ->first();

                if ($option) {
                    $fieldData['value_label'] = $option->option_label;
                }
            }

            $dynamicFields[$field->field_key] = $fieldData;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => new UserResource($this->whenLoaded('user')),
            'dynamic_fields' => $dynamicFields,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
