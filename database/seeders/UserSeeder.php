<?php

namespace Database\Seeders;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Domains\Receptionists\Models\Receptionist;
use App\Enums\RoleEnum;
use App\Enums\SpecializationEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'password';
        $users = [
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'role' => RoleEnum::Admin,
            ],
            [
                'first_name' => 'Doctor',
                'last_name' => 'User',
                'username' => 'doctor',
                'email' => 'doctor@gmail.com',
                'role' => RoleEnum::Doctor,
                'specialization' => SpecializationEnum::Cardiology,
                'experience_months' => 60,
            ],
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Ali',
                'username' => 'doctor2',
                'email' => 'doctor2@gmail.com',
                'role' => RoleEnum::Doctor,
                'specialization' => SpecializationEnum::Dermatology,
                'experience_months' => 36,
            ],
            [
                'first_name' => 'Patient',
                'last_name' => 'User',
                'username' => 'patient',
                'email' => 'patient@gmail.com',
                'role' => RoleEnum::Patient,
            ],
            [
                'first_name' => 'Receptionist',
                'last_name' => 'User',
                'username' => 'receptionist',
                'email' => 'receptionist@gmail.com',
                'role' => RoleEnum::Receptionist,
            ],
        ];

        foreach ($users as $data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $password,
                'role' => $data['role'],
                'is_active' => true,
            ]);

            match ($data['role']) {
                RoleEnum::Doctor => Doctor::create([
                    'user_id' => $user->id,
                    'specialization' => $data['specialization'],
                    'experience_months' => $data['experience_months'],
                ]),
                RoleEnum::Patient => Patient::create(['user_id' => $user->id]),
                RoleEnum::Receptionist => Receptionist::create([
                    'user_id' => $user->id,
                    'shift_start' => '09:00',
                    'shift_end' => '17:00',
                ]),
                default => null,
            };
        }
    }
}
