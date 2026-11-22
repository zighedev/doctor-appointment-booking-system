<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use RuntimeException;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    // Profile Tests
    public function test_doctor_can_view_own_profile(): void
    {
        $user = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/doctor/profile');

        $response->assertOk()
            ->assertJsonPath('data.id', $doctor->id)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_user_without_doctor_profile_cannot_view_doctor_profile(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $response = $this->actingAs($doctorUser)
            ->getJson('/api/doctor/profile');

        $response->assertNotFound();
    }

    public function test_doctor_can_update_own_profile(): void
    {
        $user = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/doctor/profile', [
                'bio' => 'Experienced cardiologist.',
                'years_of_experience' => 10,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.bio', 'Experienced cardiologist.')
            ->assertJsonPath('data.years_of_experience', 10);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'bio' => 'Experienced cardiologist.',
            'years_of_experience' => 10,
        ]);
    }

    public function test_doctor_cannot_update_protected_fields(): void
    {
        $user = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
            'is_verified' => false,
            'is_accepting_appointments' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/doctor/profile', [
                'is_verified' => true,
                'is_accepting_appointments' => false,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'is_verified' => false,
            'is_accepting_appointments' => true,
        ]);
    }

    public function test_doctor_cannot_update_profile_with_experience_above_80(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'years_of_experience' => 10,
        ]);

        $response = $this->actingAs($doctorUser)
            ->patchJson('/api/doctor/profile', [
                'years_of_experience' => 81,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('years_of_experience');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'years_of_experience' => 10,
        ]);
    }

    public function test_doctor_cannot_update_profile_with_negative_experience(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'years_of_experience' => 10,
        ]);

        $response = $this->actingAs($doctorUser)
            ->patchJson('/api/doctor/profile', [
                'years_of_experience' => -1,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('years_of_experience');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'years_of_experience' => 10,
        ]);
    }

    public function test_doctor_can_update_profile_with_80_years_of_experience(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'years_of_experience' => 10,
        ]);

        $response = $this->actingAs($doctorUser)
            ->patchJson('/api/doctor/profile', [
                'years_of_experience' => 80,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'years_of_experience' => 80,
        ]);
    }

    public function test_doctor_cannot_update_profile_with_license_number_longer_than_100_characters(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'license_number' => 'LIC-123',
        ]);

        $response = $this->actingAs($doctorUser)
            ->patchJson('/api/doctor/profile', [
                'license_number' => str_repeat('A', 101),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('license_number');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'license_number' => 'LIC-123',
        ]);
    }

    public function test_doctor_can_clear_license_number(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'license_number' => 'LIC-123',
        ]);

        $response = $this->actingAs($doctorUser)
            ->patchJson('/api/doctor/profile', [
                'license_number' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'license_number' => null,
        ]);
    }

    public function test_doctor_cannot_update_profile_with_another_doctors_license_number(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'license_number' => 'DOC-001',
        ]);

        $anotherDoctorUser = User::factory()->doctor()->create();
        Doctor::factory()->create([
            'user_id' => $anotherDoctorUser->id,
            'license_number' => 'DOC-002',
        ]);

        $response = $this->actingAs($doctorUser)->patchJson(
            '/api/doctor/profile',
            ['license_number' => 'DOC-002']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('license_number');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'license_number' => 'DOC-001',
        ]);
    }

    public function test_doctor_can_update_profile_with_the_same_license_number(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'license_number' => 'DOC-001',
        ]);

        $response = $this->actingAs($doctorUser)->patchJson(
            '/api/doctor/profile',
            ['license_number' => 'DOC-001']
        );

        $response->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'license_number' => 'DOC-001',
        ]);
    }

    public function test_doctor_cannot_update_profile_with_non_string_bio(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'bio' => 'Experienced doctor.',
        ]);

        $response = $this->actingAs($doctorUser)->patchJson(
            '/api/doctor/profile',
            ['bio' => ['invalid']]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('bio');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'bio' => 'Experienced doctor.',
        ]);
    }

    public function test_doctor_cannot_update_profile_with_non_numeric_experience(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'years_of_experience' => 10,
        ]);

        $response = $this->actingAs($doctorUser)->patchJson(
            '/api/doctor/profile',
            ['years_of_experience' => 'ten']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('years_of_experience');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'years_of_experience' => 10,
        ]);
    }

    public function test_doctor_can_clear_bio(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'bio' => 'Experienced doctor.',
        ]);

        $response = $this->actingAs($doctorUser)->patchJson(
            '/api/doctor/profile',
            ['bio' => null]
        );

        $response->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'bio' => null,
        ]);
    }

    public function test_doctor_cannot_update_profile_with_null_experience(): void
    {
        $doctorUser = User::factory()->doctor()->create();

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'years_of_experience' => 10,
        ]);

        $response = $this->actingAs($doctorUser)->patchJson(
            '/api/doctor/profile',
            ['years_of_experience' => null]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('years_of_experience');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'years_of_experience' => 10,
        ]);
    }

    public function test_patient_cannot_update_doctor_profile(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient, 'sanctum')
            ->patchJson('/api/doctor/profile', [
                'bio' => 'Unauthorized update.',
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_doctor_profile(): void
    {
        $response = $this->getJson('/api/doctor/profile');

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_update_doctor_profile(): void
    {
        $response = $this->patchJson('/api/doctor/profile', [
            'bio' => 'Unauthorized update.',
        ]);

        $response->assertUnauthorized();
    }

    // Public Doctor Listing & Details Tests
    public function test_authenticated_user_can_list_verified_doctors(): void
    {
        $doctor = Doctor::factory()->create([
            'is_verified' => true,
            'is_accepting_appointments' => true,
        ]);

        $response = $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson('/api/doctors')
            ->assertOk();
        
        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($doctor->id, $doctorIds);
    }

    public function test_unverified_doctors_are_excluded_from_listing(): void
    {
        $doctor = Doctor::factory()->create([
            'is_verified' => false,
            'is_accepting_appointments' => true,
        ]);

        $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson('/api/doctors')
            ->assertOk()
            ->assertJsonMissing([
                'license_number' => $doctor->license_number,
            ]);
    }

    public function test_inactive_doctor_account_is_excluded_from_public_listing(): void
    {
        $patient = User::factory()->patient()->create();

        $activeDoctorUser = User::factory()->doctor()->create(['is_active' => true]);
        $inactiveDoctorUser = User::factory()->doctor()->create(['is_active' => false]);

        $activeDoctor = Doctor::factory()->create([
            'user_id' => $activeDoctorUser->id,
            'is_verified' => true,
        ]);

        Doctor::factory()->create([
            'user_id' => $inactiveDoctorUser->id,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($patient)->getJson('/api/doctors');
        $response->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($activeDoctor->id, $doctorIds);
        $this->assertNotContains($inactiveDoctorUser->doctor->id, $doctorIds);
    }

    public function test_doctors_not_accepting_appointments_are_included_in_listing(): void
    {
        $doctor = Doctor::factory()->create([
            'is_verified' => true,
            'is_accepting_appointments' => false,
        ]);

        $response = $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson('/api/doctors')
            ->assertOk();
    
        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($doctor->id, $doctorIds);
    }

    public function test_public_doctor_listing_is_paginated(): void
    {
        $patient = User::factory()->patient()->create();

        for ($i = 0; $i < 16; $i++) {
            $user = User::factory()->doctor()->create(['is_active' => true]);

            Doctor::factory()->create([
                'user_id' => $user->id,
                'is_verified' => true,
            ]);
        }

        $response = $this->actingAs($patient)
            ->getJson('/api/doctors');

        $response->assertOk()
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonCount(15, 'data');
    }

    public function test_public_doctor_listing_preserves_filters_in_pagination(): void
    {
        $patient = User::factory()->patient()->create();

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 16; $i++) {
            $user = User::factory()->doctor()->create([
                'name' => "Verified Doctor {$i}",
                'is_active' => true
            ]);

            $doctor = Doctor::factory()->create([
                'user_id' => $user->id,
                'is_verified' => true,
            ]);

            $doctor->specialties()->attach($specialty->id);
        }

        $unverifiedUser = User::factory()->doctor()->create([
            'name' => 'Verified Doctor Unverified',
        ]);

        Doctor::factory()->create([
            'user_id' => $unverifiedUser->id,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($patient)
            ->getJson('/api/doctors?name=Verified&specialty=cardiology&page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('data.0.user.name', 'Verified Doctor 15')
            ->assertJsonPath('links.first', fn ($url) =>
                str_contains($url, 'name=Verified')
                && str_contains($url, 'specialty=cardiology')
            )
            ->assertJsonPath('links.last', fn ($url) =>
                str_contains($url, 'name=Verified')
                && str_contains($url, 'specialty=cardiology')
            );
    }

    public function test_public_doctor_listing_excludes_doctors_with_inactive_specialty_when_filtering_by_it(): void
    {
        $patient = User::factory()->patient()->create();

        $doctorUser = User::factory()->doctor()->create([
            'is_active' => true,
        ]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_verified' => true,
        ]);

        $specialty = Specialty::factory()->create([
            'slug' => 'cardiology',
            'is_active' => false,
        ]);

        $doctor->specialties()->attach($specialty->id);

        $response = $this->actingAs($patient)
            ->getJson('/api/doctors?specialty=cardiology');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_public_doctor_listing_excludes_doctors_with_inactive_clinic_relationship(): void
    {
        $patient = User::factory()->patient()->create();

        $doctorUser = User::factory()->doctor()->create([
            'is_active' => true,
        ]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_verified' => true,
        ]);

        $clinic = Clinic::factory()->create([
            'is_active' => true,
        ]);

        $doctor->clinics()->attach($clinic->id, [
            'is_active' => false,
            'consultation_fee' => 2000,
        ]);

        $response = $this->actingAs($patient)
            ->getJson("/api/doctors?clinic={$clinic->id}");

        $response->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_authenticated_user_can_view_verified_doctor_details(): void
    {
        $doctor = Doctor::factory()->create([
            'is_verified' => true,
            'is_accepting_appointments' => true,
        ]);

        $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson("/api/doctors/{$doctor->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $doctor->id);
    }

    public function test_unverified_doctor_details_return_not_found(): void
    {
        $doctor = Doctor::factory()->create([
            'is_verified' => false,
        ]);

        $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson("/api/doctors/{$doctor->id}")
            ->assertNotFound();
    }

    public function test_inactive_doctor_account_is_excluded_from_public_details(): void
    {
        $patient = User::factory()->patient()->create();

        $doctorUser = User::factory()->doctor()->create([
            'is_active' => false,
        ]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($patient)
            ->getJson("/api/doctors/{$doctor->id}");

        $response->assertNotFound();
    }

    public function test_doctor_details_exclude_inactive_specialties(): void
    {
        $patient = User::factory()->patient()->create();

        $doctorUser = User::factory()->doctor()->create(['is_active' => true]);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_verified' => true,
        ]);

        $activeSpecialty = Specialty::factory()->create(['is_active' => true]);
        $inactiveSpecialty = Specialty::factory()->create(['is_active' => false]);

        $doctor->specialties()->attach([
            $activeSpecialty->id,
            $inactiveSpecialty->id,
        ]);

        $response = $this->actingAs($patient)->getJson("/api/doctors/{$doctor->id}");

        $response->assertOk();
            
        $specialtyIds = collect($response->json('data.specialties'))->pluck('id')->all();

        $this->assertContains($activeSpecialty->id, $specialtyIds);
        $this->assertNotContains($inactiveSpecialty->id, $specialtyIds);
    }

    public function test_doctor_details_exclude_inactive_clinics(): void
    {
        $patient = User::factory()->patient()->create();

        $doctorUser = User::factory()->doctor()->create(['is_active' => true]);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_verified' => true,
        ]);

        $activeClinic = Clinic::factory()->create(['is_active' => true]);
        $inactiveClinic = Clinic::factory()->create(['is_active' => false]);

        $doctor->clinics()->attach([
            $activeClinic->id => [
                'is_active' => true,
                'consultation_fee' => 2000,
            ],
            $inactiveClinic->id => [
                'is_active' => true,
                'consultation_fee' => 2000,
            ],
        ]);

        $response = $this->actingAs($patient)->getJson("/api/doctors/{$doctor->id}");

        $response->assertOk();

        $clinicIds = collect($response->json('data.clinics'))->pluck('id')->all();

        $this->assertContains($activeClinic->id, $clinicIds);
        $this->assertNotContains($inactiveClinic->id, $clinicIds);
    }

    public function test_doctor_details_exclude_inactive_clinic_relationships(): void
    {
        $patient = User::factory()->patient()->create();

        $doctorUser = User::factory()->doctor()->create(['is_active' => true]);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_verified' => true,
        ]);

        $clinic = Clinic::factory()->create(['is_active' => true]);

        $doctor->clinics()->attach($clinic->id, [
            'is_active' => false,
            'consultation_fee' => 2000,
        ]);

        $response = $this->actingAs($patient)->getJson("/api/doctors/{$doctor->id}");

        $response->assertOk();

        $clinicIds = collect($response->json('data.clinics'))->pluck('id')->all();

        $this->assertNotContains($clinic->id, $clinicIds);
    }

    public function test_doctor_details_include_clinic_pivot_data(): void
    {
        $patient = User::factory()->patient()->create();

        $doctorUser = User::factory()->doctor()->create(['is_active' => true]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_verified' => true,
        ]);

        $clinic = Clinic::factory()->create(['is_active' => true]);

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 2000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->getJson("/api/doctors/{$doctor->id}");

        $response->assertOk()
            ->assertJsonPath('data.clinics.0.consultation_fee', 2000)
            ->assertJsonPath('data.clinics.0.is_relationship_active', true);
    }

    public function test_doctor_listing_can_be_filtered_by_specialty_slug(): void
    {
        $specialty = Specialty::factory()->create([
            'slug' => 'cardiology',
            'is_active' => true,
        ]);

        $doctor = Doctor::factory()->create([
            'is_verified' => true,
        ]);

        $doctor->specialties()->attach($specialty->id);

        $response = $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson('/api/doctors?specialty=cardiology')
            ->assertOk();
            
        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($doctor->id, $doctorIds);
    }

    public function test_doctor_listing_can_be_filtered_by_clinic(): void
    {
        $clinic = Clinic::factory()->create([
            'is_active' => true,
        ]);

        $doctor = Doctor::factory()->create([
            'is_verified' => true,
        ]);

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 2000,
            'is_active' => true,
        ]);

        $response = $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson("/api/doctors?clinic={$clinic->id}")
            ->assertOk();
        
        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($doctor->id, $doctorIds);
    }

    public function test_doctor_listing_can_be_searched_by_name(): void
    {
        $doctor = Doctor::factory()->create([
            'is_verified' => true,
        ]);

        $response = $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson('/api/doctors?name=' . urlencode($doctor->user->name))
            ->assertOk();
        
        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($doctor->id, $doctorIds);
    }

    public function test_guest_cannot_list_doctors(): void
    {
        $this->getJson('/api/doctors')
            ->assertUnauthorized();
    }

    // Doctor Clinics Tests
    public function test_doctor_can_view_own_clinics(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([ 'user_id' => $doctorUser->id ]);
        $clinicA = Clinic::factory()->create([ 'is_active' => true ]);
        $clinicB = Clinic::factory()->create([ 'is_active' => true ]);

        $doctor->clinics()->attach([ $clinicA->id, $clinicB->id ]);

        $this->actingAs($doctorUser, 'sanctum')
            ->getJson('/api/doctor/clinics')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_doctor_can_only_view_active_clinic_relationships(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([ 'user_id' => $doctorUser->id ]);
        $clinicA = Clinic::factory()->create([ 'is_active' => true ]);
        $clinicB = Clinic::factory()->create([ 'is_active' => true ]);
        $clinicC = Clinic::factory()->create([ 'is_active' => false ]);

        $doctor->clinics()->attach([
            $clinicA->id => ['is_active' => true],
            $clinicB->id => ['is_active' => false],
            $clinicC->id => ['is_active' => true],
        ]);

        $this->actingAs($doctorUser, 'sanctum')
            ->getJson('/api/doctor/clinics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $clinicA->id,
                'is_relationship_active' => true,
            ]);
    }

    public function test_patient_cannot_view_doctor_clinics(): void
    {
        $patientUser = User::factory()->patient()->create();

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/doctor/clinics')
            ->assertForbidden();
    }

    public function test_admin_cannot_view_doctor_clinics(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/doctor/clinics')
            ->assertForbidden();
    }

    public function test_guest_cannot_view_doctor_clinics(): void
    {
        $this->getJson('/api/doctor/clinics')
            ->assertUnauthorized();
    }

    // Admin Doctor Management Tests
    public function test_admin_can_list_all_doctors(): void
    {
        $verifiedDoctor = Doctor::factory()->create([
            'is_verified' => true,
        ]);

        $unverifiedDoctor = Doctor::factory()->create([
            'is_verified' => false,
        ]);

        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->getJson('/api/admin/doctors')
            ->assertOk()
            ->assertJsonFragment([
                'license_number' => $verifiedDoctor->license_number,
            ])
            ->assertJsonFragment([
                'license_number' => $unverifiedDoctor->license_number,
            ]);
    }

    public function test_admin_can_view_unverified_doctor_details(): void
    {
        $doctor = Doctor::factory()->create([
            'is_verified' => false,
        ]);

        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->getJson("/api/admin/doctors/{$doctor->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $doctor->id);
    }

    public function test_admin_can_filter_doctors_by_name(): void
    {
        $doctor1 = Doctor::factory()
            ->for(User::factory()->create(['name' => 'Amine']))
            ->create(); 
        $doctor2 = Doctor::factory()
            ->for(User::factory()->create(['name' => 'Mohammed']))
            ->create(); 

        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->getJson('/api/admin/doctors?name=Amine')
            ->assertOk()
            ->assertJsonFragment([
                'license_number' => $doctor1->license_number,
            ])
            ->assertJsonMissing([
                'license_number' => $doctor2->license_number,
            ]);
    }

    public function test_admin_can_filter_doctors_by_specialty(): void
    {
        $admin = User::factory()->admin()->create();

        $cardiology = Specialty::factory()->create(['slug' => 'cardiology']);
        $neurology = Specialty::factory()->create(['slug' => 'neurology']);

        $cardiologyDoctorUser = User::factory()->doctor()->create();
        $cardiologyDoctor = Doctor::factory()->create(['user_id' => $cardiologyDoctorUser->id]);
        $cardiologyDoctor->specialties()->attach($cardiology->id);

        $neurologyDoctorUser = User::factory()->doctor()->create();
        $neurologyDoctor = Doctor::factory()->create(['user_id' => $neurologyDoctorUser->id]);
        $neurologyDoctor->specialties()->attach($neurology->id);

        $response = $this->actingAs($admin)->getJson('/api/admin/doctors?specialty=cardiology');

        $response->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($cardiologyDoctor->id, $doctorIds);
        $this->assertNotContains($neurologyDoctor->id, $doctorIds);
    }

    public function test_admin_can_filter_doctors_by_clinic(): void
    {
        $admin = User::factory()->admin()->create();

        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();

        $doctorAUser = User::factory()->doctor()->create();
        $doctorA = Doctor::factory()->create(['user_id' => $doctorAUser->id]);
        $doctorA->clinics()->attach($clinicA->id);

        $doctorBUser = User::factory()->doctor()->create();
        $doctorB = Doctor::factory()->create(['user_id' => $doctorBUser->id]);
        $doctorB->clinics()->attach($clinicB->id);

        $response = $this->actingAs($admin)->getJson("/api/admin/doctors?clinic={$clinicA->id}");

        $response->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($doctorA->id, $doctorIds);
        $this->assertNotContains($doctorB->id, $doctorIds);
    }

    public function test_admin_can_filter_doctors_by_appointment_acceptance(): void
    {
        $acceptingDoctor = Doctor::factory()->create([
            'is_verified' => true,
            'is_accepting_appointments' => true,
        ]);

        $notAcceptingDoctor = Doctor::factory()->create([
            'is_verified' => true,
            'is_accepting_appointments' => false,
        ]);

        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->getJson('/api/admin/doctors?accepting_appointments=0')
            ->assertOk()
            ->assertJsonFragment([
                'license_number' => $notAcceptingDoctor->license_number,
            ])
            ->assertJsonMissing([
                'license_number' => $acceptingDoctor->license_number,
            ]);
    }

    public function test_admin_can_filter_doctors_by_inactive_status(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveDoctorUser = User::factory()->doctor()->create(['is_active' => false]);
        $inactiveDoctor = Doctor::factory()->create(['user_id' => $inactiveDoctorUser->id]);

        $activeDoctorUser = User::factory()->doctor()->create(['is_active' => true]);
        $activeDoctor = Doctor::factory()->create(['user_id' => $activeDoctorUser->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/doctors?status=0')
            ->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($inactiveDoctor->id, $doctorIds);
        $this->assertNotContains($activeDoctor->id, $doctorIds);
    }

    public function test_admin_can_filter_doctors_by_active_status(): void
    {
        $admin = User::factory()->admin()->create();
        $activeDoctorUser = User::factory()->doctor()->create(['is_active' => true]);
        $activeDoctor = Doctor::factory()->create(['user_id' => $activeDoctorUser->id]);
        $inactiveDoctorUser = User::factory()->doctor()->create(['is_active' => false]);
        $inactiveDoctor = Doctor::factory()->create(['user_id' => $inactiveDoctorUser->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/doctors?status=1')
            ->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($activeDoctor->id, $doctorIds);
        $this->assertNotContains($inactiveDoctor->id, $doctorIds);
    }

    public function test_admin_can_filter_doctors_by_unverified_status(): void
    {
        $admin = User::factory()->admin()->create();

        $unverifiedDoctorUser = User::factory()->doctor()->create();
        $unverifiedDoctor = Doctor::factory()->create([
            'user_id' => $unverifiedDoctorUser->id,
            'is_verified' => false,
        ]);

        $verifiedDoctorUser = User::factory()->doctor()->create();
        $verifiedDoctor = Doctor::factory()->create([
            'user_id' => $verifiedDoctorUser->id,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/doctors?verified=0')
            ->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($unverifiedDoctor->id, $doctorIds);
        $this->assertNotContains($verifiedDoctor->id, $doctorIds);
    }
    
    public function test_admin_can_filter_doctors_by_verified_status(): void
    {
        $admin = User::factory()->admin()->create();

        $verifiedDoctorUser = User::factory()->doctor()->create();
        $verifiedDoctor = Doctor::factory()->create([
            'user_id' => $verifiedDoctorUser->id,
            'is_verified' => true,
        ]);

        $unverifiedDoctorUser = User::factory()->doctor()->create();
        $unverifiedDoctor = Doctor::factory()->create([
            'user_id' => $unverifiedDoctorUser->id,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/doctors?verified=1')
            ->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($verifiedDoctor->id, $doctorIds);
        $this->assertNotContains($unverifiedDoctor->id, $doctorIds);
    }

    public function test_admin_can_combine_all_doctor_filters(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create(['slug' => 'cardiology']);
        $clinic = Clinic::factory()->create();

        $matchingUser = User::factory()->doctor()->create([
            'name' => 'Amine Zighed',
            'is_active' => true,
        ]);

        $matchingDoctor = Doctor::factory()->create([
            'user_id' => $matchingUser->id,
            'is_verified' => true,
            'is_accepting_appointments' => true,
        ]);

        $matchingDoctor->specialties()->attach($specialty->id);
        $matchingDoctor->clinics()->attach($clinic->id);

        $otherUser = User::factory()->doctor()->create([
            'name' => 'Amine Zighed',
            'is_active' => true,
        ]);

        $otherDoctor = Doctor::factory()->create([
            'user_id' => $otherUser->id,
            'is_verified' => true,
            'is_accepting_appointments' => true,
        ]);

        $otherDoctor->specialties()->attach($specialty->id);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/doctors?name=Amine&verified=1&accepting_appointments=1&status=1&specialty=cardiology&clinic={$clinic->id}");

        $response->assertOk();

        $doctorIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($matchingDoctor->id, $doctorIds);
        $this->assertNotContains($otherDoctor->id, $doctorIds);
    }

    public function test_admin_cannot_filter_doctors_with_invalid_name(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/doctors?name=' . str_repeat('A', 151))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_cannot_filter_doctors_by_nonexistent_specialty(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/doctors?specialty=nonexistent');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('specialty');
    }

    public function test_admin_cannot_filter_doctors_by_nonexistent_clinic(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/doctors?clinic=99999');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('clinic');
    }

    public function test_admin_cannot_filter_doctors_by_non_numeric_clinic_id(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/doctors?clinic=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('clinic');
    }

    public function test_admin_cannot_filter_doctors_with_invalid_boolean_filters(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/doctors?status=invalid&verified=invalid&accepting_appointments=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'verified', 'accepting_appointments']);
    }

    public function test_admin_doctor_listing_preserves_filters_in_pagination(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create(['slug' => 'cardiology']);

        for ($i = 0; $i < 16; $i++) {
            $user = User::factory()->doctor()->create([
                'name' => "Doctor {$i}",
                'is_active' => true,
            ]);

            $doctor = Doctor::factory()->create([
                'user_id' => $user->id,
                'is_verified' => true,
            ]);

            $doctor->specialties()->attach($specialty->id);
        }

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/doctors?specialty=cardiology&verified=1&page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonCount(1, 'data');

        $this->assertStringContainsString(
            'specialty=cardiology',
            $response->json('links.first')
        );
    }

    public function test_patient_cannot_access_admin_doctor_listing(): void
    {
        $this->actingAs(User::factory()->patient()->create(), 'sanctum')
            ->getJson('/api/admin/doctors')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_admin_doctor_listing(): void
    {
        $this->getJson('/api/admin/doctors')
            ->assertUnauthorized();
    }

    public function test_admin_can_create_doctor_with_user_account(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Dr. Ahmed',
            'email' => 'amine@example.com',
            'password' => 'password123',
            'phone' => '0551234567',
            'license_number' => 'LIC-100',
            'bio' => 'Cardiology specialist',
            'years_of_experience' => 10,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/doctors', $data)
            ->assertCreated()
            ->assertJsonPath('data.license_number', 'LIC-100')
            ->assertJsonPath('data.user.name', 'Dr. Ahmed');

        $this->assertDatabaseHas('users', [
            'email' => 'amine@example.com',
            'role' => 'doctor',
        ]);

        $this->assertDatabaseHas('doctors', [
            'license_number' => 'LIC-100',
            'years_of_experience' => 10,
        ]);
    }

    public function test_patient_cannot_create_doctor(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient, 'sanctum')
            ->postJson('/api/admin/doctors', [])
            ->assertForbidden();
    }

    public function test_admin_cannot_create_doctor_with_existing_email(): void
    {
        $admin = User::factory()->admin()->create();

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $data = [
            'name' => 'Dr. Ahmed',
            'email' => $existingUser->email,
            'password' => 'password123',
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/doctors', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_can_update_doctor_professional_information(): void
    {
        $admin = User::factory()->admin()->create();

        $doctor = Doctor::factory()->create([
            'license_number' => 'LIC-OLD',
            'years_of_experience' => 5,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}", [
                'license_number' => 'LIC-NEW',
                'bio' => 'Updated biography',
                'years_of_experience' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('data.license_number', 'LIC-NEW');

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'license_number' => 'LIC-NEW',
            'bio' => 'Updated biography',
            'years_of_experience' => 12,
        ]);
    }

    public function test_admin_can_update_doctor_without_changing_license_number(): void
    {
        $admin = User::factory()->admin()->create();

        $doctor = Doctor::factory()->create([
            'license_number' => 'LIC-100',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}", [
                'license_number' => 'LIC-100',
                'bio' => 'Updated biography',
            ])
            ->assertOk();
    }

    public function test_admin_cannot_update_doctor_with_another_doctors_license_number(): void
    {
        $admin = User::factory()->admin()->create();

        $doctor1 = Doctor::factory()->create([
            'license_number' => 'LIC-100',
        ]);

        $doctor2 = Doctor::factory()->create([
            'license_number' => 'LIC-200',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor2->id}", [
                'license_number' => $doctor1->license_number,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('license_number');
    }

    public function test_user_creation_is_rolled_back_if_doctor_creation_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $email = 'amine@example.com';

        // Simulate a failure when creating the Doctor model.
        $eventName = 'eloquent.creating: ' . Doctor::class;

        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated doctor creation failure.');
        });

        try {
            $this->withoutExceptionHandling();

            try {
                $this->actingAs($admin, 'sanctum')
                    ->postJson('/api/admin/doctors', [
                        'name' => 'Dr. Ahmed',
                        'email' => $email,
                        'password' => 'password123',
                        'license_number' => 'LIC-ROLLBACK-001',
                        'years_of_experience' => 5,
                    ]);

                $this->fail('Expected doctor creation to fail.');
            } catch (RuntimeException $exception) {
                $this->assertSame(
                    'Simulated doctor creation failure.',
                    $exception->getMessage()
                );
            }
        } finally {
            Event::forget($eventName);
        }

        // User must not exist because the transaction was rolled back.
        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);
    }
    
    // Doctor Account Status Tests
    public function test_admin_can_activate_doctor_account(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $doctor->user->is_active = false;
        $doctor->user->save();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/status", [ 'is_active' => true ])
            ->assertOk()
            ->assertJsonPath('is_active', true);

            $this->assertDatabaseHas('users', [ 'id' => $doctor->user_id, 'is_active' => true, ]);
    }

    public function test_admin_can_deactivate_doctor_account(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/status", [ 'is_active' => false ])
            ->assertOk()
            ->assertJsonPath('is_active', false);

            $this->assertDatabaseHas('users', [ 'id' => $doctor->user_id, 'is_active' => false, ]);
    }

    public function test_patient_cannot_change_doctor_account_status(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($patient, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/status", [ 'is_active' => false ])
            ->assertForbidden();

            $this->assertDatabaseHas('users', [ 'id' => $doctor->user_id, 'is_active' => true, ]);
    }

    public function test_doctor_cannot_change_doctor_account_status(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/status", [ 'is_active' => false ])
            ->assertForbidden();

            $this->assertDatabaseHas('users', [ 'id' => $doctor->user_id, 'is_active' => true, ]);
    }

    public function test_guest_cannot_change_doctor_account_status(): void
    {
        $doctor = Doctor::factory()->create();

        $this->patchJson("/api/admin/doctors/{$doctor->id}/status", [ 'is_active' => false ])
            ->assertUnauthorized();
    }

    public function test_admin_cannot_update_doctor_status_without_is_active(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/status", [])
            ->assertUnprocessable() 
            ->assertJsonValidationErrors('is_active');
    }

    public function test_admin_cannot_update_doctor_status_with_invalid_value(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/status", [ 'is_active' => 'invalid' ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_updating_doctor_status_does_not_modify_doctor_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create([
            'license_number' => 'LIC-12345', 
            'years_of_experience' => 10, 
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/status", [ 'is_active' => false ])
            ->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'license_number' => 'LIC-12345', 
            'years_of_experience' => 10, 
        ]);

        $this->assertDatabaseHas('users', [ 
            'id' => $doctor->user_id, 
            'is_active' => false, 
        ]);
    }

    // Doctor Verification Tests
    public function test_admin_can_verify_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $doctor->is_accepting_appointments = false;
        $doctor->save();

        $doctor->user->is_active = false;
        $doctor->user->save();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/verification-status", ['is_verified' => true])
            ->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'is_verified' => true,
            'is_accepting_appointments' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->user_id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_unverify_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $doctor->is_accepting_appointments = true;
        $doctor->save();
        $doctor->user->is_active = true;
        $doctor->user->save();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/verification-status", ['is_verified' => false])
            ->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'is_verified' => false,
            'is_accepting_appointments' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->user_id,
            'is_active' => true,
        ]);
    }

    public function test_patient_cannot_change_doctor_verification_status(): void
    {
        $patientUser = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create([
            'is_verified' => false,
        ]);

        $this->actingAs($patientUser, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/verification-status", ['is_verified' => true])
            ->assertForbidden();

        $this->assertDatabaseMissing('doctors', [
            'id' => $doctor->id,
            'is_verified' => true,
        ]);
    }

    public function test_doctor_cannot_change_own_verification_status(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([
            'is_verified' => false,
        ]);

        $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/verification-status", ['is_verified' => true])
            ->assertForbidden();

        $this->assertDatabaseMissing('doctors', [
            'id' => $doctor->id,
            'is_verified' => true,
        ]);
    }

    public function test_guest_cannot_change_doctor_verification_status(): void
    {
        $doctor = Doctor::factory()->create();

        $this->patchJson("/api/admin/doctors/{$doctor->id}/verification-status", ['is_verified' => true])
            ->assertUnauthorized();
    }

    public function test_admin_cannot_update_verification_status_without_is_verified(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/verification-status", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_verified');
    }

    public function test_admin_cannot_update_verification_status_with_invalid_value(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/verification-status", ['is_verified' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_verified');
    }
    
    // Doctor Appointment Availability Tests
    public function test_doctor_can_enable_appointment_availability()
    {
        $doctorUser = User::factory()->doctor()->create([
            'is_active' => true,
        ]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_accepting_appointments' => false,
            'is_verified' => true,
        ]);

        $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/doctor/appointment-availability", [
                'is_accepting_appointments' => true,
            ])
            ->assertOk()
            ->assertJson([
                'message' => 'Appointment availability updated successfully.',
                'is_accepting_appointments' => true,
            ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'is_accepting_appointments' => true,
            'is_verified' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->user_id,
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_disable_appointment_availability()
    {
        $doctorUser = User::factory()->doctor()->create([
            'is_active' => true,
        ]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_accepting_appointments' => true,
            'is_verified' => true,
        ]);

        $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/doctor/appointment-availability", [
                'is_accepting_appointments' => false
            ])
            ->assertOk()
            ->assertJson([
                'message' => 'Appointment availability updated successfully.',
                'is_accepting_appointments' => false,
            ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'is_accepting_appointments' => false,
            'is_verified' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $doctor->user_id,
            'is_active' => true,
        ]);
    }

    public function test_patient_cannot_update_appointment_availability()
    {
        $patientUser = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create([
            'is_accepting_appointments' => true,
        ]);

        $this->actingAs($patientUser, 'sanctum')
            ->patchJson("/api/doctor/appointment-availability", [
                'is_accepting_appointments' => false,
            ])
            ->assertForbidden();
        
        $this->assertDatabaseMissing('doctors', [
            'id' => $doctor->id,
            'is_accepting_appointments' => false,
        ]);
    }

    public function test_admin_cannot_update_appointment_availability()
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create([
            'is_accepting_appointments' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/doctor/appointment-availability", [
                'is_accepting_appointments' => false,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('doctors', [
            'id' => $doctor->id,
            'is_accepting_appointments' => false,
        ]);
    }

    public function test_guest_cannot_update_appointment_availability()
    {
        $doctor = Doctor::factory()->create([
            'is_accepting_appointments' => true,
        ]);

        $this->patchJson("/api/doctor/appointment-availability", [
                'is_accepting_appointments' => false,
            ])
            ->assertUnauthorized();

        $this->assertDatabaseMissing('doctors', [
            'id' => $doctor->id,
            'is_accepting_appointments' => false,
        ]);
    }

    public function test_doctor_cannot_update_appointment_availability_without_value()
    {
        $doctorUser = User::factory()->doctor()->create();
        
        $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/doctor/appointment-availability", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_accepting_appointments');
    }

    public function test_doctor_cannot_update_appointment_availability_with_invalid_value()
    {
        $doctorUser = User::factory()->doctor()->create();

        $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/doctor/appointment-availability", [
                'is_accepting_appointments' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_accepting_appointments');
    }

    public function test_doctor_can_only_update_own_appointment_availability()
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'is_accepting_appointments' => false,
        ]);
        
        $anotherDoctor = Doctor::factory()->create([
            'is_accepting_appointments' => false,
        ]);

        $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/doctor/appointment-availability", [
                'is_accepting_appointments' => true,
            ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'is_accepting_appointments' => true,
        ]);

        $this->assertDatabaseMissing('doctors', [
            'id' => $anotherDoctor->id,
            'is_accepting_appointments' => true,
        ]);
    }

}
