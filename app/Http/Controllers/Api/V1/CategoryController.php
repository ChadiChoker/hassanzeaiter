<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Category\GetAllCategoriesAction;
use App\Actions\Category\GetCategoryWithFieldsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryFieldResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly GetAllCategoriesAction $getAllCategoriesAction,
        private readonly GetCategoryWithFieldsAction $getCategoryWithFieldsAction
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $categories = $this->getAllCategoriesAction->execute();

        return CategoryResource::collection($categories);
    }

    public function fields(int $id): JsonResponse
    {
        $category = $this->getCategoryWithFieldsAction->execute($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $fields = $category->fields->sortBy('order');

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'fields' => CategoryFieldResource::collection($fields),
            'summary' => [
                'total_fields' => $fields->count(),
                'required_fields' => $fields->where('is_required', true)->count(),
                'optional_fields' => $fields->where('is_required', false)->count(),
            ],
        ]);
    }
}
