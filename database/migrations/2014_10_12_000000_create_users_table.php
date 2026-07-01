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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('email_verified_at')->nullable();
            $table->string('phone_prefix')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->integer('verification_code')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('password', 1000)->nullable();
            $table->string('role')->default('admin');
            $table->string('profile_photo', 1000)->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->boolean('completed_profile')->default(false);
            $table->string('lang', 255)->default('en');
            $table->float('wallet')->nullable();
            $table->string('city_id', 255)->nullable();
            $table->string('latitude', 255)->nullable();
            $table->string('longitude', 255)->nullable();
            $table->integer('vat_number')->nullable();
            $table->integer('cr_number')->nullable();
            $table->integer('iban')->nullable();
            $table->float('hour_price')->nullable();
            $table->text('fcm_token')->nullable();
            $table->string('facebook_id', 1000)->nullable();
            $table->string('apple_id', 1000)->nullable();
            $table->string('google_id', 1000)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
