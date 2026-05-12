<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register/patient', [AuthController::class, 'registerPatient']);
    Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
    Route::post('/register/receptionist', [AuthController::class, 'registerReceptionist']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::delete('/account', [AuthController::class, 'deleteAccount']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
