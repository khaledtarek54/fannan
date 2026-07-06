<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [SECURITY][R2-C5] Give the phone-verification code a short TTL and a per-account attempt
 * ceiling so it can no longer be brute-forced (the code stays a plaintext 4-digit value, but
 * an attacker now gets only a handful of guesses within a small time window before it dies).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            $table->unsignedTinyInteger('verification_code_attempts')->default(0)->after('verification_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_code_expires_at', 'verification_code_attempts']);
        });
    }
};
