<?php

namespace Tests\Feature;

use App\Models\Auth\Admin;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    public function test_admin_can_login_and_access_dashboard(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->get('/admin')
            ->assertOk()
            ->assertJsonPath('email', $admin->email);
    }

    public function test_invalid_admin_credentials_are_rejected(): void
    {
        $admin = Admin::factory()->create();

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => $admin->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_guests_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_logout(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
