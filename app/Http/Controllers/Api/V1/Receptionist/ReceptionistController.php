<?php

namespace App\Http\Controllers\Api\V1\Receptionist;

use App\Domains\Receptionists\Actions\ActivateReceptionistAccountAction;
use App\Domains\Receptionists\Actions\CreateReceptionistAction;
use App\Domains\Receptionists\Actions\DeleteReceptionistAction;
use App\Domains\Receptionists\Actions\UpdateReceptionistAction;
use App\Domains\Receptionists\Models\Receptionist;
use App\Domains\Receptionists\Requests\PatchReceptionistRequest;
use App\Domains\Receptionists\Requests\StoreReceptionistRequest;
use App\Domains\Receptionists\Requests\UpdateReceptionistRequest;
use App\Domains\Receptionists\Resources\ReceptionistResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReceptionistController
{
    public function __construct(
        private readonly ActivateReceptionistAccountAction $activateReceptionistAccountAction,
        private readonly CreateReceptionistAction $createReceptionistAction,
        private readonly UpdateReceptionistAction $updateReceptionistAction,
        private readonly DeleteReceptionistAction $deleteReceptionistAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $version = Cache::get('receptionists:cache_version', 0);
        $cacheKey = 'receptionists:index:v' . $version . ':' . md5(serialize($request->only(['search', 'gender', 'date_from', 'date_to', 'is_active', 'shift_start_from', 'shift_start_to', 'shift_end_from', 'shift_end_to', 'page', 'limit'])));

        $receptionists = Cache::remember($cacheKey, 172800, function () use ($request, $limit) {
            return User::whereHas('roles', fn($q) => $q->where('slug', 'receptionist'))
                ->with('receptionist', 'roles')
                ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                    $q->where('first_name', 'like', "%{$v}%")
                      ->orWhere('last_name', 'like', "%{$v}%")
                      ->orWhere('email', 'like', "%{$v}%");
                }))
                ->when($request->gender, fn ($q, $v) => $q->where('gender', $v))
                ->when($request->date_from, fn ($q, $v) => $q->where('birthday_date', '>=', $v))
                ->when($request->date_to, fn ($q, $v) => $q->where('birthday_date', '<=', $v))
                ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
                ->when($request->shift_start_from, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_start', '>=', $v)))
                ->when($request->shift_start_to, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_start', '<=', $v)))
                ->when($request->shift_end_from, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_end', '>=', $v)))
                ->when($request->shift_end_to, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_end', '<=', $v)))
                ->paginate(min($limit, 100));
        });

        return ApiResponse::success(
            ReceptionistResource::collection($receptionists),
            __('Receptionists retrieved successfully'),
            pagination: ApiResponse::pagination($receptionists)
        );
    }

    public function show(string $receptionist): JsonResponse
    {
        $version = Cache::get('receptionists:cache_version', 0);
        $cacheKey = 'receptionists:show:v' . $version . ':' . $receptionist;

        $user = Cache::remember($cacheKey, 172800, function () use ($receptionist) {
            $receptionistModel = Receptionist::where('user_id', $receptionist)
                ->with('user.roles')
                ->firstOrFail();

            $user = $receptionistModel->user;
            $user->setRelation('receptionist', $receptionistModel);

            return $user;
        });

        return ApiResponse::success(
            new ReceptionistResource($user),
            __('Receptionist retrieved successfully')
        );
    }

    public function store(StoreReceptionistRequest $request): JsonResponse
    {
        $user = $this->createReceptionistAction->execute($request);

        return ApiResponse::created(
            new ReceptionistResource($user),
            __('Receptionist created successfully'),
        );
    }

    public function update(UpdateReceptionistRequest $request, Receptionist $receptionist): JsonResponse
    {
        $dto = \App\Domains\Receptionists\DTOs\UpdateReceptionistData::fromRequest($request);
        $user = $this->updateReceptionistAction->execute($receptionist, $dto);

        return ApiResponse::success(
            new ReceptionistResource($user),
            __('Receptionist updated successfully')
        );
    }

    public function updatePartial(PatchReceptionistRequest $request, Receptionist $receptionist): JsonResponse
    {
        $dto = \App\Domains\Receptionists\DTOs\UpdateReceptionistData::fromRequestPartial($request);
        $user = $this->updateReceptionistAction->execute($receptionist, $dto);

        return ApiResponse::success(
            new ReceptionistResource($user),
            __('Receptionist updated successfully')
        );
    }

    public function destroy(Receptionist $receptionist): JsonResponse
    {
        $this->deleteReceptionistAction->execute($receptionist, request()->user());

        return ApiResponse::noContent(__('Receptionist deleted successfully'));
    }

    public function activateAccount(Receptionist $receptionist): JsonResponse
    {
        $receptionist = $this->activateReceptionistAccountAction->execute($receptionist);

        return ApiResponse::success(
            new ReceptionistResource($receptionist->user),
            __('auth.account_activated')
        );
    }
}
