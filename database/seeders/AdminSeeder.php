<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\Auth\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'roles' => StaffRole::Admin,
            'is_active' => true,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
