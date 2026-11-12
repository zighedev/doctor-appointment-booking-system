<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SpecialtyController;

Route::middleware('auth:sanctum')->group(function () {

    // Specialty listing and details.
    Route::get('specialties', [SpecialtyController::class, 'index']);
    Route::get('specialties/{specialty}', [SpecialtyController::class, 'show']);

});