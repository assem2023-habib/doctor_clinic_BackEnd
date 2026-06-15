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
use Illuminate\Support\Facades\Cache;

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
        $version = Cache::get('cities:cache_version', 0);
        $cacheKey = 'cities:index:v' . $version . ':' . md5(serialize($request->only(['country_id', 'search', 'page', 'limit'])));

        $cities = Cache::remember($cacheKey, 172800, function () use ($request, $limit) {
            return City::with('country')
                ->when($request->country_id, fn ($q, $v) => $q->where('country_id', $v))
                ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                    $q->where('name->ar', 'like', "%{$v}%")
                      ->orWhere('name->en', 'like', "%{$v}%");
                }))
                ->paginate(min($limit, 100));
        });

        return ApiResponse::success(
            CityResource::collection($cities),
            __('Cities retrieved successfully'),
            pagination: ApiResponse::pagination($cities)
        );
    }

    public function show(City $city): JsonResponse
    {
        $version = Cache::get('cities:cache_version', 0);
        $cacheKey = 'cities:show:v' . $version . ':' . $city->id;

        $city = Cache::remember($cacheKey, 172800, function () use ($city) {
            return $city->load('country');
        });

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
