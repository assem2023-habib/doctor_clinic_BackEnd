<?php

namespace App\Domains\Doctors\Resources;

use App\Domains\Doctors\Models\Specialization;
use App\Domains\Shared\Resources\UserResource;

class DoctorResource extends UserResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'specialization' => $this->doctor?->specialization ? [
                'id' => $this->doctor->specialization->id,
                'slug' => $this->doctor->specialization->slug,
                'name' => $this->doctor->specialization->name,
                'description' => $this->doctor->specialization->description,
            ] : null,
            'experience_months' => $this->doctor?->experience_months,
            'schedules' => $this->doctor?->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week?->value,
                'start_time' => $schedule->start_time?->format('H:i'),
                'end_time' => $schedule->end_time?->format('H:i'),
                'is_active' => $schedule->is_active,
            ]),
            'rating' => [
                'avg' => round((float) ($this->doctor?->ratings_avg_rating ?? 0), 1),
                'count' => (int) ($this->doctor?->ratings_count ?? 0),
                'recent' => $this->doctor?->recentRatings?->map(fn ($r) => [
                    'id' => $r->id,
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'rater' => $r->rater ? [
                        'id' => $r->rater->id,
                        'first_name' => $r->rater->first_name,
                        'last_name' => $r->rater->last_name,
                    ] : null,
                    'created_at' => $r->created_at,
                ]) ?? [],
            ],
        ]);
    }
}
