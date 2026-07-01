<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [SECURITY] Adds an explicit admin flag so the Filament panel can be gated to
 * real admins only. See docs/SECURITY_ISSUES.md A1. Defaults to false, so after
 * deploy NO account can reach /admin until a legitimate admin is flagged
 * (UPDATE users SET is_admin = 1 WHERE ... — do this for your admin accounts).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
