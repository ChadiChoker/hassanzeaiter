<?php

namespace App\Repositories;

use App\Models\Ad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdRepository
{
    /**
     * Get paginated ads for a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserAds(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Ad::where('user_id', $userId)
            ->with(['category', 'user', 'fieldValues.categoryField.options'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find an ad by ID with all relationships loaded.
     *
     * @param int $id
     * @return Ad|null
     */
    public function findWithRelations(int $id): ?Ad
    {
        return Ad::with(['category', 'user', 'fieldValues.categoryField.options'])
            ->find($id);
    }

    /**
     * Get all ads with pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllAds(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Ad::with(['category', 'user', 'fieldValues.categoryField.options'])
            ->latest();

        // Apply filters
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get ads by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAdsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Ad::where('category_id', $categoryId)
            ->with(['category', 'user', 'fieldValues.categoryField.options'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Count ads by user.
     *
     * @param int $userId
     * @return int
     */
    public function countUserAds(int $userId): int
    {
        return Ad::where('user_id', $userId)->count();
    }

    /**
     * Count ads by category.
     *
     * @param int $categoryId
     * @return int
     */
    public function countAdsByCategory(int $categoryId): int
    {
        return Ad::where('category_id', $categoryId)->count();
    }
}
