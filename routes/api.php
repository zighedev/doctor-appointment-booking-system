<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpecialtyController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () { 
    Route::post('/logout', [AuthController::class, 'logout']); 
    Route::get('/user', function (Request $request){ 
            return $request->user(); 
        }); 
    });

Route::middleware('auth:sanctum')->group(function () {

    // Publicly available to authenticated users.
    Route::get('/specialties', [SpecialtyController::class, 'index']);
    Route::get('/specialties/{specialty}', [SpecialtyController::class, 'show']);

    // Admin only.
    Route::middleware('role:admin')->group(function () {
        Route::post('/specialties', [SpecialtyController::class, 'store']);
        Route::put('/specialties/{specialty}', [SpecialtyController::class, 'update']);
        Route::patch('/specialties/{specialty}', [SpecialtyController::class, 'update']);

        Route::patch('/specialties/{specialty}/deactivate', [SpecialtyController::class, 'deactivate']);
        Route::patch('/specialties/{specialty}/activate', [SpecialtyController::class, 'activate']);
    });

});
