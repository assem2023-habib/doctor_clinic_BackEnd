<?php

namespace App\Domains\Appointments\Resources;

use App\Domains\Images\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request): array
    {
        $patientUser = $this->patient?->user;
        $doctorUser = $this->doctor?->user;

        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'appointment_date' => $this->appointment_date?->format('Y-m-d'),
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'patient' => $patientUser ? [
                'id' => $patientUser->id,
                'first_name' => $patientUser->first_name,
                'last_name' => $patientUser->last_name,
                'email' => $patientUser->email,
                'phone' => $patientUser->phone,
                'gender' => $patientUser->gender?->value,
                'birthday_date' => $patientUser->birthday_date?->format('Y-m-d'),
                'image' => new ImageResource($patientUser->image),
            ] : null,
            'doctor' => $doctorUser ? [
                'id' => $doctorUser->id,
                'first_name' => $doctorUser->first_name,
                'last_name' => $doctorUser->last_name,
                'email' => $doctorUser->email,
                'specialization' => $this->doctor?->specialization?->value,
                'experience_months' => $this->doctor?->experience_months,
                'image' => new ImageResource($doctorUser->image),
            ] : null,
        ];
    }
}
