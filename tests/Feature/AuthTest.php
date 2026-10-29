<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = \App\Models\User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = \App\Models\User::factory()->create();

        $token = $user->createToken('test')->plainTextToken;

        $this->getJson('/api/user/me', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->postJson('/api/user/logout', [], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJson(['message' => 'Logged out.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_protected_routes_reject_guests(): void
    {
        $this->getJson('/api/user/me')->assertUnauthorized();
        $this->postJson('/api/user/logout')->assertUnauthorized();
    }

    public function test_login_returns_proper_429_after_too_many_attempts(): void
    {
        foreach (range(1, 31) as $attempt) {
            $response = $this->postJson('/api/user/login', ['email' => 'nobody@example.com', 'password' => 'secret']);
        }

        $response->assertStatus(429)
            ->assertJsonStructure([
                'message',
                'retry_after_seconds',
            ])
            ->assertHeader('Retry-After');
    }
}
