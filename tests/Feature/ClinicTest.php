<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClinicTest extends TestCase
{
    use RefreshDatabase;

    // Public Clinic Listing & Details Tests
    public function test_authenticated_user_can_list_active_clinics(): void
    {
        $user = User::factory()->patient()->create();

        $activeClinic = Clinic::factory()->create(['is_active' => true]);
        $inactiveClinic = Clinic::factory()->create(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/clinics')
            ->assertOk()
            ->assertJsonFragment(['name' => $activeClinic->name])
            ->assertJsonMissing(['name' => $inactiveClinic->name]);
    }

    public function test_authenticated_user_can_view_active_clinic(): void
    {
        $user = User::factory()->patient()->create();
        $clinic = Clinic::factory()->create(['is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/clinics/{$clinic->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $clinic->id);
    }

    public function test_authenticated_user_cannot_view_inactive_clinic(): void
    {
        $user = User::factory()->patient()->create();
        $clinic = Clinic::factory()->create(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/clinics/{$clinic->id}")
            ->assertNotFound();
    }

    public function test_guest_cannot_access_clinics(): void
    {
        $this->getJson('/api/clinics')->assertUnauthorized();
    }

    public function test_guest_cannot_view_clinic_details(): void
    {
        $clinic = Clinic::factory()->create();

        $this->getJson("/api/clinics/{$clinic->id}")->assertUnauthorized();
    }

    public function test_clinic_endpoints_return_404_for_nonexistent_clinic(): void
    {
        $user = User::factory()->patient()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/clinics/999999')
            ->assertNotFound();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics/999999')
            ->assertNotFound();

        $this->patchJson('/api/admin/clinics/999999', [
            'name' => 'New name',
            'address' => 'New address',
            'city' => 'New city',
            'country' => 'New country',
        ])->assertNotFound();
    }

    // Admin Clinic Management Tests
    public function test_admin_can_list_all_clinics(): void
    {
        $admin = User::factory()->admin()->create();

        $activeClinic = Clinic::factory()->create(['is_active' => true]);
        $inactiveClinic = Clinic::factory()->create(['is_active' => false]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics')
            ->assertOk()
            ->assertJsonFragment(['name' => $activeClinic->name])
            ->assertJsonFragment(['name' => $inactiveClinic->name]);
    }

    public function test_admin_clinic_listing_is_paginated(): void
    {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->count(20)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics?page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_admin_clinic_pagination_preserves_filters(): void
    {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->count(16)->create([
            'is_active' => true,
            'city' => 'Zitouna',
        ]);

        Clinic::factory()->count(5)->create([
            'is_active' => false,
            'city' => 'Zitouna',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics?status=1&city=Zitouna')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('links.next', url('/api/admin/clinics?status=1&city=Zitouna&page=2'));

        $this->getJson('/api/admin/clinics?status=1&city=Zitouna&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    #[DataProvider('clinicStatusProvider')]
    public function test_admin_can_filter_clinics_by_status(
        int $status,
        string $expectedName,
        string $unexpectedName
    ): void {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->create([
            'name' => 'Active Clinic',
            'is_active' => true,
        ]);

        Clinic::factory()->create([
            'name' => 'Inactive Clinic',
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/clinics?status={$status}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => $expectedName])
            ->assertJsonMissing(['name' => $unexpectedName]);
    }

    public static function clinicStatusProvider(): array
    {
        return [
            'active clinics' => [1, 'Active Clinic', 'Inactive Clinic'],
            'inactive clinics' => [0, 'Inactive Clinic', 'Active Clinic'],
        ];
    }

    #[DataProvider('invalidClinicStatusProvider')]
    public function test_admin_cannot_filter_clinics_with_invalid_status(
        string $status
    ): void {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/clinics?status={$status}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public static function invalidClinicStatusProvider(): array
    {
        return [
            'invalid integer' => ['2'],
            'invalid string' => ['active'],
            'empty value' => [''],
        ];
    }

    #[DataProvider('clinicLocationProvider')]
    public function test_admin_can_filter_clinics_by_location(
        string $filter,
        string $value,
        string $expectedName,
        string $unexpectedName
    ): void {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->create([
            'name' => 'Zitouna Clinic',
            'city' => 'Zitouna',
            'state' => 'Skikda Province',
            'country' => 'Algeria',
        ]);

        Clinic::factory()->create([
            'name' => 'Algiers Clinic',
            'city' => 'Algiers',
            'state' => 'Algiers Province',
            'country' => 'Algeria',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/clinics?{$filter}={$value}")
            ->assertOk()
            ->assertJsonFragment(['name' => $expectedName])
            ->assertJsonMissing(['name' => $unexpectedName]);
    }

    public static function clinicLocationProvider(): array
    {
        return [
            'filter by city' => ['city', 'Zitouna', 'Zitouna Clinic', 'Algiers Clinic'],
            'filter by state' => ['state', 'Skikda Province', 'Zitouna Clinic', 'Algiers Clinic'],
        ];
    }

    public function test_admin_can_filter_clinics_by_country(): void
    {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->create([
            'name' => 'Algeria Clinic',
            'country' => 'Algeria',
        ]);

        Clinic::factory()->create([
            'name' => 'Egypt Clinic',
            'country' => 'Egypt',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics?country=Algeria')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Algeria Clinic'])
            ->assertJsonMissing(['name' => 'Egypt Clinic']);
    }

    public function test_admin_can_combine_clinic_filters(): void
    {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->create([
            'name' => 'Matching Clinic',
            'is_active' => true,
            'city' => 'Zitouna',
            'country' => 'Algeria',
        ]);

        Clinic::factory()->create([
            'name' => 'Inactive Clinic',
            'is_active' => false,
            'city' => 'Zitouna',
            'country' => 'Algeria',
        ]);

        Clinic::factory()->create([
            'name' => 'Other City Clinic',
            'is_active' => true,
            'city' => 'Algiers',
            'country' => 'Algeria',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics?status=1&city=Zitouna&country=Algeria')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Matching Clinic']);
    }

    #[DataProvider('invalidClinicLocationProvider')]
    public function test_admin_cannot_filter_clinics_with_invalid_location(string $filter, mixed $value): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/clinics?' . http_build_query([$filter => $value]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($filter);
    }

    public static function invalidClinicLocationProvider(): array
    {
        return [
            'empty city' => ['city', ''],
            'non-string city' => ['city', ['Zitouna']],
            'empty state' => ['state', ''],
            'non-string state' => ['state', ['Skikda']],
            'empty country' => ['country', ''],
            'non-string country' => ['country', ['Algeria']],
        ];
    }

    public function test_admin_can_view_inactive_clinic(): void
    {
        $admin = User::factory()->admin()->create();
        $clinic = Clinic::factory()->create(['is_active' => false]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/clinics/{$clinic->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $clinic->id);
    }

    public function test_admin_can_create_clinic(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Setif',
            'country' => 'Algeria',
            'phone' => '0550000001',
            'email' => 'central@example.com',
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Central Clinic');

        $this->assertDatabaseHas('clinics', [
            'name' => 'Central Clinic',
            'city' => 'Setif',
        ]);
    }

    #[DataProvider('missingRequiredClinicFieldsProvider')]
    public function test_admin_cannot_create_clinic_without_required_fields(string $field): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
        ];

        unset($data[$field]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function missingRequiredClinicFieldsProvider(): array
    {
        return [
            'missing name' => ['name'],
            'missing address' => ['address'],
            'missing city' => ['city'],
            'missing country' => ['country'],
        ];
    }

    #[DataProvider('emptyRequiredClinicFieldsProvider')]
    public function test_admin_cannot_create_clinic_with_empty_required_fields(string $field): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
        ];

        $data[$field] = '';

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function emptyRequiredClinicFieldsProvider(): array
    {
        return [
            'empty name' => ['name'],
            'empty address' => ['address'],
            'empty city' => ['city'],
            'empty country' => ['country'],
        ];
    }

    #[DataProvider('invalidClinicEmailProvider')]
    public function test_admin_cannot_create_clinic_with_invalid_email(string $email): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
            'email' => $email,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
        }

    public static function invalidClinicEmailProvider(): array
    {
        return [
            'missing domain' => ['clinic@'],
            'missing username' => ['@example.com'],
            'invalid format' => ['not-an-email'],
        ];
    }

    #[DataProvider('duplicateClinicContactProvider')]
    public function test_admin_cannot_create_clinic_with_duplicate_contact(string $field, string $value): void
    {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->create([
            $field => $value,
        ]);

        $data = [
            'name' => 'New Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
            $field => $value,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function duplicateClinicContactProvider(): array
    {
        return [
            'duplicate phone' => ['phone', '0667000001'],
            'duplicate email' => ['email', 'clinic@example.com'],
        ];
    }

    #[DataProvider('invalidClinicCoordinatesProvider')]
    public function test_admin_cannot_create_clinic_with_invalid_coordinates(
        string $field,
        mixed $value
    ): void {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
            $field => $value,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidClinicCoordinatesProvider(): array
    {
        return [
            'latitude above maximum' => ['latitude', 91],
            'latitude below minimum' => ['latitude', -91],
            'invalid latitude type' => ['latitude', 'north'],
            'longitude above maximum' => ['longitude', 181],
            'longitude below minimum' => ['longitude', -181],
            'invalid longitude type' => ['longitude', 'east'],
        ];
    }

    public function test_admin_cannot_create_clinic_with_invalid_timezone(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
            'timezone' => 'Invalid/Timezone',
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('timezone');
    }

    #[DataProvider('invalidClinicEnumProvider')]
    public function test_admin_cannot_create_clinic_with_invalid_enum_values(
        string $field,
        string $value
    ): void {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
            $field => $value,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidClinicEnumProvider(): array
    {
        return [
            'invalid booking confirmation mode' => ['booking_confirmation_mode', 'automatic'],
            'invalid payment policy' => ['payment_policy', 'cash'],
        ];
    }

    #[DataProvider('invalidClinicTimeoutProvider')]
    public function test_admin_cannot_create_clinic_with_invalid_timeout_values(
        string $field,
        mixed $value
    ): void {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'Central Clinic',
            'address' => 'Main Street',
            'city' => 'Zitouna',
            'country' => 'Algeria',
            $field => $value,
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/clinics', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidClinicTimeoutProvider(): array
    {
        return [
            'negative reschedule cutoff' => ['reschedule_cutoff_hours', -1],
            'excessive reschedule cutoff' => ['reschedule_cutoff_hours', 169],
            'non-integer reschedule cutoff' => ['reschedule_cutoff_hours', 'abc'],

            'too short confirmation timeout' => ['confirmation_timeout_minutes', 4],
            'excessive confirmation timeout' => ['confirmation_timeout_minutes', 1441],

            'too short payment timeout' => ['payment_timeout_minutes', 4],
            'excessive payment timeout' => ['payment_timeout_minutes', 121],
        ];
    }

    public function test_admin_can_update_clinic(): void
    {
        $admin = User::factory()->admin()->create();
        $clinic = Clinic::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/clinics/{$clinic->id}", [
                'name' => 'Updated Clinic',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Clinic');

        $this->assertDatabaseHas('clinics', [
            'id' => $clinic->id,
            'name' => 'Updated Clinic',
        ]);
    }

    public function test_admin_can_update_clinic_without_changing_its_contact_details(): void
    {
        $admin = User::factory()->admin()->create();

        $clinic = Clinic::factory()->create([
            'phone' => '0667000001',
            'email' => 'clinic@example.com',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/clinics/{$clinic->id}", [
                'name' => 'Updated Clinic',
                'phone' => '0667000001',
                'email' => 'clinic@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Clinic');
    }

    #[DataProvider('duplicateClinicContactProvider')]
    public function test_admin_cannot_update_clinic_with_another_clinic_contact(
        string $field,
        string $value
    ): void {
        $admin = User::factory()->admin()->create();

        Clinic::factory()->create([
            $field => $value,
        ]);

        $clinic = Clinic::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/clinics/{$clinic->id}", [
                $field => $value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public function test_admin_can_clear_nullable_clinic_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $clinic = Clinic::factory()->create([
            'phone' => '0667000001',
            'email' => 'clinic@example.com',
            'description' => 'Clinic description',
            'state' => 'Skikda Province',
            'latitude' => 36.6900,
            'longitude' => 6.3100,
            'timezone' => 'Africa/Algiers',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/clinics/{$clinic->id}", [
                'phone' => null,
                'email' => null,
                'description' => null,
                'state' => null,
                'latitude' => null,
                'longitude' => null,
                'timezone' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.state', null)
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.longitude', null)
            ->assertJsonPath('data.timezone', null);

        $this->assertDatabaseHas('clinics', [
            'id' => $clinic->id,
            'phone' => null,
            'email' => null,
            'description' => null,
            'state' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
        ]);
    }

    public function test_admin_cannot_update_clinic_with_empty_data(): void
    {
        $admin = User::factory()->admin()->create();
        $clinic = Clinic::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/clinics/{$clinic->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('request');
    }

    public function test_patient_cannot_access_admin_clinics(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/admin/clinics')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_admin_clinics(): void
    {
        $this->getJson('/api/admin/clinics')->assertUnauthorized();
    }    

    // Clinic Status Tests
    public function test_admin_can_deactivate_clinic(): void
    {
        $admin = User::factory()->admin()->create();
        $clinic = Clinic::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/clinics/{$clinic->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('clinics', [
            'id' => $clinic->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_activate_clinic(): void
    {
        $admin = User::factory()->admin()->create();
        $clinic = Clinic::factory()->create(['is_active' => false]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/clinics/{$clinic->id}/activate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('clinics', [
            'id' => $clinic->id,
            'is_active' => true,
        ]);
    }

    // Clinic Authorization Tests
    public function test_non_admin_users_cannot_manage_clinics(): void
    {
        $clinic = Clinic::factory()->create();

        $users = [
            User::factory()->doctor()->create(),
            User::factory()->patient()->create(),
            User::factory()->receptionist()->create(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/admin/clinics')
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->postJson('/api/admin/clinics', [
                    'name' => 'Clinic name',
                    'address' => 'Clinic address',
                    'city' => 'Clinic city',
                    'country' => 'Clinic country',
                ])
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/admin/clinics/{$clinic->id}", ['name' => 'Updated name'])
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/admin/clinics/{$clinic->id}/deactivate")
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/admin/clinics/{$clinic->id}/activate")
                ->assertForbidden();
        }
    }

    public function test_guest_cannot_create_clinic(): void
    {
        $this->postJson('/api/admin/clinics', [
            'name' => 'Clinic name',
            'address' => 'Clinic address',
            'city' => 'Clinic city',
            'country' => 'Clinic country',
        ])->assertUnauthorized();
    }

}
