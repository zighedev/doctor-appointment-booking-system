<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_guest_cannot_view_profile(): void
    {
        $this->getJson('/api/profile')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_update_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'name' => 'Amine Updated',
                'phone' => '0550123456',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Amine Updated')
            ->assertJsonPath('data.phone', '0550123456');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Amine Updated',
            'phone' => '0550123456',
        ]);
    }

    public function test_user_cannot_change_role_or_is_active_through_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::PATIENT,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'name' => 'Updated Name',
                'role' => UserRole::ADMIN->value,
                'is_active' => false,
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'role' => UserRole::PATIENT->value,
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_update_own_profile_with_non_string_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'name' => ['invalid'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

    }

    public function test_user_cannot_update_own_profile_with_name_longer_than_150_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/profile', [
                'name' => str_repeat('A', 151),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_user_cannot_update_own_profile_with_invalid_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/profile', [
                'email' => 'Invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_user_cannot_use_another_users_email(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'email' => $anotherUser->email,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_keep_own_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'email' => $user->email,
            ])
            ->assertOk();
    }

    public function test_email_verification_is_reset_when_email_changes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'email' => 'new-email@example.com',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new-email@example.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_user_cannot_use_another_users_phone(): void
    {
        $user = User::factory()->create(['phone' => '0550000001']);
        $anotherUser = User::factory()->create(['phone' => '0550000002',]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'phone' => $anotherUser->phone,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_user_can_keep_own_phone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'phone' => $user->phone,
            ])
            ->assertOk();
    }

    public function test_user_cannot_update_own_profile_with_phone_longer_than_30_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/profile', [
                'phone' => str_repeat('1', 31),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_user_cannot_update_own_profile_with_invalid_gender(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/profile', [
                'gender' => 'Invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gender');
    }

    public function test_user_cannot_update_own_profile_with_invalid_date_of_birth(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/profile', [
                'date_of_birth' => 'Invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_of_birth');
    }

}
