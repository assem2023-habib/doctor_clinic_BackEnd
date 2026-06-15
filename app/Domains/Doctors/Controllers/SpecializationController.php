<?php

namespace App\Domains\Doctors\Controllers;

use App\Domains\Doctors\Actions\CreateSpecializationAction;
use App\Domains\Doctors\Actions\DeleteSpecializationAction;
use App\Domains\Doctors\Actions\UpdateSpecializationAction;
use App\Domains\Doctors\DTOs\SpecializationData;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\Doctors\Requests\StoreSpecializationRequest;
use App\Domains\Doctors\Requests\UpdateSpecializationRequest;
use App\Domains\Doctors\Resources\SpecializationResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SpecializationController
{
    public function __construct(
        private readonly CreateSpecializationAction $createSpecializationAction,
        private readonly UpdateSpecializationAction $updateSpecializationAction,
        private readonly DeleteSpecializationAction $deleteSpecializationAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $version = Cache::get('specializations:cache_version', 0);
        $cacheKey = 'specializations:index:v' . $version . ':' . md5(serialize($request->only(['search', 'slug', 'is_active', 'page', 'limit'])));

        $specializations = Cache::remember($cacheKey, 172800, function () use ($request, $limit) {
            return Specialization::withCount('doctors')
                ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                    $q->where('name->ar', 'like', "%{$v}%")
                      ->orWhere('name->en', 'like', "%{$v}%");
                }))
                ->when($request->slug, fn ($q, $v) => $q->where('slug', $v))
                ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
                ->paginate(min($limit, 100));
        });

        return ApiResponse::success(
            SpecializationResource::collection($specializations),
            __('Specializations retrieved successfully'),
            pagination: ApiResponse::pagination($specializations)
        );
    }

    public function show(Specialization $specialization): JsonResponse
    {
        $version = Cache::get('specializations:cache_version', 0);
        $cacheKey = 'specializations:show:v' . $version . ':' . $specialization->id;

        $specialization = Cache::remember($cacheKey, 172800, function () use ($specialization) {
            return $specialization->loadCount('doctors')->load('image');
        });

        return ApiResponse::success(
            new SpecializationResource($specialization),
            __('Specialization retrieved successfully')
        );
    }

    public function store(StoreSpecializationRequest $request): JsonResponse
    {
        $dto = SpecializationData::fromStoreRequest($request);
        $specialization = $this->createSpecializationAction->execute($dto, $request->file('file'));

        return ApiResponse::created(
            new SpecializationResource($specialization),
            __('Specialization created successfully')
        );
    }

    public function update(UpdateSpecializationRequest $request, Specialization $specialization): JsonResponse
    {
        $dto = SpecializationData::fromUpdateRequest($request);
        $specialization = $this->updateSpecializationAction->execute($specialization, $dto, $request->file('file'));

        return ApiResponse::success(
            new SpecializationResource($specialization),
            __('Specialization updated successfully')
        );
    }

    public function destroy(Specialization $specialization): JsonResponse
    {
        $this->deleteSpecializationAction->execute($specialization);

        return ApiResponse::noContent(__('Specialization deleted successfully'));
    }
}
