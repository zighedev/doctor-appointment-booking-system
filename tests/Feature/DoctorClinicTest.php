<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorClinicTest extends TestCase
{
    use RefreshDatabase;

    // Attach Clinic to Doctor Tests
    public function test_admin_can_attach_clinic_to_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => $clinic->id,
                'consultation_fee' => 1500.00,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'consultation_fee' => 1500.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_attach_same_clinic_twice_to_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => $clinic->id,
                'consultation_fee' => 2000,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('clinic_doctor', 1);    
    } 

    public function test_admin_cannot_attach_inactive_clinic(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => $clinic->id,
                'consultation_fee' => 1500,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
        ]);
    }

    public function test_admin_cannot_attach_non_existing_clinic(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => 99999,
                'consultation_fee' => 1500,
            ]);

        $response->assertUnprocessable();
    }

    public function test_consultation_fee_is_required_and_must_be_non_negative(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => $clinic->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('consultation_fee');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => $clinic->id,
                'consultation_fee' => -100,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('consultation_fee');
    }

    public function test_patient_cannot_attach_clinic_to_doctor(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $response = $this->actingAs($patient, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => $clinic->id,
                'consultation_fee' => 1500,
            ]);

        $response->assertForbidden();
    }

    public function test_doctor_cannot_attach_clinic_to_another_doctor(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
        ]);
        $clinic = Clinic::factory()->create();

        $response = $this->actingAs($doctorUser, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/clinics", [
                'clinic_id' => $clinic->id,
                'consultation_fee' => 1500,
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_attach_clinic_to_doctor(): void
    {
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $response = $this->postJson(
            "/api/admin/doctors/{$doctor->id}/clinics",
            [
                'clinic_id' => $clinic->id,
                'consultation_fee' => 1500,
            ]
        );

        $response->assertUnauthorized();
    }

    // Update Doctor-Clinic Relationship Tests
    public function test_admin_can_update_consultation_fee(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'consultation_fee' => 2000,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'consultation_fee' => 2000,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_deactivate_doctor_clinic_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'is_active' => false,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reactivate_doctor_clinic_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'is_active' => true,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_fee_and_status_together(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'consultation_fee' => 2500,
                'is_active' => false,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'consultation_fee' => 2500,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_update_non_existing_doctor_clinic_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'consultation_fee' => 2000,
            ]);

        $response->assertNotFound();
    }

    public function test_update_clinic_rejects_invalid_data(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'consultation_fee' => -100,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('consultation_fee');

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'is_active' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_patient_cannot_update_doctor_clinic_relationship(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($patient, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'consultation_fee' => 2000,
            ]);

        $response->assertForbidden();
    }

    public function test_doctor_cannot_update_doctor_clinic_relationship(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
        ]);
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}", [
                'consultation_fee' => 2000,
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_update_doctor_clinic_relationship(): void
    {
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $response = $this->patchJson(
            "/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}",
            ['consultation_fee' => 2000]
        );

        $response->assertUnauthorized();
    }

    // Detach Clinic from Doctor Tests
    public function test_admin_can_detach_clinic_from_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id, [
            'consultation_fee' => 1500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Clinic detached successfully.',
            ]);

        $this->assertDatabaseMissing('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
        ]);
    }

    public function test_detaching_clinic_does_not_delete_doctor_or_clinic(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}")
            ->assertOk();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
        ]);

        $this->assertDatabaseHas('clinics', [
            'id' => $clinic->id,
        ]);
    }

    public function test_admin_can_detach_non_existing_relationship(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Clinic detached successfully.',
            ]);
    }

    public function test_patient_cannot_detach_clinic_from_doctor(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id);

        $response = $this->actingAs($patient, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('clinic_doctor', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
        ]);
    }

    public function test_doctor_cannot_detach_clinic_from_doctor(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
        ]);
        $clinic = Clinic::factory()->create();

        $doctor->clinics()->attach($clinic->id);

        $response = $this->actingAs($doctorUser, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}");

        $response->assertForbidden();
    }

    public function test_guest_cannot_detach_clinic_from_doctor(): void
    {
        $doctor = Doctor::factory()->create();
        $clinic = Clinic::factory()->create();

        $response = $this->deleteJson(
            "/api/admin/doctors/{$doctor->id}/clinics/{$clinic->id}"
        );

        $response->assertUnauthorized();
    }

}
