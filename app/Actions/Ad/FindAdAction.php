<?php

namespace App\Actions\Ad;

use App\Models\Ad;
use App\Repositories\AdRepository;

class FindAdAction
{
    public function __construct(
        private readonly AdRepository $adRepository
    ) {
    }

    public function execute(int $id): ?Ad
    {
        return $this->adRepository->findWithRelations($id);
    }
}
