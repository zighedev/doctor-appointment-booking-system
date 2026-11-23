<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Http\Requests\Admin\Doctor\UpdateDoctorRequest;
use App\Http\Requests\Admin\Doctor\UpdateVerificationStatusRequest;
use App\Http\Requests\Admin\Doctor\DoctorFilterRequest;
use App\Http\Requests\Admin\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateAppointmentAvailabilityRequest;
use App\Http\Requests\Doctor\UpdateMyDoctorProfileRequest;
use App\Http\Resources\Doctor\DoctorAdminResource;
use App\Http\Resources\Doctor\DoctorClinicResource;
use App\Http\Resources\Doctor\DoctorProfileResource;
use App\Http\Resources\Doctor\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{

    public function myProfile(Request $request): DoctorProfileResource
    {
        $doctor = $request->user()->doctor()->firstOrFail();

        $doctor->load(['user', 'specialties', 'clinics']);

        return new DoctorProfileResource($doctor);
    }

    public function updateMyProfile(UpdateMyDoctorProfileRequest $request): DoctorProfileResource
    {
        $doctor = $request->user()->doctor()->firstOrFail();

        $doctor->update($request->validated());

        $doctor->refresh()->load(['user', 'specialties', 'clinics']);

        return new DoctorProfileResource($doctor);
    }

    /**
     * Display doctors available for public browsing by authenticated users.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $doctors = Doctor::query()
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->where('is_verified', true)
            ->with([
                'user:id,name',
                'specialties' => fn ($query) => $query->where('specialties.is_active', true),
                'clinics' => fn ($query) => $query
                    ->where('clinics.is_active', true)
                    ->wherePivot('is_active', true),
            ])
            ->when(
                $request->filled('specialty'),
                fn (Builder $query) => $query->whereHas(
                    'specialties',
                    fn (Builder $specialtyQuery) => $specialtyQuery
                        ->where('specialties.slug', $request->input('specialty'))
                        ->where('specialties.is_active', true)
                )
            )
            ->when(
                $request->filled('clinic'),
                fn (Builder $query) => $query->whereHas(
                    'clinics',
                    fn (Builder $clinicQuery) => $clinicQuery
                        ->where('clinics.id', $request->input('clinic'))
                        ->where('clinics.is_active', true)
                        ->where('clinic_doctor.is_active', true)
                )
            )
            ->when(
                $request->filled('name'),
                fn (Builder $query) => $query->whereHas(
                    'user',
                    fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', '%' . $request->input('name') . '%')
                )
            )
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return DoctorResource::collection($doctors);
    }

    /**
     * Display a verified doctor with an active user account.
     */
    public function show(Doctor $doctor): DoctorResource
    {
        abort_unless($doctor->is_verified && $doctor->user?->is_active, 404);

        $doctor->load([
            'user:id,name',
            'specialties' => fn ($query) => $query->where('specialties.is_active', true),
            'clinics' => fn ($query) => $query
                ->where('clinics.is_active', true)
                ->wherePivot('is_active', true),
        ]);

        return new DoctorResource($doctor);
    }

    /**
     * Display all clinics associated with the authenticated doctor.
     */
    public function clinics(Request $request): AnonymousResourceCollection
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        $clinics = $doctor->clinics()
            ->where('clinics.is_active', true)
            ->wherePivot('is_active', true)
            ->withPivot(['consultation_fee', 'is_active'])
            ->paginate(15);

        return DoctorClinicResource::collection($clinics);
    }

    /**
     * Display all doctors for admin management.
     */
    public function adminIndex(DoctorFilterRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $doctors = Doctor::query()
            ->with(['user', 'specialties', 'clinics'])
            ->when(
                isset($filters['name']),
                fn (Builder $query) => $query->filterByName($filters['name'])
            )
            ->when(
                isset($filters['specialty']),
                fn (Builder $query) => $query->whereHas(
                    'specialties',
                    fn (Builder $specialtyQuery) => $specialtyQuery
                        ->where('specialties.slug', $filters['specialty'])
                )
            )
            ->when(
                isset($filters['clinic']),
                fn (Builder $query) => $query->whereHas(
                    'clinics',
                    fn (Builder $clinicQuery) => $clinicQuery
                        ->where('clinics.id', $filters['clinic'])
                )
            )
            ->when(
                array_key_exists('verified', $filters),
                fn (Builder $query) => $query->filterByVerified($filters['verified'])
            )
            ->when(
                array_key_exists('accepting_appointments', $filters),
                fn (Builder $query) => $query->filterByAcceptingAppointments($filters['accepting_appointments'])
            )
            ->when(
                array_key_exists('status', $filters),
                fn (Builder $query) => $query->filterByStatus($filters['status'])
            )
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return DoctorAdminResource::collection($doctors);
    }

    /**
     * Display any doctor for admin management.
     */
    public function adminShow(Doctor $doctor): DoctorAdminResource
    {
        $doctor->load('user', 'specialties', 'clinics');

        return new DoctorAdminResource($doctor);
    }

    /**
     * Create a doctor account and its professional profile.
     */
    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $data = $request->validated();

        $doctor = DB::transaction(function () use ($data) {

            // Create the user account.
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
            ]);

            // Assign the doctor role internally.
            $user->role = UserRole::DOCTOR;
            $user->save();

            // Create the professional doctor profile.
            return $user->doctor()->create([
                'license_number' => $data['license_number'] ?? null,
                'bio' => $data['bio'] ?? null,
                'years_of_experience' => $data['years_of_experience'] ?? 0,
            ]);
        });

        $doctor->load('user', 'specialties', 'clinics');

        return (new DoctorAdminResource($doctor))->response()->setStatusCode(201);
    }

    /**
     * Update a doctor's professional profile.
     */
    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorAdminResource
    {
        $doctor->update($request->validated());

        $doctor->refresh()->load('user', 'specialties', 'clinics');

        return new DoctorAdminResource($doctor);
    }

    /** 
     * Activate or deactivate a doctor's user account. 
     */
    public function updateStatus(UpdateStatusRequest $request, Doctor $doctor): JsonResponse
    {
        $isActive = $request->safe()->boolean('is_active');
        
        $doctor->user()->update([
            'is_active' => $isActive,
        ]);


        return response()->json([ 
            'message' => 'Doctor account status updated successfully.',
            'is_active' => $isActive, 
        ]);                            
    }

    /** 
     * Update a doctor's verification status. 
     */
    public function updateVerificationStatus(UpdateVerificationStatusRequest $request, Doctor $doctor): JsonResponse
    {
        $doctor->is_verified = $request->safe()->boolean('is_verified');
        $doctor->save();

        return response()->json([ 
            'message' => 'Doctor verification status updated successfully.',
            'is_verified' => (bool) $doctor->is_verified, 
        ]);                            
    }

    /** 
     * Update the authenticated doctor's appointment availability. 
     */
    public function updateAppointmentAvailability(UpdateAppointmentAvailabilityRequest $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        abort_if(! $doctor, 404);

        $doctor->is_accepting_appointments = $request->safe()->boolean('is_accepting_appointments');
        $doctor->save();

        return response()->json([
            'message' => 'Appointment availability updated successfully.',
            'is_accepting_appointments' => (bool) $doctor->is_accepting_appointments,
        ]);                           
    }

}
