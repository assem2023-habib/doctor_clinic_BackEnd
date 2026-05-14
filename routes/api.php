<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Doctor\DoctorController;
use App\Http\Controllers\Api\V1\Image\ImageController;
use App\Http\Controllers\Api\V1\Patient\PatientController;
use App\Http\Controllers\Api\V1\Location\CityController;
use App\Http\Controllers\Api\V1\Location\CountryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register/patient', [AuthController::class, 'registerPatient'])->middleware('image.content');
    Route::post('/register/doctor', [AuthController::class, 'registerDoctor'])->middleware('image.content');
    Route::post('/register/receptionist', [AuthController::class, 'registerReceptionist'])->middleware('image.content');
    Route::post('/login', [AuthController::class, 'login']);
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

Route::middleware('auth:api')->group(function () {
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
});
