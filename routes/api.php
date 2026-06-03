<?php

use App\Domains\Appointments\Controllers\AppointmentController;
use App\Domains\Dashboard\Controllers\DashboardController;
use App\Domains\MedicalRecords\Controllers\MedicalRecordController;
use App\Domains\Notifications\Controllers\NotificationController;
use App\Domains\RBAC\Controllers\PermissionController;
use App\Domains\RBAC\Controllers\RoleController;
use App\Domains\RBAC\Controllers\UserRoleController;
use App\Domains\Supervisions\Controllers\SupervisionController;
use App\Domains\Supervisions\Controllers\SupervisionRequestController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Doctor\DoctorController;
use App\Http\Controllers\Api\V1\Image\ImageController;
use App\Http\Controllers\Api\V1\Patient\PatientController;
use App\Http\Controllers\Api\V1\Receptionist\ReceptionistController;
use App\Domains\Doctors\Controllers\SpecializationController;
use App\Domains\Prescriptions\Controllers\MedicineController;
use App\Domains\Prescriptions\Controllers\PrescriptionController;
use App\Domains\Prescriptions\Controllers\PrescriptionItemController;
use App\Domains\Ratings\Controllers\AppRatingController;
use App\Domains\Ratings\Controllers\RatingController;
use App\Http\Controllers\Api\V1\Location\CityController;
use App\Http\Controllers\Api\V1\Location\CountryController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register/patient', [AuthController::class, 'registerPatient'])
        ->middleware(['throttle:register', 'image.content']);
    Route::post('/register/doctor', [AuthController::class, 'registerDoctor'])
        ->middleware(['throttle:register', 'image.content']);
    Route::post('/register/receptionist', [AuthController::class, 'registerReceptionist'])
        ->middleware(['throttle:register', 'image.content']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::post('/firebase-token', [AuthController::class, 'firebaseToken'])->middleware(['auth:api', 'active']);

    Route::middleware(['auth:api', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::delete('/account', [AuthController::class, 'deleteAccount']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile'])->middleware('image.content');
    });
});

Route::prefix('v1/receptionists')->group(function () {
    Route::get('/', [ReceptionistController::class, 'index']);
    Route::get('/{receptionist}', [ReceptionistController::class, 'show']);
});

Route::middleware(['auth:api', 'active', 'staff:doctor'])->prefix('v1/patients')->group(function () {
    Route::get('/', [PatientController::class, 'index']);
    Route::get('/{patient}', [PatientController::class, 'show']);
});

Route::prefix('v1/countries')->group(function () {
    Route::get('/', [CountryController::class, 'index']);
    Route::get('/{country}', [CountryController::class, 'show']);
});

Route::prefix('v1/cities')->group(function () {
    Route::get('/', [CityController::class, 'index']);
    Route::get('/{city}', [CityController::class, 'show']);
});

Route::middleware(['auth:api', 'active'])->group(function () {
    Route::prefix('v1/doctors')->group(function () {
        Route::get('/', [DoctorController::class, 'index']);
        Route::get('/{doctor}', [DoctorController::class, 'show']);
        Route::get('/{doctor}/ratings', [DoctorController::class, 'ratings']);
    });

    Route::get('/v1/doctors/{doctor}/booked-slots', [AppointmentController::class, 'bookedSlots']);

    Route::get('/v1/doctors/{doctor}/appointments', [AppointmentController::class, 'doctorAppointments']);
    Route::get('/v1/doctors/{doctor}/patients', [SupervisionController::class, 'doctorPatients']);
    Route::get('/v1/patients/{patient}/doctors', [SupervisionController::class, 'patientDoctors']);

    Route::get('/v1/patients/{patient}/available-doctors', [SupervisionController::class, 'availableDoctors']);

    Route::post('/v1/doctors/{doctor}/patients/self', [SupervisionController::class, 'selfAssign']);

    Route::prefix('v1/patients/{patient}/supervision-requests')->group(function () {
        Route::post('/', [SupervisionRequestController::class, 'store']);
        Route::get('/', [SupervisionRequestController::class, 'indexPatient']);
    });

    Route::prefix('v1/doctors/{doctor}/supervision-requests')->group(function () {
        Route::get('/', [SupervisionRequestController::class, 'indexDoctor']);
    });

    Route::prefix('v1/supervision-requests/{supervision_request}')->group(function () {
        Route::post('/approve', [SupervisionRequestController::class, 'approve']);
        Route::post('/reject', [SupervisionRequestController::class, 'reject']);
        Route::post('/cancel', [SupervisionRequestController::class, 'cancel']);
    });

    Route::middleware('staff')->group(function () {
        Route::post('/v1/doctors/{doctor}/patients', [SupervisionController::class, 'assign']);
        Route::post('/v1/doctors/{doctor}/patients/bulk', [SupervisionController::class, 'bulkAssign']);
    });

    Route::delete('/v1/doctors/{doctor}/patients/{patient}', [SupervisionController::class, 'remove']);

    Route::delete('/v1/patients/{patient}/doctors/{doctor}', [SupervisionController::class, 'patientRemoveDoctor']);

    Route::post('/v1/device-tokens', [DeviceTokenController::class, 'update']);

    Route::get('/v1/dashboard', DashboardController::class);

    Route::prefix('v1/roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{role}', [RoleController::class, 'show']);
    });

    Route::prefix('v1/permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('/{permission}', [PermissionController::class, 'show']);
    });

    Route::prefix('v1/users/{user}/roles')->group(function () {
        Route::get('/', [UserRoleController::class, 'getUserRoles']);
    });

    Route::prefix('v1/specializations')->group(function () {
        Route::get('/', [SpecializationController::class, 'index']);
        Route::get('/{specialization}', [SpecializationController::class, 'show']);
    });

    Route::get('v1/app-ratings', [AppRatingController::class, 'index']);

    Route::prefix('v1/ratings')->group(function () {
        Route::get('/', [RatingController::class, 'index']);
        Route::get('/{rating}', [RatingController::class, 'show']);
        Route::post('/', [RatingController::class, 'store']);
        Route::put('/{rating}', [RatingController::class, 'update']);
        Route::delete('/{rating}', [RatingController::class, 'destroy']);
    });

    Route::prefix('v1/medicines')->group(function () {
        Route::get('/', [MedicineController::class, 'index']);
        Route::get('/{medicine}', [MedicineController::class, 'show']);
        Route::post('/', [MedicineController::class, 'store']);

        Route::middleware('staff:doctor')->group(function () {
            Route::put('/{medicine}', [MedicineController::class, 'update']);
            Route::delete('/{medicine}', [MedicineController::class, 'destroy']);
        });
    });

    Route::get('/v1/medical-records/{medical_record}/prescriptions', [PrescriptionController::class, 'index']);
    Route::post('/v1/medical-records/{medical_record}/prescriptions', [PrescriptionController::class, 'store']);
    Route::get('/v1/prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::put('/v1/prescriptions/{prescription}', [PrescriptionController::class, 'update']);
    Route::delete('/v1/prescriptions/{prescription}', [PrescriptionController::class, 'destroy']);

    Route::get('/v1/prescriptions/{prescription}/items', [PrescriptionItemController::class, 'index']);
    Route::post('/v1/prescriptions/{prescription}/items', [PrescriptionItemController::class, 'store']);
    Route::get('/v1/prescription-items/{prescription_item}', [PrescriptionItemController::class, 'show']);
    Route::put('/v1/prescription-items/{prescription_item}', [PrescriptionItemController::class, 'update']);
    Route::delete('/v1/prescription-items/{prescription_item}', [PrescriptionItemController::class, 'destroy']);

    Route::middleware('admin')->group(function () {
        Route::prefix('v1/countries')->group(function () {
            Route::post('/', [CountryController::class, 'store']);
            Route::put('/{country}', [CountryController::class, 'update']);
            Route::delete('/{country}', [CountryController::class, 'destroy']);
        });

        Route::prefix('v1/cities')->group(function () {
            Route::post('/', [CityController::class, 'store']);
            Route::put('/{city}', [CityController::class, 'update']);
            Route::delete('/{city}', [CityController::class, 'destroy']);
        });

        Route::prefix('v1/specializations')->group(function () {
            Route::post('/', [SpecializationController::class, 'store']);
            Route::put('/{specialization}', [SpecializationController::class, 'update']);
            Route::delete('/{specialization}', [SpecializationController::class, 'destroy']);
        });

        Route::prefix('v1/doctors')->group(function () {
            Route::post('/', [DoctorController::class, 'store'])->middleware('image.content');
            Route::put('/{doctor}', [DoctorController::class, 'update']);
            Route::patch('/{doctor}', [DoctorController::class, 'updatePartial']);
            Route::delete('/{doctor}', [DoctorController::class, 'destroy']);
            Route::put('/{doctor}/activate-account', [DoctorController::class, 'activateAccount']);
        });

        Route::prefix('v1/receptionists')->group(function () {
            Route::post('/', [ReceptionistController::class, 'store'])->middleware('image.content');
            Route::put('/{receptionist}', [ReceptionistController::class, 'update']);
            Route::patch('/{receptionist}', [ReceptionistController::class, 'updatePartial']);
            Route::delete('/{receptionist}', [ReceptionistController::class, 'destroy']);
            Route::put('/{receptionist}/activate-account', [ReceptionistController::class, 'activateAccount']);
        });

        Route::prefix('v1/patients')->group(function () {
            Route::post('/', [PatientController::class, 'store'])->middleware('image.content');
            Route::put('/{patient}', [PatientController::class, 'update']);
            Route::patch('/{patient}', [PatientController::class, 'updatePartial']);
            Route::delete('/{patient}', [PatientController::class, 'destroy']);
        });

        Route::prefix('v1/users')->group(function () {
            Route::get('/', [\App\Domains\Users\Controllers\UserController::class, 'index']);
            Route::get('/{user}', [\App\Domains\Users\Controllers\UserController::class, 'show']);
            Route::put('/{user}', [\App\Domains\Users\Controllers\UserController::class, 'update']);
            Route::put('/{user}/toggle-active', [\App\Domains\Users\Controllers\UserController::class, 'toggleActive']);
            Route::delete('/{user}', [\App\Domains\Users\Controllers\UserController::class, 'destroy']);
        });

        Route::post('/v1/medical-records/{medical_record}/transfer', [MedicalRecordController::class, 'transfer']);
    });

    Route::prefix('v1/images')->group(function () {
        Route::get('/{image}', [ImageController::class, 'show']);
        Route::post('/', [ImageController::class, 'store'])->middleware('image.content');
        Route::delete('/{image}', [ImageController::class, 'destroy']);
    });

        Route::prefix('v1/roles')->group(function () {
            Route::post('/', [RoleController::class, 'store']);
            Route::put('/{role}', [RoleController::class, 'update']);
            Route::delete('/{role}', [RoleController::class, 'destroy']);
            Route::post('/{role}/permissions', [RoleController::class, 'syncPermissions']);
        });

        Route::prefix('v1/permissions')->group(function () {
            Route::post('/', [PermissionController::class, 'store']);
            Route::put('/{permission}', [PermissionController::class, 'update']);
            Route::delete('/{permission}', [PermissionController::class, 'destroy']);
        });

        Route::prefix('v1/users/{user}/roles')->group(function () {
            Route::post('/', [UserRoleController::class, 'syncUserRoles']);
        });

        Route::prefix('v1/appointments')->group(function () {
            Route::get('/', [AppointmentController::class, 'index']);
            Route::get('/{appointment}', [AppointmentController::class, 'show']);
            Route::post('/', [AppointmentController::class, 'store']);
            Route::post('/{appointment}/respond', [AppointmentController::class, 'respond']);
            Route::post('/{appointment}/set-time', [AppointmentController::class, 'setTime']);
            Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel']);
            Route::post('/{appointment}/start', [AppointmentController::class, 'start']);
            Route::post('/{appointment}/complete', [AppointmentController::class, 'complete']);
            Route::post('/{appointment}/suggest-alternative', [AppointmentController::class, 'suggestAlternative']);
        });

        Route::prefix('v1/notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/read', [NotificationController::class, 'markMultipleAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/', [NotificationController::class, 'destroyMultiple']);
            Route::delete('/all', [NotificationController::class, 'destroyAll']);
            Route::get('/{notification}', [NotificationController::class, 'show'])->whereUuid('notification');
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->whereUuid('notification');
            Route::delete('/{notification}', [NotificationController::class, 'destroy'])->whereUuid('notification');
        });
    });
