<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryFieldOption;
use App\Services\OlxApiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesAndFieldsSeeder extends Seeder
{
    private OlxApiService $olxApiService;

    public function __construct()
    {
        $this->olxApiService = new OlxApiService();
    }


    public function run(): void
    {
        $this->command->info('Fetching categories from OLX API...');

        try {
            $categoriesData = $this->olxApiService->fetchCategories();

            $this->command->info('Found ' . count($categoriesData) . ' categories. Seeding...');

            DB::transaction(function () use ($categoriesData) {
                $allCategories = [];

                foreach ($categoriesData as $categoryData) {
                    $allCategories[] = $categoryData;

                    if (isset($categoryData['children']) && is_array($categoryData['children'])) {
                        foreach ($categoryData['children'] as $childData) {
                            $allCategories[] = $childData;
                        }
                    }
                }

                $this->command->info('Total categories (including subcategories): ' . count($allCategories));

                foreach ($allCategories as $categoryData) {
                    $this->seedCategory($categoryData);
                }

                $this->updateParentRelationships($allCategories);
            });

            $this->command->info('Categories and fields seeded successfully!');
        } catch (\Exception $e) {
            $this->command->error('Error seeding categories: ' . $e->getMessage());
            throw $e;
        }
    }
    private function seedCategory(array $categoryData): void
    {
        $category = Category::updateOrCreate(
            ['external_id' => (string) $categoryData['externalID']],
            [
                'olx_internal_id' => (int) $categoryData['id'],
                'name' => $categoryData['name'] ?? 'Unknown',
                'slug' => $categoryData['slug'] ?? Str::slug($categoryData['name'] ?? 'unknown'),
                'description' => $categoryData['description'] ?? null,
                'icon' => $categoryData['icon'] ?? null,
                'parent_id' => null, 
                'order' => $categoryData['displayPriority'] ?? $categoryData['order'] ?? 0,
                'is_active' => $categoryData['isActive'] ?? true,
            ]
        );

        $this->command->info("  - Seeded category: {$category->name} (Internal ID: {$category->olx_internal_id}, External ID: {$category->external_id})");

        $this->seedCategoryFields($category);
    }


    private function seedCategoryFields(Category $category): void
    {
        try {
            $fieldsData = $this->olxApiService->fetchCategoryFields($category->external_id);

            $fields = $this->extractFieldsFromResponse($fieldsData, (string) $category->olx_internal_id);

            if (empty($fields)) {
                $this->command->info("    No fields found for category: {$category->name}");
                return;
            }

            $this->command->info("    Found " . count($fields) . " fields for {$category->name}");

            foreach ($fields as $fieldData) {
                try {
                    $this->seedCategoryField($category, $fieldData);
                } catch (\Exception $e) {
                    $this->command->error("      Error seeding field: " . $e->getMessage());
                    $this->command->error("      Field data: " . json_encode($fieldData));
                }
            }
        } catch (\Exception $e) {
            $this->command->warn("    Could not fetch fields for category {$category->name}: " . $e->getMessage());
        }
    }

    private function updateParentRelationships(array $categoriesData): void
    {
        $this->command->info('Updating parent-child relationships...');

        foreach ($categoriesData as $categoryData) {
            if (!isset($categoryData['parentID'])) {
                continue;
            }

            $category = Category::where('external_id', (string) $categoryData['externalID'])->first();
            if (!$category) {
                continue;
            }

            $parent = Category::where('olx_internal_id', (int) $categoryData['parentID'])->first();
            if (!$parent) {
                $this->command->warn("  Parent not found for {$category->name} (looking for internal ID: {$categoryData['parentID']})");
                continue;
            }

            $category->update(['parent_id' => $parent->id]);
            $this->command->info("  ✓ Linked {$category->name} → {$parent->name}");
        }
    }

    private function extractFieldsFromResponse(array $response, string $categoryId): array
    {

        $fields = [];

        if (isset($response[$categoryId]['flatFields'])) {
            $fields = $response[$categoryId]['flatFields'];
        }
        elseif (isset($response['data'][$categoryId]['flatFields'])) {
            $fields = $response['data'][$categoryId]['flatFields'];
        }
        elseif (isset($response[$categoryId])) {
            $fields = is_array($response[$categoryId]) ? $response[$categoryId] : [];
        }

        return $fields;
    }

    private function seedCategoryField(Category $category, array $fieldData): void
    {
        $fieldKey = $fieldData['attribute'] ?? $fieldData['key'] ?? $fieldData['name'] ?? 'unknown';
        $fieldType = $this->normalizeFieldType($fieldData['valueType'] ?? $fieldData['filterType'] ?? $fieldData['type'] ?? 'text');

        $field = CategoryField::updateOrCreate(
            [
                'category_id' => $category->id,
                'external_id' => (string) ($fieldData['id'] ?? $category->external_id . '_' . $fieldKey),
            ],
            [
                'field_key' => $fieldKey,
                'field_label' => $fieldData['name'] ?? $fieldData['label'] ?? $fieldKey,
                'field_type' => $fieldType,
                'is_required' => $fieldData['isMandatory'] ?? $fieldData['required'] ?? false,
                'is_searchable' => in_array('searchable', $fieldData['roles'] ?? []) || ($fieldData['searchable'] ?? false),
                'validation_rules' => $this->buildValidationRules($fieldData),
                'placeholder' => $fieldData['placeholder'] ?? null,
                'help_text' => $fieldData['help'] ?? null,
                'order' => $fieldData['displayPriority'] ?? $fieldData['order'] ?? 0,
                'metadata' => json_encode($fieldData),
            ]
        );

        $this->command->info("      ✓ Seeded field: {$field->field_label} ({$field->field_type})");

        if (in_array($fieldType, ['select', 'radio', 'checkbox']) && isset($fieldData['choices'])) {
            $optionsCount = count($fieldData['choices']);
            $this->seedFieldOptions($field, $fieldData['choices']);
            $this->command->info("        ✓ Seeded {$optionsCount} options for {$field->field_label}");
        }
    }

    private function normalizeFieldType(string $type): string
    {
        $typeMap = [
            'input' => 'text',
            'textarea' => 'text',
            'select' => 'select',
            'radio' => 'radio',
            'checkbox' => 'checkbox',
            'number' => 'number',
            'price' => 'number',
            'date' => 'date',
            'float' => 'number',
            'integer' => 'number',
            'string' => 'text',
            'enum' => 'select',
            'boolean' => 'checkbox',
            'range' => 'number',
            'single_choice' => 'select',
            'multiple_choice' => 'checkbox',
        ];

        return $typeMap[strtolower($type)] ?? 'text';
    }

    private function buildValidationRules(array $fieldData): ?string
    {
        $rules = [];

        if ($fieldData['isMandatory'] ?? $fieldData['required'] ?? false) {
            $rules[] = 'required';
        }

        $type = $this->normalizeFieldType($fieldData['valueType'] ?? $fieldData['type'] ?? 'text');

        if ($type === 'number') {
            $rules[] = 'numeric';
        }

        if ($type === 'date') {
            $rules[] = 'date';
        }

        if (isset($fieldData['minValue'])) {
            $rules[] = 'min:' . $fieldData['minValue'];
        } elseif (isset($fieldData['min'])) {
            $rules[] = 'min:' . $fieldData['min'];
        }

        if (isset($fieldData['maxValue'])) {
            $rules[] = 'max:' . $fieldData['maxValue'];
        } elseif (isset($fieldData['max'])) {
            $rules[] = 'max:' . $fieldData['max'];
        }

        if (isset($fieldData['minLength'])) {
            $rules[] = 'min:' . $fieldData['minLength'];
        }

        if (isset($fieldData['maxLength'])) {
            $rules[] = 'max:' . $fieldData['maxLength'];
        }

        return !empty($rules) ? implode('|', $rules) : null;
    }

    private function seedFieldOptions(CategoryField $field, array $choices): void
    {
        foreach ($choices as $index => $choice) {
            $optionKey = $choice['slug'] ?? $choice['key'] ?? $choice['value'] ?? (string) $index;
            $optionValue = $choice['value'] ?? $choice['key'] ?? (string) $index;
            $optionLabel = $choice['label'] ?? $choice['name'] ?? $optionValue;

            CategoryFieldOption::updateOrCreate(
                [
                    'category_field_id' => $field->id,
                    'external_id' => (string) ($choice['id'] ?? $field->external_id . '_option_' . $index),
                ],
                [
                    'option_key' => $optionKey,
                    'option_label' => $optionLabel,
                    'option_value' => $optionValue,
                    'order' => $choice['displayPriority'] ?? $choice['order'] ?? $index,
                    'is_default' => $choice['default'] ?? false,
                    'metadata' => json_encode($choice),
                ]
            );
        }
    }
}

