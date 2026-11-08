<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\Auth\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StaffRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_returns_their_role(): void
    {
        $therapist = Admin::factory()->therapist()->create([
            'email' => 'therapist@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/admin/login', [
            'email' => $therapist->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('admin.roles', 'therapist');
    }

    public function test_therapist_can_use_the_therapist_mobile_endpoints(): void
    {
        $therapist = Admin::factory()->therapist()->create();

        $this->withToken($therapist->createToken('test')->plainTextToken)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('roles', 'therapist');
    }

    public function test_therapist_cannot_access_the_admin_staff_endpoints(): void
    {
        $therapist = Admin::factory()->therapist()->create();

        $this->withToken($therapist->createToken('test')->plainTextToken)
            ->getJson('/api/admin/staff')
            ->assertForbidden();
    }

    public function test_admin_can_list_staff(): void
    {
        $admin = Admin::factory()->create();

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson('/api/admin/staff')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roles', 'is_active']]]);
    }

    public function test_admin_can_deactivate_a_therapist_and_revoke_their_tokens(): void
    {
        $admin = Admin::factory()->create();
        $therapist = Admin::factory()->therapist()->create();
        $therapistToken = $therapist->createToken('test')->plainTextToken;

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->patchJson('/api/admin/staff/'.$therapist->id, ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $therapist->id,
        ]);

        // The sanctum guard caches the resolved user, so a fresh guard is needed
        // to prove the revoked token no longer authenticates.
        Auth::forgetGuards();

        $this->withToken($therapistToken)
            ->getJson('/api/admin/me')
            ->assertUnauthorized();
    }

    public function test_admin_can_change_a_staff_role(): void
    {
        $admin = Admin::factory()->create();
        $therapist = Admin::factory()->therapist()->create();

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->patchJson('/api/admin/staff/'.$therapist->id, ['roles' => StaffRole::Admin->value])
            ->assertOk()
            ->assertJsonPath('data.roles', 'admin');
    }

    public function test_deactivated_staff_cannot_log_in(): void
    {
        $therapist = Admin::factory()->therapist()->inactive()->create([
            'email' => 'therapist@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/admin/login', [
            'email' => $therapist->email,
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_deactivated_staff_is_rejected_on_protected_requests(): void
    {
        $therapist = Admin::factory()->therapist()->inactive()->create();

        $this->withToken($therapist->createToken('test')->plainTextToken)
            ->getJson('/api/admin/me')
            ->assertForbidden();
    }

    public function test_therapist_cannot_access_the_admin_dashboard(): void
    {
        $therapist = Admin::factory()->therapist()->create();

        $this->actingAs($therapist, 'admin')
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_the_admin_dashboard(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertJsonPath('email', $admin->email);
    }
}
