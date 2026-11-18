<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\SpecialtyController;

Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {

        // Specialty Management
        Route::get('specialties', [SpecialtyController::class, 'adminIndex']);
        Route::get('specialties/{specialty}', [SpecialtyController::class, 'adminShow']);
        Route::post('specialties', [SpecialtyController::class, 'store']);
        Route::match(['put', 'patch'], 'specialties/{specialty}', [SpecialtyController::class, 'update']);
        Route::patch('specialties/{specialty}/deactivate', [SpecialtyController::class, 'deactivate']);
        Route::patch('specialties/{specialty}/activate', [SpecialtyController::class, 'activate']);

        // Clinic Management
        Route::get('clinics', [ClinicController::class, 'adminIndex']);
        Route::get('clinics/{clinic}', [ClinicController::class, 'adminShow']);
        Route::post('clinics', [ClinicController::class, 'store']);
        Route::match(['put', 'patch'], 'clinics/{clinic}', [ClinicController::class, 'update']);
        Route::patch('clinics/{clinic}/deactivate', [ClinicController::class, 'deactivate']);
        Route::patch('clinics/{clinic}/activate', [ClinicController::class, 'activate']);

        // Patient management
        Route::get('patients', [PatientController::class, 'adminIndex']);
        Route::get('patients/{patient}', [PatientController::class, 'adminShow']);
        Route::post('patients', [PatientController::class, 'store']);
        Route::patch('patients/{patient}', [PatientController::class, 'update']);
        Route::patch('patients/{patient}/status', [PatientController::class, 'updateStatus']);

    });
    