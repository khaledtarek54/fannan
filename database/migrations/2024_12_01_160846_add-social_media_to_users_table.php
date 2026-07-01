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
            $table->string('whatsapp')->nullable();
            $table->string('facebook', '512')->nullable();
            $table->string('instagram', '512')->nullable();
            $table->string('twiteer', '512')->nullable();
            $table->string('snapchat', '512')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['facebook', 'instagram', 'twiteer','snapchat']);
        });
    }
};
