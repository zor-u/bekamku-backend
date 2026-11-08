<?php

namespace Tests\Feature;

use App\Models\Auth\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_receive_token(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'therapist@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['admin' => ['id', 'name', 'email'], 'token'])
            ->assertJsonMissingPath('admin.password');
    }

    public function test_admin_login_rejects_invalid_credentials(): void
    {
        $admin = Admin::factory()->create();

        $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_can_retrieve_their_profile_with_a_token(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'therapist@example.com',
            'password' => 'password',
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('email', 'therapist@example.com');
    }

    public function test_customer_token_is_rejected_on_admin_endpoints(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/admin/me')
            ->assertUnauthorized();
    }

    public function test_admin_token_is_rejected_on_customer_endpoints(): void
    {
        $admin = Admin::factory()->create();

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson('/api/user/me')
            ->assertUnauthorized();
    }

    public function test_guests_cannot_access_admin_endpoints(): void
    {
        $this->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_admin_can_logout(): void
    {
        $admin = Admin::factory()->create();

        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/admin/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $admin->id,
        ]);
    }
}
