<?php

namespace Tests\Feature;

use App\Actions\Auth\RegisterUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'phone'], 'token'])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'phone'], 'token'])
            ->assertJsonMissingPath('user.password');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/user/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::factory()->create();

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

    public function test_register_normalises_uppercase_email(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Upper Case',
            'email' => 'Upper.Case@Example.COM',
            'phone' => '081234567891',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'upper.case@example.com');

        $this->assertDatabaseHas('users', ['email' => 'upper.case@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/register', [
            'name' => 'Duplicate Email',
            'email' => $user->email,
            'phone' => '081234567892',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_register_rejects_duplicate_phone(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/register', [
            'name' => 'Duplicate Phone',
            'email' => 'duplicate-phone@example.com',
            'phone' => $user->phone,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_register_rejects_invalid_phone_format(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Bad Phone',
            'email' => 'bad-phone@example.com',
            'phone' => '+628123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_register_rejects_mismatched_password_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Mismatch',
            'email' => 'mismatch@example.com',
            'phone' => '081234567893',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_register_requires_all_fields(): void
    {
        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password']);
    }

    public function test_register_returns_429_after_too_many_attempts(): void
    {
        foreach (range(1, 20) as $attempt) {
            $this->postJson('/api/register', [
                'name' => 'Throttle',
                'email' => "throttle{$attempt}@example.com",
                'phone' => '0812345678'.str_pad((string) $attempt, 2, '0', STR_PAD_LEFT),
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertCreated();
        }

        $this->postJson('/api/register', [
            'name' => 'Throttle',
            'email' => 'throttle21@example.com',
            'phone' => '081234567821',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after_seconds'])
            ->assertHeader('Retry-After');
    }

    public function test_register_maps_a_database_unique_violation_to_a_validation_error(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        try {
            (new RegisterUser)->handle([
                'name' => 'Race',
                'email' => $user->email,
                'phone' => '081234567899',
                'password' => 'password',
            ]);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());

            throw $exception;
        }
    }
}
