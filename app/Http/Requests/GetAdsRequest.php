<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'status' => 'sometimes|in:active,inactive,pending',
        ];
    }
}
