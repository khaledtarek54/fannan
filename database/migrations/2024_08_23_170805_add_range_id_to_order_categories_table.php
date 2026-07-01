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
        Schema::table('order_categories', function (Blueprint $table) {
            $table->double('from_range')->nullable();
            $table->double('to_range')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_categories', function (Blueprint $table) {
            $table->dropColumn('from_range');
            $table->dropColumn('to_range');
        });
    }
};
