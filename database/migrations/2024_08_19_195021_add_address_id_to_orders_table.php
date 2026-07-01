<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->after('client_id')->constrained('addresses');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
            $table->dropColumn('address_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropColumn('address_id');
            $table->foreignId('city_id')->constrained('cities');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address_description')->nullable();
        });
    }
};
