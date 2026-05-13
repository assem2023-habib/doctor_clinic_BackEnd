<?php

namespace App\Http\Controllers\Api\V1\Location;

use App\Domains\Locations\Actions\CreateCityAction;
use App\Domains\Locations\Actions\DeleteCityAction;
use App\Domains\Locations\Actions\UpdateCityAction;
use App\Domains\Locations\DTOs\CityData;
use App\Domains\Locations\Models\City;
use App\Domains\Locations\Requests\StoreCityRequest;
use App\Domains\Locations\Requests\UpdateCityRequest;
use App\Domains\Locations\Resources\CityResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController
{
    public function __construct(
        private readonly CreateCityAction $createCityAction,
        private readonly UpdateCityAction $updateCityAction,
        private readonly DeleteCityAction $deleteCityAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $cities = City::with('country')
            ->when($request->country_id, fn ($q, $v) => $q->where('country_id', $v))
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name->ar', 'like', "%{$v}%")
                  ->orWhere('name->en', 'like', "%{$v}%");
            }))
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            CityResource::collection($cities),
            __('Cities retrieved successfully'),
            pagination: ApiResponse::pagination($cities)
        );
    }

    public function show(City $city): JsonResponse
    {
        $city->load('country');

        return ApiResponse::success(
            new CityResource($city),
            __('City retrieved successfully')
        );
    }

    public function store(StoreCityRequest $request): JsonResponse
    {
        $dto = CityData::fromStoreRequest($request);
        $city = $this->createCityAction->execute($dto);

        return ApiResponse::created(
            new CityResource($city),
            __('City created successfully')
        );
    }

    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        $dto = CityData::fromUpdateRequest($request);
        $city = $this->updateCityAction->execute($city, $dto);

        return ApiResponse::success(
            new CityResource($city),
            __('City updated successfully')
        );
    }

    public function destroy(City $city): JsonResponse
    {
        $this->deleteCityAction->execute($city);

        return ApiResponse::noContent(__('City deleted successfully'));
    }
}
