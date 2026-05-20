<?php

use App\Domains\Appointments\Controllers\AppointmentController;
use App\Domains\RBAC\Controllers\PermissionController;
use App\Domains\RBAC\Controllers\RoleController;
use App\Domains\RBAC\Controllers\UserRoleController;
use App\Domains\Supervisions\Controllers\SupervisionController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Doctor\DoctorController;
use App\Http\Controllers\Api\V1\Image\ImageController;
use App\Http\Controllers\Api\V1\Patient\PatientController;
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

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::delete('/account', [AuthController::class, 'deleteAccount']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile'])->middleware('image.content');
    });
});

Route::prefix('v1/doctors')->group(function () {
    Route::get('/', [DoctorController::class, 'index']);
    Route::get('/{doctor}', [DoctorController::class, 'show']);
});

Route::middleware(['auth:api', 'staff'])->prefix('v1/patients')->group(function () {
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

Route::get('/v1/doctors/{doctor}/available-slots', [AppointmentController::class, 'availableSlots']);

Route::middleware('auth:api')->group(function () {
    Route::get('/v1/doctors/{doctor}/patients', [SupervisionController::class, 'doctorPatients']);
    Route::get('/v1/patients/{patient}/doctors', [SupervisionController::class, 'patientDoctors']);

    Route::middleware('staff')->group(function () {
        Route::post('/v1/doctors/{doctor}/patients', [SupervisionController::class, 'assign']);
        Route::delete('/v1/doctors/{doctor}/patients/{patient}', [SupervisionController::class, 'remove']);
    });

    Route::post('/v1/device-tokens', [DeviceTokenController::class, 'update']);

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

        Route::prefix('v1/doctors')->group(function () {
            Route::put('/{doctor}', [DoctorController::class, 'update']);
            Route::patch('/{doctor}', [DoctorController::class, 'updatePartial']);
            Route::delete('/{doctor}', [DoctorController::class, 'destroy']);
        });

        Route::prefix('v1/patients')->group(function () {
            Route::put('/{patient}', [PatientController::class, 'update']);
            Route::patch('/{patient}', [PatientController::class, 'updatePartial']);
            Route::delete('/{patient}', [PatientController::class, 'destroy']);
        });
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
        Route::post('/{appointment}/complete', [AppointmentController::class, 'complete']);
        Route::post('/{appointment}/suggest-alternative', [AppointmentController::class, 'suggestAlternative']);
    });
});
