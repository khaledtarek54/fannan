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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable();
            $table->enum('type', ['direct', 'bidding'])->default('direct');
            $table->foreignId('client_id')->nullable()->constrained('users');
            $table->foreignId('artist_id')->constrained('users');
            $table->foreignId('city_id')->constrained('cities');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address_description')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
