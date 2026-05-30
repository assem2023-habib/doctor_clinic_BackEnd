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
        $users = User::selectRaw("
            COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                SUM(CASE WHEN YEARWEEK(created_at) = YEARWEEK(CURDATE()) THEN 1 ELSE 0 END) as new_this_week,
                SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as new_this_month
        ")->first();

        $usersByRole = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'name');

        $appointments = Appointment::selectRaw("
            COUNT(*) as total,
                SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN YEARWEEK(appointment_date) = YEARWEEK(CURDATE()) THEN 1 ELSE 0 END) as this_week,
                SUM(CASE WHEN YEAR(appointment_date) = YEAR(CURDATE()) AND MONTH(appointment_date) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as this_month
        ")->first();

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

        $ratings = DB::table('ratings')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        return [
            'users' => [
                'total' => (int) $users->total,
                'doctors' => (int) ($usersByRole['Doctor'] ?? 0),
                'patients' => (int) ($usersByRole['Patient'] ?? 0),
                'receptionists' => (int) ($usersByRole['Receptionist'] ?? 0),
                'admins' => (int) ($usersByRole['Admin'] ?? 0),
                'active' => (int) $users->active,
                'inactive' => (int) $users->inactive,
                'new_today' => (int) $users->new_today,
                'new_this_week' => (int) $users->new_this_week,
                'new_this_month' => (int) $users->new_this_month,
            ],
            'appointments' => [
                'total' => (int) $appointments->total,
                'today' => (int) $appointments->today,
                'this_week' => (int) $appointments->this_week,
                'this_month' => (int) $appointments->this_month,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'medical_records' => ['total' => $medicalRecords],
            'prescriptions' => ['total' => $prescriptions],
            'specializations' => [
                'total' => $specializationsTotal,
                'top' => $topSpecializations,
            ],
            'ratings' => [
                'average' => $ratings->average ? round((float) $ratings->average, 2) : 0,
                'total' => (int) $ratings->total,
            ],
        ];
    }

    public function forDoctor(User $user): array
    {
        $doctor = $user->doctor;

        if (!$doctor) {
            return [];
        }

        $doctorId = $doctor->id;

        $patientsCount = DB::table('doctor_patient')
            ->where('doctor_id', $doctorId)
            ->where('supervision_status', 'active')
            ->count();

        $newPatientsThisMonth = DB::table('doctor_patient')
            ->where('doctor_id', $doctorId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->selectRaw("
                COUNT(*) as total,
                    SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN appointment_date >= CURDATE() AND status != 'completed' AND status != 'cancelled' THEN 1 ELSE 0 END) as upcoming
            ")->first();

        $appointmentsByStatus = Appointment::where('doctor_id', $doctorId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $medicalRecords = MedicalRecord::where('doctor_id', $doctorId)->count();
        $prescriptions = Prescription::whereHas('medicalRecord', fn ($q) => $q->where('doctor_id', $doctorId))->count();

        $ratings = DB::table('ratings')
            ->where('rateable_id', $user->id)
            ->where('rateable_type', 'App\Models\User')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        return [
            'patients' => [
                'total' => $patientsCount,
                'new_this_month' => $newPatientsThisMonth,
            ],
            'appointments' => [
                'total' => (int) $appointments->total,
                'today' => (int) $appointments->today,
                'upcoming' => (int) $appointments->upcoming,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'medical_records' => ['total' => $medicalRecords],
            'prescriptions' => ['total' => $prescriptions],
            'ratings' => [
                'average' => $ratings->average ? round((float) $ratings->average, 2) : 0,
                'total' => (int) $ratings->total,
            ],
        ];
    }

    public function forPatient(User $user): array
    {
        $patient = $user->patient;

        if (!$patient) {
            return [];
        }

        $patientId = $patient->id;

        $doctorsCount = DB::table('doctor_patient')
            ->where('patient_id', $patientId)
            ->where('supervision_status', 'active')
            ->count();

        $appointments = Appointment::where('patient_id', $patientId)
            ->selectRaw("
                COUNT(*) as total,
                    SUM(CASE WHEN appointment_date >= CURDATE() AND status != 'completed' AND status != 'cancelled' THEN 1 ELSE 0 END) as upcoming
            ")->first();

        $appointmentsByStatus = Appointment::where('patient_id', $patientId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $medicalRecords = MedicalRecord::where('patient_id', $patientId)->count();

        $prescriptions = Prescription::whereHas('medicalRecord', fn ($q) => $q->where('patient_id', $patientId))->count();

        return [
            'doctors' => ['total' => $doctorsCount],
            'appointments' => [
                'total' => (int) $appointments->total,
                'upcoming' => (int) $appointments->upcoming,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'medical_records' => ['total' => $medicalRecords],
            'prescriptions' => ['total' => $prescriptions],
        ];
    }

    public function forReceptionist(User $user): array
    {
        $appointmentsToday = Appointment::whereDate('appointment_date', now()->toDateString())
            ->selectRaw("COUNT(*) as total")
            ->first();

        $appointmentsByStatus = Appointment::whereDate('appointment_date', now()->toDateString())
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $patientsRegisteredToday = User::whereHas('roles', fn ($q) => $q->where('slug', 'patient'))
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $totalPatients = User::whereHas('roles', fn ($q) => $q->where('slug', 'patient'))->count();
        $totalDoctors = User::whereHas('roles', fn ($q) => $q->where('slug', 'doctor'))->count();

        return [
            'appointments' => [
                'today_total' => (int) $appointmentsToday->total,
                'by_status' => $appointmentsByStatus->map(fn ($c, $s) => (int) $c)->toArray(),
            ],
            'patients' => [
                'registered_today' => $patientsRegisteredToday,
                'total' => $totalPatients,
            ],
            'doctors' => [
                'total' => $totalDoctors,
            ],
        ];
    }
}
