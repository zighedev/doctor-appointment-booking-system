<?php

namespace Tests\Feature;

use App\Enums\UserGender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use RuntimeException;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    // Patient Profile Tests
    public function test_patient_can_view_own_profile(): void
    {
        $user = User::factory()->patient()->create();

        $patient = Patient::factory()->create([
            'user_id' => $user->id,
            'blood_type' => 'AB+',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/patient/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $patient->id)
            ->assertJsonPath('data.blood_type', 'AB+')
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_doctor_cannot_view_patient_profile(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor, 'sanctum')
            ->getJson('/api/patient/profile')
            ->assertForbidden();
    }

    public function test_patient_can_update_own_profile(): void
    {
        $user = User::factory()->patient()->create();

        $patient = Patient::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/patient/profile', [
                'blood_type' => 'A+',
                'address' => 'New Address',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.blood_type', 'A+')
            ->assertJsonPath('data.address', 'New Address');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'blood_type' => 'A+',
            'address' => 'New Address',
        ]);
    }

    public function test_patient_can_only_update_own_profile(): void
    {
        $user = User::factory()->patient()->create();

        $ownPatient = Patient::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherPatient = Patient::factory()->create(['blood_type' => 'A+']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/patient/profile', [
                'blood_type' => 'B+',
            ])
            ->assertOk();

        $this->assertDatabaseHas('patients', [
            'id' => $ownPatient->id,
            'blood_type' => 'B+',
        ]);

        $this->assertDatabaseHas('patients', [
            'id' => $otherPatient->id,
            'blood_type' => 'A+',
        ]);
    }

    public function test_doctor_cannot_update_patient_profile(): void
    {
        $doctor = User::factory()->doctor()->create();

        $response = $this->actingAs($doctor, 'sanctum')
            ->patchJson('/api/patient/profile', [
                'blood_type' => 'A+',
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_update_patient_profile(): void
    {
        $response = $this->patchJson('/api/patient/profile', [
            'blood_type' => 'A+',
        ]);

        $response->assertUnauthorized();
    }

    // Admin Patient Management Tests
    public function test_admin_can_view_all_patients(): void
    {
        $admin = User::factory()->admin()->create();
        Patient::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/patients')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_patient_list_is_paginated(): void
    {
        $admin = User::factory()->admin()->create();
        Patient::factory()->count(16)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/patients')
            ->assertOk()
            ->assertJsonCount(15, 'data');

        $response->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_patient_pagination_preserves_filters(): void
    {
        $admin = User::factory()->admin()->create();

        Patient::factory()
        ->count(16)
        ->create()
        ->each(fn ($patient) => $patient->user->update([
            'gender' => UserGender::MALE,
        ]));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/patients?gender=' . UserGender::MALE->value)
            ->assertOk();

        $response->assertJsonPath(
            'links.next',
            fn ($url) => str_contains($url, 'gender=' . UserGender::MALE->value)
        );
    }

    public function test_admin_can_filter_patients_registered_today(): void
    {
        $admin = User::factory()->admin()->create();

        $todayPatient = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => today()->startOfDay()->addHours(10),
            ]), 'user')
            ->create();

        $yesterdayPatient = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => today()->subDay()->startOfDay(),
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?registration_period=today');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $todayPatient->id]);

        $this->assertSame([$todayPatient->id], collect($response->json('data'))->pluck('id')->all());

    }

    public function test_admin_can_filter_patients_registered_in_last_7_days(): void
    {
        $admin = User::factory()->admin()->create();

        $recentPatient = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => now()->subDays(3),
            ]), 'user')
            ->create();

        $oldPatient = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => now()->subDays(10),
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?registration_period=last_7_days');

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame([$recentPatient->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_admin_can_filter_patients_registered_in_last_30_days(): void
    {
        $admin = User::factory()->admin()->create();

        $recentPatient = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => now()->subDays(20),
            ]), 'user')
            ->create();

        $oldPatient = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => now()->subDays(40),
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?registration_period=last_30_days');

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame([$recentPatient->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_admin_can_filter_patients_registered_this_month(): void
    {
        $admin = User::factory()->admin()->create();

        $thisMonthPatient = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => now()->startOfMonth()->addDays(5),
            ]), 'user')
            ->create();

        $lastMonthPatient  = Patient::factory()
            ->for(User::factory()->create([
                'created_at' => now()->startOfMonth()->subDay(),
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?registration_period=this_month');

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame([$thisMonthPatient->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_admin_can_filter_patients_by_gender(): void
    {
       $admin = User::factory()->admin()->create();

        $malePatient = Patient::factory()
            ->for(User::factory()->create([
                'gender' => UserGender::MALE,
            ]), 'user')
            ->create();

        $femalePatient = Patient::factory()
            ->for(User::factory()->create([
                'gender' => UserGender::FEMALE,
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?gender=' . UserGender::MALE->value);

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame([$malePatient->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_admin_can_filter_patients_by_inactive_status(): void
    {
        $admin = User::factory()->admin()->create();

        $inactivePatient = Patient::factory()
            ->for(User::factory()->create([
                'is_active' => false,
            ]), 'user')
            ->create();

        $activePatient = Patient::factory()
            ->for(User::factory()->create([
                'is_active' => true,
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/patients?status=0');
        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame([$inactivePatient->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_admin_can_filter_patients_by_active_status(): void
    {
        $admin = User::factory()->admin()->create();

        $activePatient = Patient::factory()
            ->for(User::factory()->create([
                'is_active' => true,
            ]), 'user')
            ->create();

        $inactivePatient = Patient::factory()
            ->for(User::factory()->create([
                'is_active' => false,
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/patients?status=1');
        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame([$activePatient->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_admin_can_combine_all_patient_filters(): void
    {
        $admin = User::factory()->admin()->create();

        $matchingPatient = Patient::factory()
            ->for(User::factory()->create([
                'gender' => UserGender::MALE,
                'is_active' => true,
                'created_at' => now()->subDays(2),
            ]), 'user')
            ->create();

        // Wrong gender
        Patient::factory()
            ->for(User::factory()->create([
                'gender' => UserGender::FEMALE,
                'is_active' => true,
                'created_at' => now()->subDays(2),
            ]), 'user')
            ->create();

        // Inactive account
        Patient::factory()
            ->for(User::factory()->create([
                'gender' => UserGender::MALE,
                'is_active' => false,
                'created_at' => now()->subDays(2),
            ]), 'user')
            ->create();

        // Outside registration period
        Patient::factory()
            ->for(User::factory()->create([
                'gender' => UserGender::MALE,
                'is_active' => true,
                'created_at' => now()->subDays(10),
            ]), 'user')
            ->create();

        $response = $this->actingAs($admin)->getJson(
            '/api/admin/patients?registration_period=last_7_days&gender='
            . UserGender::MALE->value . '&status=1'
        );

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame([$matchingPatient->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_admin_cannot_filter_patients_with_invalid_registration_period(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?registration_period=last_year');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['registration_period']);
    }

    public function test_admin_cannot_filter_patients_with_invalid_gender(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?gender=unknown');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_admin_cannot_filter_patients_with_invalid_status(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/patients?status=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_admin_can_view_patient_details(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create([
            'blood_type' => 'A+',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/patients/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $patient->id)
            ->assertJsonPath('data.blood_type', 'A+')
            ->assertJsonPath('data.user.id', $patient->user_id);
    }

    public function test_patient_cannot_view_all_patients(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/admin/patients')
            ->assertForbidden();
    }

    public function test_patient_cannot_view_another_patient_admin_details(): void
    {
        $patient = Patient::factory()
            ->for(User::factory()->create(), 'user')
            ->create();

        $anotherPatient = Patient::factory()
            ->for(User::factory()->create(), 'user')
            ->create();

        $this->actingAs($patient->user, 'sanctum')
            ->getJson("/api/admin/patients/{$anotherPatient->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_all_patients(): void
    {
        $this->getJson('/api/admin/patients')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_view_patient_details(): void
    {
        $patient = Patient::factory()->create();

        $this->getJson("/api/admin/patients/{$patient->id}")
            ->assertUnauthorized();
    }

    public function test_doctor_cannot_view_patient_admin_details(): void
    {
        $doctor = User::factory()->doctor()->create();

        $patient = Patient::factory()
            ->for(User::factory()->create(), 'user')
            ->create();

        $this->actingAs($doctor, 'sanctum')
            ->getJson("/api/admin/patients/{$patient->id}")
            ->assertForbidden();
    }

    public function test_admin_can_create_patient(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/patients', [
                'name' => 'Amine',
                'email' => 'amine@example.com',
                'password' => 'password123',
                'phone' => '0550000001',
                'gender' => 'male',
                'date_of_birth' => '1995-05-14',
                'blood_type' => 'O+',
                'emergency_contact_name' => 'Mohammed',
                'emergency_contact_phone' => '0550000002',
                'emergency_contact_relationship' => 'Brother',
                'address' => 'Skikda, Algeria',
            ])
            ->assertCreated()
            ->assertJsonPath('data.blood_type', 'O+')
            ->assertJsonPath('data.user.name', 'Amine')
            ->assertJsonPath('data.user.role', 'patient');

        $this->assertDatabaseHas('users', [
            'email' => 'amine@example.com',
            'role' => 'patient',
        ]);

        $this->assertDatabaseHas('patients', [
            'blood_type' => 'O+',
            'emergency_contact_name' => 'Mohammed',
        ]);
    }

    public function test_patient_cannot_create_another_patient(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient, 'sanctum')
            ->postJson('/api/admin/patients', [
                'name' => 'Another Patient',
                'email' => 'another@example.com',
                'password' => 'password123',
            ])
            ->assertForbidden();
    }

    public function test_doctor_cannot_create_patient(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor, 'sanctum')
            ->postJson('/api/admin/patients', [
                'name' => 'Test Patient',
                'email' => 'patient@example.com',
                'password' => 'password123',
                'gender' => UserGender::MALE->value,
            ])
            ->assertForbidden();
    }

    public function test_guest_cannot_create_patient(): void
    {
        $this->postJson('/api/admin/patients', [
            'name' => 'Amine',
            'email' => 'amine@example.com',
            'password' => 'password123',
        ])->assertUnauthorized();
    }

    public function test_admin_cannot_create_patient_with_invalid_data(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/patients', [
                'name' => '',
                'email' => 'invalid-email',
                'password' => '123',
                'gender' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'gender',
            ]);
    }

    public function test_admin_cannot_create_patient_with_existing_email(): void
    {
        $admin = User::factory()->admin()->create();

        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/patients', [
                'name' => 'Amine',
                'email' => 'existing@example.com',
                'password' => 'password123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_can_update_patient(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}", [
                'blood_type' => 'A+',
                'address' => 'New Address',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.blood_type', 'A+')
            ->assertJsonPath('data.address', 'New Address');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'blood_type' => 'A+',
            'address' => 'New Address',
        ]);
    }

    public function test_patient_cannot_update_patient(): void
    {
        $patientUser = User::factory()->patient()->create();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($patientUser, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}", [
                'blood_type' => 'B+',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('patients', [
            'id' => $patient->id,
            'blood_type' => 'B+',
        ]);
    }

    public function test_guest_cannot_update_patient(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->patchJson("/api/admin/patients/{$patient->id}", [
            'blood_type' => 'B+',
        ]);

        $response->assertUnauthorized();
    }

    public function test_admin_cannot_update_patient_with_invalid_data(): void
    {
        $admin = User::factory()->admin()->create();

        $patient = Patient::factory()
            ->for(User::factory()->create(), 'user')
            ->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}", [
                'blood_type' => 'AB+AB-',
                'emergency_contact_name' => str_repeat('A', 151),
                'emergency_contact_phone' => str_repeat('1', 31),
                'emergency_contact_relationship' => str_repeat('A', 101),
                'address' => ['invalid'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'blood_type',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'address',
            ]);
    }

    public function test_user_creation_is_rolled_back_if_patient_creation_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $email = 'rollback@example.com';
        $eventName = 'eloquent.creating: ' . Patient::class;

        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated patient creation failure.');
        });

        try {
            $this->withoutExceptionHandling();
            try {
                $this->actingAs($admin, 'sanctum')
                    ->postJson('/api/admin/patients', [
                        'name' => 'Amine',
                        'email' => $email,
                        'password' => 'password123',
                        'blood_type' => 'O-',
                    ]);
                $this->fail('Expected patient creation to fail.');
            } catch (RuntimeException $exception) {
                $this->assertSame(
                    'Simulated patient creation failure.',
                    $exception->getMessage()
                );
            }
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);
    }

    // Patient Account Status Tests
    public function test_admin_can_activate_patient_account(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $patient->user()->update(['is_active' => false]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}/status", [
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('is_active', true);

        $this->assertDatabaseHas('users', [
            'id' => $patient->user_id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_deactivate_patient_account(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}/status", [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('users', [
            'id' => $patient->user_id,
            'is_active' => false,
        ]);
    }

    public function test_patient_cannot_change_patient_account_status(): void
    {
        $patientUser = User::factory()->patient()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($patientUser, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}/status", [
                'is_active' => false,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $patient->user_id,
            'is_active' => true,
        ]);
    }

    public function test_doctor_cannot_change_patient_account_status(): void
    {
        $doctor = User::factory()->doctor()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($doctor, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}/status", [
                'is_active' => false,
            ])
            ->assertForbidden();
            
        $this->assertDatabaseHas('users', [
            'id' => $patient->user_id,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_change_patient_account_status(): void
    {
        $patient = Patient::factory()->create();

        $this->patchJson("/api/admin/patients/{$patient->id}/status", [
            'is_active' => false,
        ])->assertUnauthorized();
    }

    public function test_admin_cannot_update_patient_status_without_is_active(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}/status", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_admin_cannot_update_patient_status_with_invalid_value(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}/status", ['is_active' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_updating_patient_status_does_not_modify_patient_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = Patient::factory()->create([
            'blood_type' => 'O+',
            'address' => 'zitouna Skikda',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/patients/{$patient->id}/status", ['is_active' => false])
            ->assertOk();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'blood_type' => 'O+',
            'address' => 'zitouna Skikda',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $patient->user_id,
            'is_active' => false,
        ]);
    }

}
