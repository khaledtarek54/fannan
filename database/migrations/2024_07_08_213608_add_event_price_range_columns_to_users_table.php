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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('hour_price');
            $table->integer('from_range_event')->default(0);
            $table->integer('to_range_event')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->float('hour_price')->nullable();
            $table->dropColumn('from_range_event');
            $table->dropColumn('to_range_event');
        });
    }
};
