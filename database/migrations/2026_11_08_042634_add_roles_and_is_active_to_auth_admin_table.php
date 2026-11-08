<?php

use App\Enums\StaffRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auth_admin', function (Blueprint $table) {
            $table->string('roles')->default('therapist')->after('email');
            $table->boolean('is_active')->default(true)->after('roles');
        });

        // Every existing staff account was an administrator before roles existed.
        DB::table('auth_admin')->update(['roles' => StaffRole::Admin->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_admin', function (Blueprint $table) {
            $table->dropColumn(['roles', 'is_active']);
        });
    }
};
