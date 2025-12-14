<?php

namespace App\Actions\Ad;

use App\Models\Ad;
use App\Repositories\AdRepository;
use Illuminate\Support\Facades\DB;

class CreateAdAction
{
    public function __construct(
        private readonly AdRepository $adRepository,
        private readonly StoreAdFieldValuesAction $storeFieldValuesAction
    ) {
    }

    public function execute(array $data, int $userId): Ad
    {
        return DB::transaction(function () use ($data, $userId) {
            $ad = Ad::create([
                'user_id' => $userId,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'price' => $data['price'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            if (isset($data['fields']) && is_array($data['fields'])) {
                $this->storeFieldValuesAction->execute($ad, $data['fields']);
            }

            return $this->adRepository->findWithRelations($ad->id);
        });
    }
}
