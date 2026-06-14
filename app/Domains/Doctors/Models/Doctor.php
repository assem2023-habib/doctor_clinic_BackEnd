<?php

namespace App\Domains\Doctors\Models;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Patients\Models\Patient;
use App\Domains\Ratings\Models\Rating;
use App\Enums\RatingTypeEnum;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasUuidV7;

    protected $fillable = ['user_id', 'specialization_id', 'experience_months'];

    public function getRouteKeyName(): string
    {
        return 'user_id';
    }

    protected function casts(): array
    {
        return [
            'experience_months' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'doctor_patient')
            ->withPivot('assigned_by', 'notes', 'supervision_status', 'supervision_start', 'supervision_end', 'created_at')
            ->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'rateable_id', 'user_id')
            ->where('type', RatingTypeEnum::User)
            ->where('rateable_type', 'App\Models\User');
    }
}
