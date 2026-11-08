<?php

namespace App\Models\Auth;

use App\Enums\StaffRole;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }

    protected $table = 'auth_admin';

    protected $fillable = ['name', 'email', 'roles', 'is_active', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'roles' => StaffRole::class,
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->roles === StaffRole::Admin;
    }
}
