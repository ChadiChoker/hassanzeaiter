<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ad\CreateAdAction;
use App\Actions\Ad\FindAdAction;
use App\Actions\Ad\GetAllAdsAction;
use App\Actions\Ad\GetUserAdsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetAdsRequest;
use App\Http\Requests\StoreAdRequest;
use App\Http\Resources\AdResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class AdController extends Controller
{
    public function __construct(
        private readonly CreateAdAction $createAdAction,
        private readonly GetUserAdsAction $getUserAdsAction,
        private readonly GetAllAdsAction $getAllAdsAction,
        private readonly FindAdAction $findAdAction
    ) {
    }

    public function index(GetAdsRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $filters = $request->only(['category_id', 'status', 'search']);

        $ads = $this->getAllAdsAction->execute($filters, $perPage);

        return AdResource::collection($ads);
    }

    public function store(StoreAdRequest $request): JsonResponse
    {
        try {
            $ad = $this->createAdAction->execute(
                $request->validated(),
                Auth::id()
            );

            return (new AdResource($ad))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create ad',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function myAds(GetAdsRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);

        $ads = $this->getUserAdsAction->execute(Auth::id(), $perPage);

        return AdResource::collection($ads);
    }

    public function show(int $id): AdResource|JsonResponse
    {
        $ad = $this->findAdAction->execute($id);

        if (!$ad) {
            return response()->json([
                'message' => 'Ad not found',
            ], 404);
        }

        return new AdResource($ad);
    }
}
