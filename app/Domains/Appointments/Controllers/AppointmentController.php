<?php

namespace App\Domains\Appointments\Controllers;

use App\Domains\Appointments\Actions\CancelAppointmentAction;
use App\Domains\Appointments\Actions\CompleteAppointmentAction;
use App\Domains\Appointments\Actions\PatientRespondAction;
use App\Domains\Appointments\Actions\RequestAppointmentAction;
use App\Domains\Appointments\Actions\SetAppointmentTimeAction;
use App\Domains\Appointments\Actions\StartAppointmentAction;
use App\Domains\Appointments\Actions\SuggestAlternativeAction;
use App\Domains\Appointments\DTOs\RequestAppointmentData;
use App\Domains\Appointments\DTOs\SetAppointmentTimeData;
use App\Domains\Appointments\DTOs\SuggestAlternativeData;
use App\Domains\Appointments\Enums\PatientResponseEnum;
use App\Domains\Appointments\Jobs\AutoConfirmAppointment;
use App\Domains\Appointments\Models\Appointment;
use App\Domains\Appointments\Requests\ListAppointmentsRequest;
use App\Domains\Appointments\Requests\PatientRespondRequest;
use App\Domains\Appointments\Requests\RequestAppointmentRequest;
use App\Domains\Appointments\Requests\SetAppointmentTimeRequest;
use App\Domains\Appointments\Requests\SuggestAlternativeRequest;
use App\Domains\Appointments\Resources\AppointmentResource;
use App\Domains\Appointments\Services\AppointmentRtdbService;
use App\Domains\Appointments\Services\AvailableSlotsService;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Shared\Responses\ApiResponse;
use App\Enums\AppointmentStatusEnum;
use App\Enums\HttpStatusEnum;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController
{
    public function __construct(
        private readonly RequestAppointmentAction $requestAction,
        private readonly SetAppointmentTimeAction $setTimeAction,
        private readonly PatientRespondAction $respondAction,
        private readonly CancelAppointmentAction $cancelAction,
        private readonly CompleteAppointmentAction $completeAction,
        private readonly StartAppointmentAction $startAction,
        private readonly SuggestAlternativeAction $suggestAction,
        private readonly AvailableSlotsService $slotsService,
        private readonly NotificationManager $notificationManager,
        private readonly AppointmentRtdbService $rtdb,
    ) {}

    public function bookedSlots(Request $request, Doctor $doctor): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date', 'required_with:to_date'],
            'to_date' => ['nullable', 'date', 'required_with:from_date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $limit = (int) $request->integer('limit', 20);

        $paginator = $this->slotsService->getBookedSlots(
            $doctor,
            $limit,
            $request->date,
            $request->from_date,
            $request->to_date,
        );

        $slots = collect($paginator->items())->map(fn ($a) => [
            'appointment_date' => $a->appointment_date?->format('Y-m-d'),
            'start_time' => $a->start_time?->format('H:i'),
            'end_time' => $a->end_time?->format('H:i'),
        ]);

        return ApiResponse::success(
            $slots,
            __('Booked slots retrieved successfully'),
            pagination: ApiResponse::pagination($paginator)
        );
    }

    public function index(ListAppointmentsRequest $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->integer('limit', 20);

        $query = Appointment::with(['patient.user.image', 'doctor.user.image', 'doctor.schedules']);

        if ($user->hasRole('patient')) {
            $patient = $user->patient;
            $query->where('patient_id', $patient?->id);
        } elseif ($user->hasRole('doctor')) {
            $doctor = $user->doctor;
            $query->where('doctor_id', $doctor?->id);
        } elseif (!$user->hasAnyRole(['admin', 'receptionist'])) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('appointment_date', $request->date);
        }

        $appointments = $query->orderBy('created_at', 'desc')
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            AppointmentResource::collection($appointments),
            __('Appointments retrieved successfully'),
            pagination: ApiResponse::pagination($appointments)
        );
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccess($user, $appointment)) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        return ApiResponse::success(
            new AppointmentResource($appointment),
            __('Appointment retrieved successfully')
        );
    }

    public function store(RequestAppointmentRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('patient')) {
            return ApiResponse::forbidden(__('Only patients can request appointments'));
        }

        $patient = $user->patient;
        if (!$patient) {
            return ApiResponse::error(__('Patient profile not found'), 400);
        }

        $data = RequestAppointmentData::fromRequest($request, $patient->id);
        $appointment = $this->requestAction->execute($data);

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        $this->notificationManager->send('appointment.requested', new NotificationData(
            topic: 'appointment.requested',
            title: __('New Appointment Request'),
            body: [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'reason' => $appointment->reason,
            ],
            userIds: [$appointment->doctor?->user_id],
            type: 'appointment',
        ));

        return ApiResponse::success(
            new AppointmentResource($appointment),
            __('Appointment requested successfully'),
            status: HttpStatusEnum::Created
        );
    }

    public function setTime(SetAppointmentTimeRequest $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['admin', 'receptionist'])
            && !($user->hasRole('doctor') && $user->doctor?->id === $appointment->doctor_id)) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        if ($appointment->status !== AppointmentStatusEnum::Requested) {
            return ApiResponse::error(__('Appointment is not in requested status'), 400);
        }

        $data = new SetAppointmentTimeData(
            appointmentDate: $request->appointment_date,
            startTime: $request->start_time,
            endTime: $request->end_time,
            changedBy: "{$user->id}: {$user->first_name} {$user->last_name}",
        );

        $appointment = $this->setTimeAction->execute($appointment, $data);

        AutoConfirmAppointment::dispatch($appointment->id)
            ->delay(Carbon::now()->addHours((int) config('appointment.response_window_hours', 6)));

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        $this->notificationManager->send('appointment.time_set', new NotificationData(
            topic: 'appointment.time_set',
            title: __('Appointment Time Set'),
            body: [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
                'start_time' => $appointment->start_time?->format('H:i'),
                'end_time' => $appointment->end_time?->format('H:i'),
            ],
            userIds: [$appointment->patient?->user_id],
            type: 'appointment',
        ));

        $this->rtdb->syncAppointment($appointment);

        return ApiResponse::success(
            new AppointmentResource($appointment),
            __('Appointment time set successfully')
        );
    }

    public function respond(PatientRespondRequest $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();
        $patient = $user->patient;

        if (!$patient || $patient->id !== $appointment->patient_id) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        if ($appointment->status !== AppointmentStatusEnum::Set) {
            return ApiResponse::error(__('Appointment is not awaiting your response'), 400);
        }

        $response = PatientResponseEnum::from($request->response);
        $changedBy = "{$user->id}: {$user->first_name} {$user->last_name}";

        $appointment = $this->respondAction->execute($appointment, $response, $changedBy);

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        $event = $response === PatientResponseEnum::Accepted
            ? 'appointment.accepted'
            : 'appointment.rejected';

        $title = $response === PatientResponseEnum::Accepted
            ? __('Appointment Accepted')
            : __('Appointment Rejected');

        $this->notificationManager->send($event, new NotificationData(
            topic: $event,
            title: $title,
            body: [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'response' => $response->value,
            ],
            userIds: [$appointment->doctor?->user_id],
            type: 'appointment',
        ));

        if ($response === PatientResponseEnum::Accepted) {
            $this->rtdb->syncAppointment($appointment);
        } else {
            $this->rtdb->removeAppointment($appointment);
        }

        return ApiResponse::success(
            new AppointmentResource($appointment),
            $response === PatientResponseEnum::Accepted
                ? __('Appointment confirmed successfully')
                : __('Appointment rejected')
        );
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);
        $isDoctor = $user->hasRole('doctor') && $user->doctor?->id === $appointment->doctor_id;

        if (!$isStaff && !$isDoctor) {
            return ApiResponse::forbidden(__('Only staff can cancel appointments'));
        }

        $changedBy = "{$user->id}: {$user->first_name} {$user->last_name}";
        $appointment = $this->cancelAction->execute($appointment, $changedBy);

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        $this->notificationManager->send('appointment.cancelled', new NotificationData(
            topic: 'appointment.cancelled',
            title: __('Appointment Cancelled'),
            body: [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'cancelled_by' => $changedBy,
            ],
            userIds: [$appointment->patient?->user_id, $appointment->doctor?->user_id],
            type: 'appointment',
        ));

        $this->rtdb->removeAppointment($appointment);

        return ApiResponse::success(
            new AppointmentResource($appointment),
            __('Appointment cancelled successfully')
        );
    }

    public function complete(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);
        $isDoctor = $user->hasRole('doctor') && $user->doctor?->id === $appointment->doctor_id;

        if (!$isStaff && !$isDoctor) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        if ($appointment->status !== AppointmentStatusEnum::InProgress) {
            return ApiResponse::error(__('Only in-progress appointments can be completed'), HttpStatusEnum::BadRequest);
        }

        $changedBy = "{$user->id}: {$user->first_name} {$user->last_name}";
        $appointment = $this->completeAction->execute($appointment, $changedBy);

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        $this->notificationManager->send('appointment.completed', new NotificationData(
            topic: 'appointment.completed',
            title: __('Appointment Completed'),
            body: [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
            ],
            userIds: [$appointment->patient?->user_id],
            type: 'appointment',
        ));

        $this->rtdb->removeAppointment($appointment);

        return ApiResponse::success(
            new AppointmentResource($appointment),
            __('Appointment completed successfully')
        );
    }

    public function start(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);
        $isDoctor = $user->hasRole('doctor') && $user->doctor?->id === $appointment->doctor_id;

        if (!$isStaff && !$isDoctor) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        if ($appointment->status !== AppointmentStatusEnum::Accepted) {
            return ApiResponse::error(__('Only accepted appointments can be started'), HttpStatusEnum::BadRequest);
        }

        $changedBy = "{$user->id}: {$user->first_name} {$user->last_name}";
        $appointment = $this->startAction->execute($appointment, $changedBy);

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        $this->notificationManager->send('appointment.in_progress', new NotificationData(
            topic: 'appointment.in_progress',
            title: __('Appointment In Progress'),
            body: [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
            ],
            userIds: [$appointment->patient?->user_id],
            type: 'appointment',
        ));

        $this->rtdb->syncAppointment($appointment);

        return ApiResponse::success(
            new AppointmentResource($appointment),
            __('Appointment started successfully')
        );
    }

    public function suggestAlternative(SuggestAlternativeRequest $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);
        $isDoctor = $user->hasRole('doctor') && $user->doctor?->id === $appointment->doctor_id;

        if (!$isStaff && !$isDoctor) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        if ($appointment->status !== AppointmentStatusEnum::Requested) {
            return ApiResponse::error(__('Can only suggest alternatives for requested appointments'), 400);
        }

        $data = new SuggestAlternativeData(
            message: $request->message,
            changedBy: "{$user->id}: {$user->first_name} {$user->last_name}",
        );

        $appointment = $this->suggestAction->execute($appointment, $data);

        $appointment->load(['patient.user.image', 'doctor.user.image']);

        $this->notificationManager->send('appointment.alternative_suggested', new NotificationData(
            topic: 'appointment.alternative_suggested',
            title: __('Alternative Suggested'),
            body: [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'message' => $data->message,
            ],
            userIds: [$appointment->patient?->user_id],
            type: 'appointment',
        ));

        return ApiResponse::success(
            new AppointmentResource($appointment),
            __('Alternative suggested successfully')
        );
    }

    private function canAccess($user, Appointment $appointment): bool
    {
        if ($user->hasAnyRole(['admin', 'receptionist'])) {
            return true;
        }

        if ($user->hasRole('patient') && $user->patient?->id === $appointment->patient_id) {
            return true;
        }

        if ($user->hasRole('doctor') && $user->doctor?->id === $appointment->doctor_id) {
            return true;
        }

        return false;
    }
}
