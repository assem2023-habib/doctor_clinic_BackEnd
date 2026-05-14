<?php

namespace App\Domains\Doctors\Resources;

use App\Domains\Shared\Resources\UserResource;

class DoctorResource extends UserResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'specialization' => $this->doctor?->specialization?->value,
            'experience_months' => $this->doctor?->experience_months,
            'schedules' => $this->doctor?->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week?->value,
                'start_time' => $schedule->start_time?->format('H:i'),
                'end_time' => $schedule->end_time?->format('H:i'),
                'is_active' => $schedule->is_active,
            ]),
        ]);
    }
}
