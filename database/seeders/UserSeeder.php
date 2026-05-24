<?php

namespace Database\Seeders;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\Patients\Models\Patient;
use App\Domains\Receptionists\Models\Receptionist;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'password';

        $cardiology = Specialization::where('slug', 'cardiology')->first();
        $dermatology = Specialization::where('slug', 'dermatology')->first();

        // Admin
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => $password,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Doctor 1
        $doctor1 = User::create([
            'first_name' => 'Doctor',
            'last_name' => 'User',
            'username' => 'doctor',
            'email' => 'doctor@gmail.com',
            'password' => $password,
            'is_active' => true,
        ]);
        $doctor1->assignRole('doctor');
        Doctor::create([
            'user_id' => $doctor1->id,
            'specialization_id' => $cardiology->id,
            'experience_months' => 60,
        ]);

        // Doctor 2
        $doctor2 = User::create([
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'username' => 'doctor2',
            'email' => 'doctor2@gmail.com',
            'password' => $password,
            'is_active' => true,
        ]);
        $doctor2->assignRole('doctor');
        Doctor::create([
            'user_id' => $doctor2->id,
            'specialization_id' => $dermatology->id,
            'experience_months' => 36,
        ]);

        // Patient
        $patient = User::create([
            'first_name' => 'Patient',
            'last_name' => 'User',
            'username' => 'patient',
            'email' => 'patient@gmail.com',
            'password' => $password,
            'is_active' => true,
        ]);
        $patient->assignRole('patient');
        Patient::create(['user_id' => $patient->id]);

        // Receptionist
        $receptionist = User::create([
            'first_name' => 'Receptionist',
            'last_name' => 'User',
            'username' => 'receptionist',
            'email' => 'receptionist@gmail.com',
            'password' => $password,
            'is_active' => true,
        ]);
        $receptionist->assignRole('receptionist');
        Receptionist::create([
            'user_id' => $receptionist->id,
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ]);
    }
}
