<?php

namespace App\Actions\Category;

use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class GetAllCategoriesAction
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function execute(): Collection
    {
        return $this->categoryRepository->getAllCategories();
    }
}
