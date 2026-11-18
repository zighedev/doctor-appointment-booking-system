<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Http\Requests\Admin\Patient\PatientFilterRequest;
use App\Http\Requests\Admin\Patient\StorePatientRequest;
use App\Http\Requests\Admin\Patient\UpdatePatientRequest;
use App\Http\Requests\Patient\UpdateMyPatientProfileRequest;
use App\Http\Resources\PatientDetailsResource;
use App\Http\Resources\PatientResource;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /** 
     * Display the authenticated patient's profile. 
     */
    public function myProfile(Request $request): PatientDetailsResource
    {
        $patient = $request->user()->patient()->firstOrFail();

        $patient->load('user');

        return new PatientDetailsResource($patient);
    }

    /** 
     * Update the authenticated patient's profile. 
     */
    public function updateMyProfile(UpdateMyPatientProfileRequest $request): PatientDetailsResource
    {
        $patient = $request->user()->patient()->firstOrFail();

        $patient->update($request->validated());

        $patient->load('user');

        return new PatientDetailsResource($patient);
    }

    /**
     * Display all patients for admin management.
     */
    public function adminIndex(PatientFilterRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $patients = Patient::query()
            ->with('user:id,name,gender,is_active,created_at')
            ->when(
                isset($filters['registration_period']),
                fn ($query) => $query->filterByRegistrationPeriod($filters['registration_period'])
            )
            ->when(
                isset($filters['gender']),
                fn ($query) => $query->filterByGender($filters['gender'])
            )
            ->when(
                array_key_exists('status', $filters),
                fn ($query) => $query->filterByStatus($filters['status'])
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return PatientResource::collection($patients);
    }

    /**
     * Display a specific patient for admin management.
     */
    public function adminShow(Patient $patient): PatientDetailsResource
    {
        $patient->load('user');

        return new PatientDetailsResource($patient);
    }

    /** 
     * Create a new patient account and its associated patient profile.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $data = $request->validated();

        $patient = DB::transaction(function () use ($data) {

            // Create the user account.
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
            ]);

            // Assign the patient role internally.
            $user->role = UserRole::PATIENT;
            $user->save();

            // Create the patient profile.
            return $user->patient()->create([
                'blood_type' => $data['blood_type'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
        });

        $patient->load('user');

        return (new PatientDetailsResource($patient))->response()->setStatusCode(201);
    }

    /** 
     * Update a specific patient's profile by an administrator.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): PatientDetailsResource
    {
        $patient->update($request->validated());

        $patient->load('user');

        return new PatientDetailsResource($patient);
    }

    /** 
     * Activate or deactivate a patient's user account. 
     */
    public function updateStatus(UpdateStatusRequest $request, Patient $patient): JsonResponse
    {
        $isActive = $request->safe()->boolean('is_active');
        $patient->user->is_active = $isActive;
        $patient->user->save();

        return response()->json(
            [ 
                'message' => 'Patient account status updated successfully.',
                'is_active' => $isActive, 
            ]
        );                            
    }

}
