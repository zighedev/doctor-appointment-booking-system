<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorSpecialty\AttachSpecialtyRequest;
use App\Http\Requests\Admin\DoctorSpecialty\SetPrimarySpecialtyRequest;
use App\Http\Resources\Doctor\DoctorAdminResource;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DoctorSpecialtyController extends Controller
{
    public function attach(AttachSpecialtyRequest $request, Doctor $doctor): DoctorAdminResource
    {
        $doctor->specialties()->attach($request->validated()['specialty_id']);

        $doctor->load(['user', 'specialties', 'clinics']);

        return new DoctorAdminResource($doctor);
    }

    public function detach(Doctor $doctor, Specialty $specialty): JsonResponse
    {
        $doctor->specialties()->detach($specialty->id);

        return response()->json([
            'message' => 'Specialty detached successfully.',
        ]);
    }

    public function setPrimary(SetPrimarySpecialtyRequest $request, Doctor $doctor, Specialty $specialty): JsonResponse
    {

        if (! $doctor->specialties()->whereKey($specialty->id)->exists()) {
            return response()->json([
                'message' => 'The specialty is not assigned to this doctor.',
            ], 422);
        }

        DB::transaction(function () use ($doctor, $specialty) {

            DB::table('doctor_specialty')
                ->where('doctor_id', $doctor->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $doctor->specialties()->updateExistingPivot($specialty->id, ['is_primary' => true]);
        });

        return response()->json(['message' => 'Primary specialty updated successfully.']);
    }

}
