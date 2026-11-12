<?php

namespace Tests\Feature;

use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpecialtyTest extends TestCase
{
    use RefreshDatabase;

    // Public Specialty Listing & Details Tests
    public function test_authenticated_user_can_list_active_specialties_only(): void
    {
        $user = User::factory()->patient()->create();

        Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
            'is_active' => true,
        ]);

        Specialty::factory()->create([
            'name' => 'Dermatology',
            'slug' => 'dermatology',
            'is_active' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/specialties')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Cardiology'])
            ->assertJsonMissing(['name' => 'Dermatology']);
    }

    public function test_authenticated_user_can_view_an_active_specialty(): void
    {
        $user = User::factory()->patient()->create();

        $specialty = Specialty::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/specialties/{$specialty->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $specialty->id);
    }

    public function test_authenticated_user_cannot_view_an_inactive_specialty(): void
    {
        $user = User::factory()->patient()->create();

        $specialty = Specialty::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/specialties/{$specialty->id}")
            ->assertNotFound();
    }

    public function test_guest_cannot_access_public_specialties(): void
    {
        $this->getJson('/api/specialties')
            ->assertUnauthorized();

        $specialty = Specialty::factory()->create([
            'is_active' => true,
        ]);

        $this->getJson("/api/specialties/{$specialty->id}")
            ->assertUnauthorized();
    }

    public function test_specialty_endpoints_return_404_for_nonexistent_specialty(): void
    {
        $user = User::factory()->patient()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/specialties/999999')
            ->assertNotFound();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/specialties/999999')
            ->assertNotFound();

        $this->patchJson('/api/admin/specialties/999999', [
            'name' => 'Updated Specialty',
        ])->assertNotFound();
    }

    // Admin Specialty Management Tests
    public function test_admin_can_list_all_specialties_including_inactive_ones(): void
    {
        $admin = User::factory()->admin()->create();

        Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
            'is_active' => true,
        ]);

        Specialty::factory()->create([
            'name' => 'Dermatology',
            'slug' => 'dermatology',
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/specialties')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Cardiology'])
            ->assertJsonFragment(['name' => 'Dermatology']);
    }

    #[DataProvider('statusFilterProvider')]
    public function test_admin_can_filter_specialties_by_status(
        int $status,
        string $expectedName,
        string $unexpectedName
    ): void {
        $admin = User::factory()->admin()->create();

        Specialty::factory()->create([
            'name' => 'Cardiology',
            'is_active' => true,
        ]);

        Specialty::factory()->create([
            'name' => 'Dermatology',
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/specialties?status={$status}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => $expectedName])
            ->assertJsonMissing(['name' => $unexpectedName]);
    }

    public static function statusFilterProvider(): array
    {
        return [
            'active specialties' => [1, 'Cardiology', 'Dermatology'],
            'inactive specialties' => [0, 'Dermatology', 'Cardiology'],
        ];
    }

    #[DataProvider('invalidStatusProvider')]
    public function test_admin_cannot_filter_specialties_with_invalid_status(string $status): void 
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/specialties?status={$status}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public static function invalidStatusProvider(): array
    {
        return [
            'invalid string' => ['invalid'],
            'empty value' => [''],
            'unsupported value' => ['2'],
        ];
    }

    public function test_admin_can_view_an_inactive_specialty(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/specialties/{$specialty->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $specialty->id)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_admin_can_create_specialty_with_automatically_generated_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/specialties', [
                'name' => 'Heart Surgery',
                'description' => 'Heart and cardiovascular surgery',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Heart Surgery')
            ->assertJsonPath('data.slug', 'heart-surgery');

        $this->assertDatabaseHas('specialties', [
            'name' => 'Heart Surgery',
            'slug' => 'heart-surgery',
        ]);
    }

    public function test_admin_can_create_specialties_with_unique_slugs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/specialties', [
                'name' => 'Heart Surgery',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'heart-surgery');

        $this->postJson('/api/admin/specialties', [
            'name' => 'Heart-Surgery',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'heart-surgery-1');

        $this->assertDatabaseHas('specialties', [
            'name' => 'Heart Surgery',
            'slug' => 'heart-surgery',
        ]);

        $this->assertDatabaseHas('specialties', [
            'name' => 'Heart-Surgery',
            'slug' => 'heart-surgery-1',
        ]);
    }

    public function test_admin_cannot_create_specialty_with_duplicate_name(): void
    {
        $admin = User::factory()->admin()->create();

        Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/specialties', [
                'name' => 'Cardiology',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[DataProvider('invalidSpecialtyNameProvider')]
    public function test_admin_cannot_create_specialty_with_invalid_name(array $data): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/specialties', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public static function invalidSpecialtyNameProvider(): array
    {
        return [
            'missing name' => [[]],
            'empty name' => [['name' => '']],
            'null name' => [['name' => null]],
            'non-string name' => [['name' => 123]],
            'name exceeds maximum length' => [
                ['name' => str_repeat('A', 101)],
            ],
        ];
    }

    #[DataProvider('invalidDescriptionProvider')]
    public function test_admin_cannot_create_specialty_with_invalid_description(mixed $description): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/specialties', [
                'name' => 'Cardiology',
                'description' => $description,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('description');
    }

    public static function invalidDescriptionProvider(): array
    {
        return [
            'integer description' => [123],
            'array description' => [['invalid']],
            'boolean description' => [true],
        ];
    }

    public function test_admin_can_create_specialty_with_null_description(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/specialties', [
                'name' => 'Cardiology',
                'description' => null,
            ])
            ->assertCreated()
            ->assertJsonPath('data.description', null);
    }

    public function test_admin_cannot_update_specialty_with_duplicate_name(): void
    {
        $admin = User::factory()->admin()->create();

        Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
        ]);

        $specialty = Specialty::factory()->create([
            'name' => 'Dermatology',
            'slug' => 'dermatology',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", [
                'name' => 'Cardiology',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'name' => 'Dermatology',
            'slug' => 'dermatology',
        ]);
    }

    public function test_admin_can_update_specialty_with_its_current_name(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", [
                'name' => 'Cardiology',
                'description' => 'Updated description',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Cardiology')
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'name' => 'Cardiology',
            'description' => 'Updated description',
        ]);
    }

    #[DataProvider('invalidUpdateNameProvider')]
    public function test_admin_cannot_update_specialty_with_invalid_name(array $data): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiology',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public static function invalidUpdateNameProvider(): array
    {
        return [
            'empty name' => [['name' => '']],
            'null name' => [['name' => null]],
            'non-string name' => [['name' => 123]],
            'name exceeds maximum length' => [['name' => str_repeat('A', 101)]],
        ];
    }

    public function test_admin_can_update_specialty_without_providing_name(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiology',
            'description' => 'Old description',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", [
                'description' => 'Updated description',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Cardiology')
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'name' => 'Cardiology',
            'description' => 'Updated description',
        ]);
    }

    public function test_admin_can_update_specialty_without_changing_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", [
                'name' => 'Cardiac Surgery',
                'description' => 'Updated description',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Cardiac Surgery')
            ->assertJsonPath('data.slug', 'cardiology');

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'name' => 'Cardiac Surgery',
            'slug' => 'cardiology',
            'description' => 'Updated description',
        ]);
    }

    #[DataProvider('invalidUpdateDescriptionProvider')]
    public function test_admin_cannot_update_specialty_with_invalid_description(mixed $description): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", [
                'description' => $description,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('description');
    }

    public static function invalidUpdateDescriptionProvider(): array
    {
        return [
            'integer description' => [123],
            'array description' => [['invalid']],
            'boolean description' => [true],
        ];
    }

    public function test_admin_can_update_specialty_description_to_null(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'description' => 'Existing description',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", [
                'description' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.description', null);

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'description' => null,
        ]);
    }

    public function test_admin_cannot_update_specialty_with_empty_request(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('request');
    }

    public function test_admin_specialty_listing_is_paginated(): void
    {
        $admin = User::factory()->admin()->create();

        Specialty::factory()->count(16)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/specialties')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);
    }

    // Specialty Status Tests
    public function test_admin_can_deactivate_specialty(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_activate_specialty(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/specialties/{$specialty->id}/activate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'is_active' => true,
        ]);
    }

    // Specialty Authorization Tests
    public function test_non_admin_users_cannot_manage_specialties(): void
    {
        $users = [
            User::factory()->doctor()->create(),
            User::factory()->patient()->create(),
            User::factory()->receptionist()->create(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/admin/specialties')
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->postJson('/api/admin/specialties', [
                    'name' => 'Cardiology',
                ])
                ->assertForbidden();
        }
    }
    
    public function test_guest_cannot_create_specialty(): void
    {
        $this->postJson('/api/admin/specialties', [
            'name' => 'Cardiology',
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_access_admin_specialty_listing(): void
    {
        $this->getJson('/api/admin/specialties')
            ->assertUnauthorized();
    }

}
