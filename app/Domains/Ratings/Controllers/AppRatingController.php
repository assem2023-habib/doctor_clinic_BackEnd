<?php

namespace App\Domains\Ratings\Controllers;

use App\Domains\Ratings\Models\Rating;
use App\Domains\Ratings\Resources\AppRatingResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppRatingController
{
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);

        $types = ['service', 'center', 'appointment_system'];

        $ratings = Rating::with('rater')
            ->whereIn('type', $types)
            ->when($request->filled('type'), function ($q) use ($request) {
                $filterTypes = $request->input('type');
                $filterTypes = is_array($filterTypes) ? $filterTypes : [$filterTypes];
                $q->whereIn('type', $filterTypes);
            })
            ->when($request->rating, fn ($q, $v) => $q->where('rating', $v))
            ->latest()
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            AppRatingResource::collection($ratings),
            __('App ratings retrieved successfully'),
            pagination: ApiResponse::pagination($ratings)
        );
    }
}
