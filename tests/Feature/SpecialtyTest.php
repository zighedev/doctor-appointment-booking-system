<?php

namespace Tests\Feature;

use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialtyTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/specialties');

        $response->assertOk()
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

    public function test_admin_can_create_specialty_with_automatically_generated_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/specialties', [
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

    public function test_admin_cannot_create_specialty_with_duplicate_name(): void
    {
        $admin = User::factory()->admin()->create();

        Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/specialties', [
                'name' => 'Cardiology',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_admin_can_update_specialty_without_changing_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/specialties/{$specialty->id}", [
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

    public function test_patient_cannot_create_specialty(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient, 'sanctum')
            ->postJson('/api/specialties', [
                'name' => 'Cardiology',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_deactivate_specialty(): void
    {
        $admin = User::factory()->admin()->create();

        $specialty = Specialty::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/specialties/{$specialty->id}/deactivate")
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
            ->patchJson("/api/specialties/{$specialty->id}/activate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_create_specialty(): void
    {
        $this->postJson('/api/specialties', [
            'name' => 'Cardiology',
        ])->assertUnauthorized();
    }
}
