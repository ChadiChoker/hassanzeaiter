<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\CategoryField;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class StoreAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:draft,active,inactive,sold',
        ];

        $categoryId = $this->input('category_id');

        if ($categoryId) {
            $categoryFields = CategoryField::where('category_id', $categoryId)
                ->orWhereNull('category_id')
                ->get();

            foreach ($categoryFields as $field) {
                $fieldRules = $this->buildFieldValidationRules($field);
                if ($fieldRules) {
                    $rules["fields.{$field->field_key}"] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    private function buildFieldValidationRules(CategoryField $field): array|string|null
    {
        $rules = [];

        if ($field->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($field->field_type) {
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'select':
            case 'radio':
                $options = $field->options()->pluck('option_value')->toArray();
                if (!empty($options)) {
                    $rules[] = 'in:' . implode(',', $options);
                }
                break;
            case 'checkbox':
                $rules[] = 'boolean';
                break;
            case 'text':
            default:
                $rules[] = 'string';
                break;
        }

        if ($field->validation_rules) {
            $customRules = explode('|', $field->validation_rules);
            foreach ($customRules as $customRule) {
                if (!in_array($customRule, $rules)) {
                    $rules[] = $customRule;
                }
            }
        }

        return !empty($rules) ? implode('|', $rules) : null;
    }

    public function messages(): array
    {
        $messages = [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'title.required' => 'The ad title is required.',
            'description.required' => 'The ad description is required.',
            'price.numeric' => 'The price must be a valid number.',
        ];

        $categoryId = $this->input('category_id');
        if ($categoryId) {
            $categoryFields = CategoryField::where('category_id', $categoryId)
                ->orWhereNull('category_id')
                ->get();

            foreach ($categoryFields as $field) {
                $messages["fields.{$field->field_key}.required"] = "The {$field->field_label} field is required.";
            }
        }

        return $messages;
    }

    protected function failedValidation(Validator $validator): void
    {
        $categoryId = $this->input('category_id');
        $errors = $validator->errors()->toArray();

        $hasFieldErrors = collect($errors)->keys()->contains(function ($key) {
            return str_starts_with($key, 'fields.');
        });

        $response = [
            'message' => 'The given data was invalid.',
            'errors' => $errors,
        ];

        if ($hasFieldErrors && $categoryId && Category::where('id', $categoryId)->exists()) {
            $category = Category::find($categoryId);
            $fields = CategoryField::where('category_id', $categoryId)
                ->with('options')
                ->orderBy('order')
                ->get()
                ->map(function ($field) {
                    $fieldInfo = [
                        'field_key' => $field->field_key,
                        'field_label' => $field->field_label,
                        'field_type' => $field->field_type,
                        'is_required' => $field->is_required,
                    ];

                    if (in_array($field->field_type, ['select', 'radio']) && $field->options->isNotEmpty()) {
                        $fieldInfo['valid_options'] = $field->options->pluck('option_value')->toArray();
                    }

                    return $fieldInfo;
                });

            $response['help'] = [
                'message' => 'Check the available fields for this category',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'available_fields' => $fields,
                'endpoint' => url("/api/v1/categories/{$categoryId}/fields"),
            ];
        }

        throw new HttpResponseException(
            response()->json($response, 422)
        );
    }
}
