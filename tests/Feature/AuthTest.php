<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('user.name', 'Test Patient')
            ->assertJsonPath('user.email', 'patient@example.com')
            ->assertJsonPath('user.role', UserRole::PATIENT->value)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure([
                'message',
                'user',
                'token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'patient@example.com',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registration_fails_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/register', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_when_email_already_exists(): void
    {
        User::factory()->create([
            'email' => 'patient@example.com',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Another Patient',
            'email' => 'patient@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_can_login_successfully(): void
    {
        $user = User::factory()->patient()->create([
            'email' => 'patient@example.com',
            'password' => 'Password123',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'patient@example.com',
            'password' => 'Password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure([
                'message',
                'user',
                'token',
                'token_type',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        User::factory()->patient()->create([
            'email' => 'patient@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'patient@example.com',
            'password' => 'WrongPassword123',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'The provided credentials are incorrect.'
            );

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->patient()->create([
            'email' => 'inactive@example.com',
            'password' => 'Password123',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password123',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'Your account is inactive. Please contact the administrator.'
            );

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->patient()->create();
        
        $token = $user->createToken('auth_token');

        $response = $this
            ->withToken($token->plainTextToken)
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_authenticated_user_can_access_the_user_endpoint(): void
    {
        $user = User::factory()->patient()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_guest_cannot_access_the_user_endpoint(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }
}
