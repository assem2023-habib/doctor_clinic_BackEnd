<?php

namespace App\Domains\Patients\Models;

use App\Domains\Doctors\Models\Doctor;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasUuidV7;

    protected $fillable = ['user_id'];

    public function getRouteKeyName(): string
    {
        return 'user_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Domains\Appointments\Models\Appointment::class);
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_patient')
            ->withPivot('assigned_by', 'notes', 'supervision_status', 'supervision_start', 'supervision_end', 'created_at')
            ->withTimestamps();
    }
}
