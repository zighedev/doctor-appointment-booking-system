<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\SpecialtyController;

Route::middleware('auth:sanctum')->group(function () {

    // Specialty listing and details.
    Route::get('specialties', [SpecialtyController::class, 'index']);
    Route::get('specialties/{specialty}', [SpecialtyController::class, 'show']);

    // Clinic listing and details.
    Route::get('clinics', [ClinicController::class, 'index']);
    Route::get('clinics/{clinic}', [ClinicController::class, 'show']);
    
});