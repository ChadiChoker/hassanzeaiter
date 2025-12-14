<?php

namespace App\Actions\Ad;

use App\Repositories\AdRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetUserAdsAction
{
    public function __construct(
        private readonly AdRepository $adRepository
    ) {
    }

    public function execute(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->adRepository->getUserAds($userId, $perPage);
    }
}
