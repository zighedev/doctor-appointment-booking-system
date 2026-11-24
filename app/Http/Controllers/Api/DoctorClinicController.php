<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorClinic\AttachClinicRequest;
use App\Http\Requests\Admin\DoctorClinic\UpdateClinicRequest;
use App\Http\Resources\Doctor\DoctorAdminResource;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;

class DoctorClinicController extends Controller
{
    public function attach(AttachClinicRequest $request, Doctor $doctor): DoctorAdminResource
    {
        $doctor->clinics()->attach(
            $request->validated()['clinic_id'],
            [
                'consultation_fee' => $request->validated()['consultation_fee'],
            ]
        );

        $doctor->load(['user', 'specialties', 'clinics']);

        return new DoctorAdminResource($doctor);
    }

    public function update(UpdateClinicRequest $request, Doctor $doctor, Clinic $clinic): JsonResponse
    {
        if (! $doctor->clinics()->whereKey($clinic->id)->exists()) {
            return response()->json([
                'message' => 'This clinic is not assigned to this doctor.',
            ], 404);
        }

        $doctor->clinics()->updateExistingPivot($clinic->id, $request->validated());

        return response()->json([
            'message' => 'Doctor-clinic relationship updated successfully.',
        ]);
    }

    public function detach(Doctor $doctor, Clinic $clinic): JsonResponse
    {
        $hasAppointments = Appointment::whereHas('appointmentSlot', 
            function($query) use ($doctor, $clinic){
                $query->where('clinic_id', $clinic->id)
                      ->where('doctor_id', $doctor->id);
            })->exists();

        if($hasAppointments){
             return response()->json([
                'message' => 'there are appointments exists.',
            ]);
        }

        // delete empty slots.
        AppointmentSlot::where('clinic_id', $clinic->id)
                       ->where('doctor_id', $doctor->id)
                       ->delete();

        $doctor->clinics()->detach($clinic->id);

        return response()->json([
            'message' => 'Clinic detached successfully.',
        ]);
    }

}
