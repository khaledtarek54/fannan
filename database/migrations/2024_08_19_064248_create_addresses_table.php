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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('city_id')->constrained('cities');
            $table->string('name');
            $table->text('description')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id', 'user_id']);
            $table->index(['id', 'city_id']);
            $table->index(['id', 'user_id', 'city_id']);
            $table->index(['id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
