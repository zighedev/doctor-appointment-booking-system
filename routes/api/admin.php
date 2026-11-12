<?php

use Illuminate\Support\Facades\Route;
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

    });
    