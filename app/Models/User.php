<?php

namespace App\Models;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Images\Models\Image;
use App\Domains\Locations\Models\City;
use App\Domains\Locations\Models\Country;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Patients\Models\Patient;
use App\Domains\Ratings\Models\Rating;
use App\Domains\RBAC\Models\Role;
use App\Domains\RBAC\Services\PermissionService;
use App\Domains\Receptionists\Models\Receptionist;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

#[Fillable([
    'first_name',
    'last_name',
    'username',
    'email',
    'phone',
    'address',
    'gender',
    'birthday_date',
    'role',
    'is_active',
    'password',
    'country_id',
    'city_id',
    'device_tokens',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuidV7, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'birthday_date' => 'date',
            'gender' => GenderEnum::class,
            'role' => RoleEnum::class,
            'device_tokens' => 'array',
        ];
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function receptionist()
    {
        return $this->hasOne(Receptionist::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function createdAppointments()
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class)
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function ratingsGiven()
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    public function ratingsReceived()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $slug): bool
    {
        return PermissionService::hasRole($this, $slug);
    }

    public function hasAnyRole(array $slugs): bool
    {
        return PermissionService::hasAnyRole($this, $slugs);
    }

    public function hasPermission(string $slug): bool
    {
        return PermissionService::hasPermission($this, $slug);
    }
}
