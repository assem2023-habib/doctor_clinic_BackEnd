<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Domains\Doctors\Actions\ActivateDoctorAccountAction;
use App\Domains\Doctors\Actions\CreateDoctorAction;
use App\Domains\Doctors\Actions\DeleteDoctorAction;
use App\Domains\Doctors\Actions\UpdateDoctorAction;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Requests\PatchDoctorRequest;
use App\Domains\Patients\Models\Patient;
use App\Domains\Doctors\Requests\StoreDoctorRequest;
use App\Domains\Doctors\Requests\UpdateDoctorRequest;
use App\Domains\Doctors\Resources\DoctorResource;
use App\Domains\Ratings\Models\Rating;
use App\Domains\Ratings\Resources\RatingResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Supervisions\Models\SupervisionRequest;
use App\Enums\RatingTypeEnum;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController
{
    public function __construct(
        private readonly UpdateDoctorAction $updateDoctorAction,
        private readonly DeleteDoctorAction $deleteDoctorAction,
        private readonly ActivateDoctorAccountAction $activateDoctorAccountAction,
        private readonly CreateDoctorAction $createDoctorAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $doctors = User::whereHas('roles', fn($q) => $q->where('slug', 'doctor'))
            ->with(['doctor.schedules', 'roles'])
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%");
            }))
            ->when($request->specialization_id, fn ($q, $v) => $q->whereHas('doctor', fn ($q) => $q->where('specialization_id', $v)))
            ->when($request->experience_from, fn ($q, $v) => $q->whereHas('doctor', fn ($q) => $q->where('experience_months', '>=', (int) $v)))
            ->when($request->experience_to, fn ($q, $v) => $q->whereHas('doctor', fn ($q) => $q->where('experience_months', '<=', (int) $v)))
            ->when($request->gender, fn ($q, $v) => $q->where('gender', $v))
            ->when($request->date_from, fn ($q, $v) => $q->where('birthday_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->where('birthday_date', '<=', $v))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('has_appointments'), fn ($q) => $request->boolean('has_appointments') ? $q->whereHas('doctor.appointments') : $q->whereDoesntHave('doctor.appointments'))
            ->when($request->filled('appointment_status'), fn ($q) => $q->whereHas('doctor.appointments', fn ($q) => $q->whereIn('status', (array) $request->appointment_status)))
            ->when($request->filled('appointment_date'), fn ($q) => $q->whereHas('doctor.appointments', fn ($q) => $q->whereDate('appointment_date', $request->appointment_date)))
            ->when($request->filled('appointment_from_date'), fn ($q) => $q->whereHas('doctor.appointments', fn ($q) => $q->whereDate('appointment_date', '>=', $request->appointment_from_date)))
            ->when($request->filled('appointment_to_date'), fn ($q) => $q->whereHas('doctor.appointments', fn ($q) => $q->whereDate('appointment_date', '<=', $request->appointment_to_date)))
            ->when($request->filled('appointment_patient_id'), fn ($q) => $q->whereHas('doctor.appointments', fn ($q) => $q->whereIn('patient_id', Patient::whereIn('user_id', (array) $request->appointment_patient_id)->pluck('id'))))
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            DoctorResource::collection($doctors),
            __('Doctors retrieved successfully'),
            pagination: ApiResponse::pagination($doctors)
        );
    }

    public function show(Request $request, string $doctor): JsonResponse
    {
        $doctor = Doctor::where('user_id', $doctor)
            ->with('user.roles', 'schedules')
            ->firstOrFail();

        $doctor->loadCount('ratings');
        $doctor->loadAvg('ratings', 'rating');

        if ($request->user()?->patient) {
            $supervisionRequest = SupervisionRequest::where('patient_id', $request->user()->patient->id)
                ->where('doctor_id', $doctor->id)
                ->first();

            if ($supervisionRequest) {
                $doctor->supervision_request_status = $supervisionRequest->status->value;
            }

            $doctor->user->has_rated_doctor = Rating::where('rater_id', $request->user()->id)
                ->where('rateable_id', $doctor->user_id)
                ->where('type', RatingTypeEnum::User)
                ->where('rateable_type', 'App\Models\User')
                ->exists();
        }

        $recentRatings = $doctor->ratings()
            ->with('rater')
            ->latest()
            ->limit(5)
            ->get();

        $doctor->setRelation('recentRatings', $recentRatings);

        $user = $doctor->user;
        $user->setRelation('doctor', $doctor);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor retrieved successfully')
        );
    }

    public function ratings(Request $request, string $doctor): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);

        $doctor = Doctor::where('user_id', $doctor)->firstOrFail();

        $ratings = $doctor->ratings()
            ->with('rater')
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('comment', 'like', "%{$v}%")
                  ->orWhereHas('rater', fn ($q) => $q->where('first_name', 'like', "%{$v}%")
                      ->orWhere('last_name', 'like', "%{$v}%"));
            }))
            ->latest()
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            RatingResource::collection($ratings),
            __('Doctor ratings retrieved successfully'),
            pagination: ApiResponse::pagination($ratings)
        );
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $dto = \App\Domains\Doctors\DTOs\UpdateDoctorData::fromRequest($request);
        $user = $this->updateDoctorAction->execute($doctor, $dto);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor updated successfully')
        );
    }

    public function updatePartial(PatchDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $dto = \App\Domains\Doctors\DTOs\UpdateDoctorData::fromRequestPartial($request);
        $user = $this->updateDoctorAction->execute($doctor, $dto);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor updated successfully')
        );
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        $this->deleteDoctorAction->execute($doctor, request()->user());

        return ApiResponse::noContent(__('Doctor deleted successfully'));
    }

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $user = $this->createDoctorAction->execute($request);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor created successfully'),
            status: 201
        );
    }

    public function activateAccount(Doctor $doctor): JsonResponse
    {
        $doctor = $this->activateDoctorAccountAction->execute($doctor);

        return ApiResponse::success(
            new DoctorResource($doctor->user),
            __('auth.account_activated')
        );
    }
}
