<?php

use App\Http\Controllers\Api\DoctorController;

Route::middleware(['auth:sanctum', 'role:doctor'])
    ->prefix('doctor')
    ->group(function () {

        Route::get('profile', [DoctorController::class, 'myProfile']);
        Route::patch('profile', [DoctorController::class, 'updateMyProfile']);
        Route::get('clinics', [DoctorController::class, 'clinics']);
        Route::patch(
            'appointment-availability',
            [DoctorController::class, 'updateAppointmentAvailability']
        );

    });
    