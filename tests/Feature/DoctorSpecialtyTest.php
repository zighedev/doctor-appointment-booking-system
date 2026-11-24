<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorSpecialtyTest extends TestCase
{
    use RefreshDatabase;

    // Attach Specialty to Doctor Tests
    public function test_admin_can_attach_specialty_to_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/specialties", [
                'specialty_id' => $specialty->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('doctor_specialty', [
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'is_primary' => false,
        ]);
    }

    public function test_admin_cannot_attach_same_specialty_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $doctor->specialties()->attach($specialty->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/specialties", [
                'specialty_id' => $specialty->id,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('doctor_specialty', 1);
    }

    public function test_admin_cannot_attach_inactive_specialty(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/specialties", [
                'specialty_id' => $specialty->id,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('doctor_specialty', [
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
        ]);
    }

    public function test_patient_cannot_attach_specialty_to_doctor(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($patient, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/specialties", [
                'specialty_id' => $specialty->id,
            ]);

        $response->assertForbidden();
    }

    public function test_doctor_cannot_attach_specialty_to_another_doctor(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($doctorUser, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/specialties", [
                'specialty_id' => $specialty->id,
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_attach_specialty_to_doctor(): void
    {
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->postJson(
            "/api/admin/doctors/{$doctor->id}/specialties",
            ['specialty_id' => $specialty->id]
        );

        $response->assertUnauthorized();
    }

    public function test_admin_cannot_attach_non_existing_specialty(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/doctors/{$doctor->id}/specialties", [
                'specialty_id' => 999999,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('doctor_specialty', 0);
    }

    // Detach Specialty from Doctor Tests
    public function test_admin_can_detach_specialty_from_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $doctor->specialties()->attach($specialty->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('doctor_specialty', [
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
        ]);
    }

    public function test_detaching_specialty_does_not_delete_specialty(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $doctor->specialties()->attach($specialty->id);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}");

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
        ]);
    }

    public function test_admin_can_detach_specialty_that_is_not_attached(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}");

        $response->assertOk();
    }

    public function test_patient_cannot_detach_specialty(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($patient, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}");

        $response->assertForbidden();
    }

    public function test_doctor_cannot_detach_specialty(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($doctorUser, 'sanctum')
            ->deleteJson("/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}");

        $response->assertForbidden();
    }

    public function test_guest_cannot_detach_specialty(): void
    {
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->deleteJson(
            "/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}"
        );

        $response->assertUnauthorized();
    }

    // Set Primary Specialty Tests
    public function test_admin_can_set_primary_specialty(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $doctor->specialties()->attach($specialty->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}/primary"
            );

        $response->assertOk();

        $this->assertDatabaseHas('doctor_specialty', [
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'is_primary' => true,
        ]);
    }

    public function test_setting_new_primary_specialty_removes_previous_primary(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $firstSpecialty = Specialty::factory()->create();
        $secondSpecialty = Specialty::factory()->create();

        $doctor->specialties()->attach($firstSpecialty->id, [
            'is_primary' => true,
        ]);

        $doctor->specialties()->attach($secondSpecialty->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/admin/doctors/{$doctor->id}/specialties/{$secondSpecialty->id}/primary"
            );

        $response->assertOk();

        $this->assertDatabaseHas('doctor_specialty', [
            'doctor_id' => $doctor->id,
            'specialty_id' => $firstSpecialty->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('doctor_specialty', [
            'doctor_id' => $doctor->id,
            'specialty_id' => $secondSpecialty->id,
            'is_primary' => true,
        ]);

        $this->assertDatabaseCount('doctor_specialty', 2);
    }

    public function test_admin_cannot_set_unattached_specialty_as_primary(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}/primary"
            );

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('doctor_specialty', [
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
        ]);
    }

    public function test_patient_cannot_set_primary_specialty(): void
    {
        $patient = User::factory()->patient()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($patient, 'sanctum')
            ->patchJson(
                "/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}/primary"
            );

        $response->assertForbidden();
    }

    public function test_doctor_cannot_set_primary_specialty(): void
    {
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->actingAs($doctorUser, 'sanctum')
            ->patchJson(
                "/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}/primary"
            );

        $response->assertForbidden();
    }

    public function test_guest_cannot_set_primary_specialty(): void
    {
        $doctor = Doctor::factory()->create();
        $specialty = Specialty::factory()->create();

        $response = $this->patchJson(
            "/api/admin/doctors/{$doctor->id}/specialties/{$specialty->id}/primary"
        );

        $response->assertUnauthorized();
    }
    
}
