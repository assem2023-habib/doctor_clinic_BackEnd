<?php

namespace App\Domains\Ratings\Controllers;

use App\Domains\Ratings\Actions\CreateRatingAction;
use App\Domains\Ratings\Actions\DeleteRatingAction;
use App\Domains\Ratings\Actions\UpdateRatingAction;
use App\Domains\Ratings\DTOs\RatingData;
use App\Domains\Ratings\Models\Rating;
use App\Domains\Ratings\Requests\StoreRatingRequest;
use App\Domains\Ratings\Requests\UpdateRatingRequest;
use App\Domains\Ratings\Resources\RatingResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RatingController
{
    public function __construct(
        private readonly CreateRatingAction $createRatingAction,
        private readonly UpdateRatingAction $updateRatingAction,
        private readonly DeleteRatingAction $deleteRatingAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $version = Cache::get('ratings:cache_version', 0);
        $cacheKey = 'ratings:index:v' . $version . ':' . md5(serialize($request->only([
            'type', 'rater_id', 'rateable_id', 'rateable_type', 'rating', 'page', 'limit',
        ])));

        $ratings = Cache::remember($cacheKey, 172800, function () use ($request, $limit) {
            return Rating::with('rater')
                ->when($request->type, fn ($q, $v) => $q->whereIn('type', (array) $v))
                ->when($request->rater_id, fn ($q, $v) => $q->where('rater_id', $v))
                ->when($request->rateable_id, fn ($q, $v) => $q->where('rateable_id', $v))
                ->when($request->rateable_type, fn ($q, $v) => $q->where('rateable_type', $v))
                ->when($request->rating, fn ($q, $v) => $q->where('rating', $v))
                ->latest()
                ->paginate(min($limit, 100));
        });

        return ApiResponse::success(
            RatingResource::collection($ratings),
            __('Ratings retrieved successfully'),
            pagination: ApiResponse::pagination($ratings)
        );
    }

    public function show(Rating $rating): JsonResponse
    {
        $version = Cache::get('ratings:cache_version', 0);
        $cacheKey = 'ratings:show:v' . $version . ':' . $rating->id;

        $rating = Cache::remember($cacheKey, 172800, function () use ($rating) {
            return $rating->load('rater');
        });

        return ApiResponse::success(
            new RatingResource($rating),
            __('Rating retrieved successfully')
        );
    }

    public function store(StoreRatingRequest $request): JsonResponse
    {
        $dto = RatingData::fromStoreRequest($request);
        $rating = $this->createRatingAction->execute($dto);

        return ApiResponse::created(
            new RatingResource($rating),
            __('Rating created successfully')
        );
    }

    public function update(UpdateRatingRequest $request, Rating $rating): JsonResponse
    {
        $dto = RatingData::fromUpdateRequest($request);
        $rating = $this->updateRatingAction->execute($rating, $dto, $request->user()->id);

        return ApiResponse::success(
            new RatingResource($rating),
            __('Rating updated successfully')
        );
    }

    public function destroy(Request $request, Rating $rating): JsonResponse
    {
        $user = $request->user();
        $this->deleteRatingAction->execute($rating, $user->id, $user->hasRole('admin'));

        return ApiResponse::noContent(__('Rating deleted successfully'));
    }
}
