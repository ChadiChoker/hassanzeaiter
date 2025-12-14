<?php

namespace App\Actions\Ad;

use App\Repositories\AdRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetAllAdsAction
{
    public function __construct(
        private readonly AdRepository $adRepository
    ) {
    }

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->adRepository->getAllAds($filters, $perPage);
    }
}
