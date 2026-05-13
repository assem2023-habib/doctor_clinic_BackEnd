<?php

namespace App\Http\Controllers\Api\V1\Location;

use App\Domains\Locations\Actions\CreateCountryAction;
use App\Domains\Locations\Actions\DeleteCountryAction;
use App\Domains\Locations\Actions\UpdateCountryAction;
use App\Domains\Locations\DTOs\CountryData;
use App\Domains\Locations\Models\Country;
use App\Domains\Locations\Requests\StoreCountryRequest;
use App\Domains\Locations\Requests\UpdateCountryRequest;
use App\Domains\Locations\Resources\CountryResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController
{
    public function __construct(
        private readonly CreateCountryAction $createCountryAction,
        private readonly UpdateCountryAction $updateCountryAction,
        private readonly DeleteCountryAction $deleteCountryAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $countries = Country::with('cities')
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name->ar', 'like', "%{$v}%")
                  ->orWhere('name->en', 'like', "%{$v}%");
            }))
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            CountryResource::collection($countries),
            __('Countries retrieved successfully'),
            pagination: [
                'current_page' => $countries->currentPage(),
                'last_page' => $countries->lastPage(),
                'limit' => $countries->perPage(),
                'total' => $countries->total(),
                'from' => $countries->firstItem(),
                'to' => $countries->lastItem(),
            ]
        );
    }

    public function show(Country $country): JsonResponse
    {
        $country->load('cities');

        return ApiResponse::success(
            new CountryResource($country),
            __('Country retrieved successfully')
        );
    }

    public function store(StoreCountryRequest $request): JsonResponse
    {
        $dto = CountryData::fromStoreRequest($request);
        $country = $this->createCountryAction->execute($dto);

        return ApiResponse::created(
            new CountryResource($country),
            __('Country created successfully')
        );
    }

    public function update(UpdateCountryRequest $request, Country $country): JsonResponse
    {
        $dto = CountryData::fromUpdateRequest($request);
        $country = $this->updateCountryAction->execute($country, $dto);

        return ApiResponse::success(
            new CountryResource($country),
            __('Country updated successfully')
        );
    }

    public function destroy(Country $country): JsonResponse
    {
        $this->deleteCountryAction->execute($country);

        return ApiResponse::noContent(__('Country deleted successfully'));
    }
}
