<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $tokens = collect($user->fcm_tokens ?? [])
            ->push($validated['token'])
            ->unique()
            ->values()
            ->toArray();

        $user->update(['fcm_tokens' => $tokens]);

        return ApiResponse::success(
            ['fcm_tokens' => $tokens],
            __('Device token updated successfully')
        );
    }
}
