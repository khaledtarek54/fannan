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
        Schema::table('user_categories', function (Blueprint $table) {
            $table->foreignId('range_id')->after('subcategory_id')->nullable()->constrained('price_ranges');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_categories', function (Blueprint $table) {
            $table->dropForeign(['range_id']);
            $table->dropColumn('range_id');
        });
    }
};
