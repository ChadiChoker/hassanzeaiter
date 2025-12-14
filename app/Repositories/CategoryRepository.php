<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function getAllCategories(): Collection
    {
        return Category::select('id', 'name', 'slug', 'description', 'parent_id')
            ->orderBy('order')
            ->get();
    }

    public function findWithFields(int $id): ?Category
    {
        return Category::with(['fields.options'])
            ->find($id);
    }
}
