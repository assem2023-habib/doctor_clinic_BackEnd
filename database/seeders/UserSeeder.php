<?php

namespace Database\Seeders;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Domains\Receptionists\Models\Receptionist;
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
                'role' => 'admin',
            ],
            [
                'first_name' => 'Doctor',
                'last_name' => 'User',
                'username' => 'doctor',
                'email' => 'doctor@gmail.com',
                'role' => 'doctor',
            ],
            [
                'first_name' => 'Patient',
                'last_name' => 'User',
                'username' => 'patient',
                'email' => 'patient@gmail.com',
                'role' => 'patient',
            ],
            [
                'first_name' => 'Receptionist',
                'last_name' => 'User',
                'username' => 'receptionist',
                'email' => 'receptionist@gmail.com',
                'role' => 'receptionist',
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
                'doctor' => Doctor::create(['user_id' => $user->id]),
                'patient' => Patient::create(['user_id' => $user->id]),
                'receptionist' => Receptionist::create([
                    'user_id' => $user->id,
                    'shift_start' => '09:00',
                    'shift_end' => '17:00',
                ]),
                default => null,
            };
        }
    }
}
