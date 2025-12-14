<?php

namespace App\Actions\Ad;

use App\Models\Ad;
use App\Models\AdFieldValue;
use App\Models\CategoryField;

class StoreAdFieldValuesAction
{
    public function execute(Ad $ad, array $fields): void
    {
        $categoryFields = CategoryField::where('category_id', $ad->category_id)
            ->orWhereNull('category_id')
            ->get()
            ->keyBy('field_key');

        $fieldValues = [];

        foreach ($fields as $fieldKey => $fieldValue) {
            $categoryField = $categoryFields->get($fieldKey);

            if ($categoryField && $fieldValue !== null) {
                $fieldValues[] = [
                    'ad_id' => $ad->id,
                    'category_field_id' => $categoryField->id,
                    'value' => is_array($fieldValue) ? json_encode($fieldValue) : (string) $fieldValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($fieldValues)) {
            AdFieldValue::insert($fieldValues);
        }
    }
}
