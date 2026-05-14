<?php

namespace App\Domains\Doctors\Models;

use App\Domains\Appointments\Models\Appointment;
use App\Enums\SpecializationEnum;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasUuidV7;

    protected $fillable = ['user_id', 'specialization', 'experience_months'];

    protected function casts(): array
    {
        return [
            'specialization' => SpecializationEnum::class,
            'experience_months' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
