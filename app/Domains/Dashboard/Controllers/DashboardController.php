<?php

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Dashboard\Services\DashboardService;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = match (true) {
            $user->hasRole('super-admin') || $user->hasRole('admin') => $this->dashboardService->forAdmin(),
            $user->hasRole('doctor') => $this->dashboardService->forDoctor($user),
            $user->hasRole('patient') => $this->dashboardService->forPatient($user),
            $user->hasRole('receptionist') => $this->dashboardService->forReceptionist($user),
            default => [],
        };

        return ApiResponse::success($data, __('Dashboard retrieved successfully'));
    }
}
