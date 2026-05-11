<?php

namespace App\Models;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Receptionists\Models\Receptionist;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Traits\HasUuidV7;
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
}
