<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Repositories\CategoryRepository;

class GetCategoryWithFieldsAction
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function execute(int $id): ?Category
    {
        return $this->categoryRepository->findWithFields($id);
    }
}
