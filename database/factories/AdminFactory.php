<?php

namespace Database\Factories;

use App\Enums\StaffRole;
use App\Models\Auth\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'roles' => StaffRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the staff account is a therapist.
     */
    public function therapist(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => StaffRole::Therapist,
        ]);
    }

    /**
     * Indicate that the staff account is deactivated.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
