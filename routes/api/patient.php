<?php

use App\Http\Controllers\Api\PatientController;

Route::middleware(['auth:sanctum', 'role:patient'])
    ->prefix('patient')
    ->group(function () {

        Route::get('profile', [PatientController::class, 'myProfile']);
        Route::patch('profile', [PatientController::class, 'updateMyProfile']);

});
    