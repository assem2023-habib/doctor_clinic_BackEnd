<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Appointments\Models\Appointment;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Prescriptions\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function forAdmin(): array
    {
        $now = now();

        $users = User::selectRaw("
            COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ")->first();

        $newToday = User::whereDate('created_at', $now->toDateString())->count();
        $newThisWeek = User::whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count();
        $newThisMonth = User::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->count();

        $usersByRole = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'name');

        $appointmentsTotal = Appointment::count();
        $appointmentsToday = Appointment::whereDate('appointment_date', $now->toDateString())->count();
        $appointmentsThisWeek = Appointment::whereBetween('appointment_date', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count();
        $appointmentsThisMonth = Appointment::whereYear('appointment_date', $now->year)->whereMonth('appointment_date', $now->month)->count();

        $appointmentsByStatus = Appointment::selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $medicalRecords = MedicalRecord::count();
        $prescriptions = Prescription::count();
        $specializationsTotal = DB::table('specializations')->count();

        $topSpecializations = DB::table('specializations')
            ->leftJoin('doctors', 'specializations.id', '=', 'doctors.specialization_id')
            ->select('specializations.name', DB::raw('COUNT(doctors.id) as doctors_count'))
            ->groupBy('specializations.id', 'specializations.name')
            ->orderByDesc('doctors_count')
            ->limit(5)
            ->get()
            ->toArray();

        $ratings = $this->getGlobalRatings();

        return [
            'users' => [
                'total' => (int) $users->total,
                'doctors' => (int) ($usersByRole['Doctor'] ?? 0),
                'patients' => (int) ($usersByRole['Patient'] ?? 0),
                'receptionists' => (int) ($usersByRole['Receptionist'] ?? 0),
                'admins' => (int) ($usersByRole['Admin'] ?? 0),
                'active' => (int) $users->active,
                'inactive' => (int) $users->inactive,
                'new_today' => $newToday,
                'new_this_week' => $newThisWeek,
                'new_this_month' => $newThisMonth,
            ],
            'appointments' => [
                'total' => $appointmentsTotal,
                'today' => $appointmentsToday,
                'this_week' => $appointmentsThisWeek,
                'this_month' => $appointmentsThisMonth,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'medical_records' => ['total' => $medicalRecords],
            'prescriptions' => ['total' => $prescriptions],
            'specializations' => [
                'total' => $specializationsTotal,
                'top' => $topSpecializations,
            ],
            'ratings' => $ratings,
        ];
    }

    public function forDoctor(User $user): array
    {
        $doctor = $user->doctor;

        if (!$doctor) {
            return [];
        }

        $now = now();
        $doctorId = $doctor->id;

        $patientsCount = DB::table('doctor_patient')
            ->where('doctor_id', $doctorId)
            ->where('supervision_status', 'active')
            ->count();

        $newPatientsThisMonth = DB::table('doctor_patient')
            ->where('doctor_id', $doctorId)
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();

        $appointmentsTotal = Appointment::where('doctor_id', $doctorId)->count();
        $appointmentsToday = Appointment::where('doctor_id', $doctorId)->whereDate('appointment_date', $now->toDateString())->count();
        $appointmentsUpcoming = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', '>=', $now->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $appointmentsByStatus = Appointment::where('doctor_id', $doctorId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $medicalRecords = MedicalRecord::where('doctor_id', $doctorId)->count();
        $prescriptions = Prescription::whereHas('medicalRecord', fn ($q) => $q->where('doctor_id', $doctorId))->count();

        $ratings = DB::table('ratings')
            ->where('rateable_id', $user->id)
            ->where('rateable_type', 'App\Models\User')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total, SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative_count')
            ->first();

        return [
            'patients' => [
                'total' => $patientsCount,
                'new_this_month' => $newPatientsThisMonth,
            ],
            'appointments' => [
                'total' => $appointmentsTotal,
                'today' => $appointmentsToday,
                'upcoming' => $appointmentsUpcoming,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'medical_records' => ['total' => $medicalRecords],
            'prescriptions' => ['total' => $prescriptions],
            'ratings' => [
                'average' => $ratings->average ? round((float) $ratings->average, 2) : 0,
                'total' => (int) $ratings->total,
                'negative_count' => (int) ($ratings->negative_count ?? 0),
            ],
        ];
    }

    public function forPatient(User $user): array
    {
        $patient = $user->patient;

        if (!$patient) {
            return [];
        }

        $now = now();
        $patientId = $patient->id;

        $doctorsCount = DB::table('doctor_patient')
            ->where('patient_id', $patientId)
            ->where('supervision_status', 'active')
            ->count();

        $appointmentsTotal = Appointment::where('patient_id', $patientId)->count();
        $appointmentsUpcoming = Appointment::where('patient_id', $patientId)
            ->where('appointment_date', '>=', $now->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $appointmentsByStatus = Appointment::where('patient_id', $patientId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $medicalRecords = MedicalRecord::where('patient_id', $patientId)->count();

        $prescriptions = Prescription::whereHas('medicalRecord', fn ($q) => $q->where('patient_id', $patientId))->count();

        return [
            'doctors' => ['total' => $doctorsCount],
            'appointments' => [
                'total' => $appointmentsTotal,
                'upcoming' => $appointmentsUpcoming,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'medical_records' => ['total' => $medicalRecords],
            'prescriptions' => ['total' => $prescriptions],
        ];
    }

    public function forReceptionist(User $user): array
    {
        $now = now();

        $appointmentsToday = Appointment::whereDate('appointment_date', $now->toDateString())->count();

        $appointmentsByStatus = Appointment::whereDate('appointment_date', $now->toDateString())
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $patientsRegisteredToday = User::whereHas('roles', fn ($q) => $q->where('slug', 'patient'))
            ->whereDate('created_at', $now->toDateString())
            ->count();

        $totalPatients = User::whereHas('roles', fn ($q) => $q->where('slug', 'patient'))->count();
        $totalDoctors = User::whereHas('roles', fn ($q) => $q->where('slug', 'doctor'))->count();
        $medicalRecords = MedicalRecord::count();
        $prescriptions = Prescription::count();

        $ratings = $this->getGlobalRatings();

        return [
            'appointments' => [
                'today_total' => $appointmentsToday,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'patients' => [
                'registered_today' => $patientsRegisteredToday,
                'total' => $totalPatients,
            ],
            'doctors' => [
                'total' => $totalDoctors,
            ],
            'medical_records' => ['total' => $medicalRecords],
            'prescriptions' => ['total' => $prescriptions],
            'ratings' => $ratings,
        ];
    }

    private function getGlobalRatings(): array
    {
        $doctorRatings = DB::table('ratings')
            ->select([
                'ratings.rateable_id as doctor_id',
                'users.first_name',
                'users.last_name',
                DB::raw('ROUND(AVG(ratings.rating), 2) as average'),
                DB::raw('COUNT(ratings.id) as total'),
            ])
            ->join('users', 'users.id', '=', 'ratings.rateable_id')
            ->where('ratings.rateable_type', 'App\Models\User')
            ->where('ratings.type', 'user')
            ->groupBy('ratings.rateable_id', 'users.first_name', 'users.last_name')
            ->get()
            ->map(fn ($row) => (object) [
                'doctor_id' => $row->doctor_id,
                'doctor_name' => $row->first_name . ' ' . $row->last_name,
                'average' => (float) $row->average,
                'total' => (int) $row->total,
            ]);

        $topPositive = $doctorRatings->sortByDesc('average')->take(3)->values();
        $lowestPositive = $doctorRatings->sortBy('average')->take(3)->values();
        $mostRated = $doctorRatings->sortByDesc('total')->take(3)->values();

        $overall = DB::table('ratings')
            ->where('rateable_type', 'App\Models\User')
            ->where('type', 'user')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total, SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative_count')
            ->first();

        $topPerSpecialization = DB::select("
            SELECT ranked.specialization_id, ranked.specialization_name,
                   ranked.doctor_id, ranked.first_name, ranked.last_name, ranked.average, ranked.total
            FROM (
                SELECT
                    s.id AS specialization_id,
                    s.name AS specialization_name,
                    u.id AS doctor_id,
                    u.first_name,
                    u.last_name,
                    ROUND(AVG(r.rating), 2) AS average,
                    COUNT(r.id) AS total,
                    ROW_NUMBER() OVER (PARTITION BY s.id ORDER BY AVG(r.rating) DESC, COUNT(r.id) DESC) AS rn
                FROM ratings r
                INNER JOIN users u ON u.id = r.rateable_id
                INNER JOIN doctors d ON d.user_id = u.id
                INNER JOIN specializations s ON s.id = d.specialization_id
                WHERE r.rateable_type = 'App\Models\User'
                    AND r.type = 'user'
                GROUP BY s.id, s.name, u.id, u.first_name, u.last_name
            ) ranked
            WHERE ranked.rn = 1
            ORDER BY ranked.average DESC
        ");

        $topPerSpecialization = array_map(fn ($row) => [
            'specialization_id' => $row->specialization_id,
            'specialization_name' => $row->specialization_name,
            'doctor_id' => $row->doctor_id,
            'doctor_name' => $row->first_name . ' ' . $row->last_name,
            'average' => (float) $row->average,
            'total' => (int) $row->total,
        ], $topPerSpecialization);

        return [
            'average' => $overall->average ? round((float) $overall->average, 2) : 0,
            'total' => (int) $overall->total,
            'negative_count' => (int) ($overall->negative_count ?? 0),
            'top_positive' => $topPositive,
            'lowest_positive' => $lowestPositive,
            'most_rated' => $mostRated,
            'top_per_specialization' => $topPerSpecialization,
        ];
    }
}
