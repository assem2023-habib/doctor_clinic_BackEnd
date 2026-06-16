<?php

namespace App\Console\Commands;

use App\Domains\Auth\DTOs\LoginData;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Models\DoctorSchedule;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\Locations\Models\City;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Patients\Models\Patient;
use App\Domains\Ratings\Models\Rating;
use App\Domains\Receptionists\Models\Receptionist;
use App\Models\User;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SpecializationSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('k6:seed')]
#[Description('Seed database with test data for K6 load testing and generate access tokens')]
class SeedK6TestData extends Command
{
    private const PASSWORD = 'Password1!';
    private const TOKENS_PATH = __DIR__ . '/../../../k6-tests/data/tokens.json';
    private const DATA_PATH = __DIR__ . '/../../../k6-tests/data/test-data.json';

    public function handle(AuthService $authService): void
    {
        $this->info('Starting K6 test data seeding...');

        $this->seedBaseData();

        $specializations = Specialization::all();
        $cities = City::all();

        // Check if test users already exist
        $existingDoctor = User::where('email', 'doctor_test_1@example.com')->first();
        if ($existingDoctor) {
            $this->warn('Test users already exist, skipping user creation...');
            $doctors = User::where('email', 'like', 'doctor_test_%@example.com')->get()->all();
            $patients = User::where('email', 'like', 'patient_test_%@example.com')->get()->all();
            $receptionists = User::where('email', 'like', 'receptionist_test_%@example.com')->get()->all();
            $admins = User::where('email', 'like', 'admin_test_%@example.com')->get()->all();

            $this->info("Found " . count($doctors) . " doctors, " . count($patients) . " patients, " . count($receptionists) . " receptionists, " . count($admins) . " admins");
        } else {
            $doctors = $this->createDoctors(50, $specializations, $cities);
            $patients = $this->createPatients(200, $cities);
            $receptionists = $this->createReceptionists(5, $cities);
            $admins = $this->createAdmins(2, $cities);
        }

        // 4. Create doctor schedules
        $this->info('Creating doctor schedules...');
        $this->createDoctorSchedules($doctors);

        // 5. Create appointments
        $this->info('Creating appointments...');
        $this->createAppointments($doctors, $patients, 1000);

        // 6. Create ratings
        $this->info('Creating ratings...');
        $this->tryStep(fn() => $this->createRatings($patients, $doctors, 500), 'ratings');

        // 7. Create notifications
        $this->info('Creating notifications...');
        $this->tryStep(fn() => $this->createNotifications(array_merge($admins, $doctors, $patients, $receptionists), 1000), 'notifications');

        // 8. Generate tokens for all users
        $this->info('Generating access tokens...');
        $allUsers = array_merge($admins, $doctors, $patients, $receptionists);
        $tokens = $this->tryStep(fn() => $this->generateTokens($allUsers, $authService), 'tokens', []);

        // 9. Save output files
        $this->saveOutputFiles($tokens, $doctors, $patients, $receptionists, $admins);

        $this->info('K6 test data seeding complete!');
        $this->warn("Total users: " . count($allUsers));
        $this->warn("Tokens saved to: " . self::TOKENS_PATH);
    }

    private function tryStep(\Closure $callback, string $label, mixed $default = null): mixed
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            $this->warn("Step '{$label}' failed: {$e->getMessage()}");
            return $default;
        }
    }

    private function seedBaseData(): void
    {
        if (Specialization::count() === 0) {
            $this->call(SpecializationSeeder::class);
        }
        if (City::count() === 0) {
            $this->call(LocationSeeder::class);
        }
        $this->call(RbacSeeder::class);

        $adminEmail = 'admin@gmail.com';
        if (!User::where('email', $adminEmail)->exists()) {
            $this->call(UserSeeder::class);
        }
    }

    /**
     * @return User[]
     */
    private function createDoctors(int $count, iterable $specializations, iterable $cities): array
    {
        $users = [];
        $specIds = collect($specializations)->pluck('id')->toArray();
        $cityIds = collect($cities)->pluck('id')->toArray();

        for ($i = 1; $i <= $count; $i++) {
            $user = User::create([
                'first_name' => 'Doctor',
                'last_name' => "Test$i",
                'username' => "doctor_test_$i",
                'email' => "doctor_test_$i@example.com",
                'password' => Hash::make(self::PASSWORD),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'is_active' => true,
                'city_id' => $cityIds ? $cityIds[array_rand($cityIds)] : null,
            ]);
            $user->assignRole('doctor');

            Doctor::create([
                'user_id' => $user->id,
                'specialization_id' => $specIds[array_rand($specIds)],
                'experience_months' => rand(12, 360),
            ]);

            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return User[]
     */
    private function createPatients(int $count, iterable $cities): array
    {
        $users = [];
        $cityIds = collect($cities)->pluck('id')->toArray();

        for ($i = 1; $i <= $count; $i++) {
            $user = User::create([
                'first_name' => 'Patient',
                'last_name' => "Test$i",
                'username' => "patient_test_$i",
                'email' => "patient_test_$i@example.com",
                'password' => Hash::make(self::PASSWORD),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'is_active' => true,
                'city_id' => $cityIds ? $cityIds[array_rand($cityIds)] : null,
            ]);
            $user->assignRole('patient');

            Patient::create(['user_id' => $user->id]);

            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return User[]
     */
    private function createReceptionists(int $count, iterable $cities): array
    {
        $users = [];
        $cityIds = collect($cities)->pluck('id')->toArray();

        for ($i = 1; $i <= $count; $i++) {
            $user = User::create([
                'first_name' => 'Receptionist',
                'last_name' => "Test$i",
                'username' => "receptionist_test_$i",
                'email' => "receptionist_test_$i@example.com",
                'password' => Hash::make(self::PASSWORD),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'is_active' => true,
                'city_id' => $cityIds ? $cityIds[array_rand($cityIds)] : null,
            ]);
            $user->assignRole('receptionist');

            Receptionist::create([
                'user_id' => $user->id,
                'shift_start' => '09:00',
                'shift_end' => '17:00',
            ]);

            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return User[]
     */
    private function createAdmins(int $count, iterable $cities): array
    {
        $users = [];
        $cityIds = collect($cities)->pluck('id')->toArray();

        for ($i = 1; $i <= $count; $i++) {
            $user = User::create([
                'first_name' => 'Admin',
                'last_name' => "Test$i",
                'username' => "admin_test_$i",
                'email' => "admin_test_$i@example.com",
                'password' => Hash::make(self::PASSWORD),
                'gender' => 'male',
                'is_active' => true,
                'city_id' => $cityIds ? $cityIds[array_rand($cityIds)] : null,
            ]);
            $user->assignRole('admin');

            $users[] = $user;
        }

        return $users;
    }

    /**
     * @param User[] $doctors
     */
    private function createDoctorSchedules(array $doctors): void
    {
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];

        foreach ($doctors as $doctor) {
            $doctorModel = Doctor::where('user_id', $doctor->id)->first();
            if (!$doctorModel) continue;

            $scheduledDays = (array) array_rand(array_flip($days), rand(2, 4));

            foreach ($scheduledDays as $day) {
                DoctorSchedule::create([
                    'doctor_id' => $doctorModel->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * @param User[] $doctors
     * @param User[] $patients
     */
    private function createAppointments(array $doctors, array $patients, int $count): void
    {
        $statuses = ['pending', 'requested', 'set', 'accepted', 'completed', 'cancelled'];
        $reasons = ['Checkup', 'Follow-up', 'Consultation', 'Lab results', 'Prescription refill', 'Urgent care'];

        for ($i = 0; $i < $count; $i++) {
            $doctor = $doctors[array_rand($doctors)];
            $patient = $patients[array_rand($patients)];
            $doctorModel = Doctor::where('user_id', $doctor->id)->first();

            $date = date('Y-m-d', strtotime('-' . rand(1, 60) . ' days'));
            $startHour = rand(9, 16);
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = sprintf('%02d:30', $startHour + (rand(0, 1) ? 0 : 1));

            \App\Domains\Appointments\Models\Appointment::create([
                'doctor_id' => $doctorModel?->id,
                'patient_id' => Patient::where('user_id', $patient->id)->first()?->id,
                'appointment_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $statuses[array_rand($statuses)],
                'reason' => $reasons[array_rand($reasons)],
                'created_by' => $patient->id,
            ]);
        }
    }

    /**
     * @param User[] $patients
     * @param User[] $doctors
     */
    private function createRatings(array $patients, array $doctors, int $count): void
    {
        $created = 0;
        $attempts = 0;
        $maxAttempts = $count * 3;

        while ($created < $count && $attempts < $maxAttempts) {
            $attempts++;
            $rater = $patients[array_rand($patients)];
            $target = $doctors[array_rand($doctors)];

            Rating::firstOrCreate(
                [
                    'rater_id' => $rater->id,
                    'type' => 'user',
                    'rateable_id' => $target->id,
                    'rateable_type' => 'App\Models\User',
                ],
                [
                    'rating' => rand(1, 5),
                    'comment' => 'K6 test rating',
                ]
            );

            $created++;
        }

        $this->info("Created {$created} ratings ({$attempts} attempts)");
    }

    /**
     * @param User[] $allUsers
     */
    private function createNotifications(array $allUsers, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $notification = Notification::create([
                'topic' => 'appointment',
                'title' => 'Test notification ' . ($i + 1),
                'body' => ['message' => 'K6 test notification body', 'type' => 'reminder'],
            ]);

            $userIds = array_map(fn($u) => $u->id, $allUsers);
            if (empty($userIds)) continue;
            $numRecipients = min(rand(1, 10), count($userIds));
            $keys = (array) array_rand($userIds, $numRecipients);
            $recipients = array_map(fn($k) => $userIds[$k], $keys);
            $notification->users()->attach($recipients, ['read_at' => rand(0, 1) ? now() : null]);
        }
    }

    /**
     * @param User[] $users
     * @return array<string, array{access_token: string, refresh_token: string, expires_in: int}>
     */
    private function generateTokens(array $users, AuthService $authService): array
    {
        $tokens = [];

        foreach ($users as $user) {
            try {
                $tokenData = $authService->issueToken(
                    LoginData::fromCredentials($user->email, self::PASSWORD)
                );

                $tokens[$user->email] = [
                    'access_token' => $tokenData->accessToken,
                    'refresh_token' => $tokenData->refreshToken,
                    'expires_in' => $tokenData->expiresIn,
                ];
            } catch (\Exception $e) {
                $this->warn("Failed to generate token for {$user->email}: {$e->getMessage()}");
            }
        }

        return $tokens;
    }

    /**
     * @param array $tokens
     * @param User[] $doctors
     * @param User[] $patients
     * @param User[] $receptionists
     * @param User[] $admins
     */
    private function saveOutputFiles(array $tokens, array $doctors, array $patients, array $receptionists, array $admins): void
    {
        $dataDir = dirname(self::TOKENS_PATH);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // Save tokens grouped by role
        $tokenOutput = [
            'password' => self::PASSWORD,
            'tokens' => $tokens,
        ];

        file_put_contents(
            self::TOKENS_PATH,
            json_encode($tokenOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Save test data IDs
        $dataOutput = [
            'doctors' => array_map(fn($u) => [
                'id' => $u->id,
                'user_id' => $u->id,
                'email' => $u->email,
            ], $doctors),
            'patients' => array_map(fn($u) => [
                'id' => $u->id,
                'user_id' => $u->id,
                'email' => $u->email,
            ], $patients),
            'receptionists' => array_map(fn($u) => [
                'id' => $u->id,
                'user_id' => $u->id,
                'email' => $u->email,
            ], $receptionists),
            'admins' => array_map(fn($u) => [
                'id' => $u->id,
                'user_id' => $u->id,
                'email' => $u->email,
            ], $admins),
        ];

        file_put_contents(
            self::DATA_PATH,
            json_encode($dataOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
