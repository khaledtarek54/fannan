<?php

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
        Schema::table('settings', function (Blueprint $table) {
            DB::statement("ALTER TABLE settings MODIFY type ENUM('tax', 'terms_and_conditions', 'privacy_policy', 'about_us', 'help_and_support', 'platform_fees', 'vat', 'call_center_call', 'artist_acknowledgement')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            DB::statement("ALTER TABLE settings MODIFY type ENUM('tax', 'terms_and_conditions', 'privacy_policy', 'about_us', 'help_and_support', 'platform_fees', 'vat', 'call_center_call')");

        });
    }
};
