<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Route::middleware(['auth:sanctum', 'role:admin'])
            ->get('/api/test/admin', function () {
                return response()->json(['message' => 'Access granted.']);
            });
    }

    public function test_admin_can_access_route_restricted_to_admin_role(): void
    {
        $user = User::factory()->admin()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/test/admin');
        $response->assertOk()
                ->assertJson(['message' => 'Access granted.']);
    }

    public function test_patient_cannot_access_route_restricted_to_admin_role(): void
    {
        $user = User::factory()->patient()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/test/admin');

        $response->assertForbidden()
                ->assertJson(['message' => 'This action is unauthorized.']);
    }

    public function test_guest_cannot_access_route_restricted_to_admin_role(): void
    {
        $response = $this->getJson('/api/test/admin');
        $response->assertUnauthorized()
                ->assertJson(['message' => 'Unauthenticated.']);
    }
}
