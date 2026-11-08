<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasTable('auth_users')) {
            Schema::rename('users', 'auth_users');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('auth_users') && ! Schema::hasTable('users')) {
            Schema::rename('auth_users', 'users');
        }
    }
};
