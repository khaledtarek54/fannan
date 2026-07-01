<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_transactions', function (Blueprint $table) {
            $table->id();
            // Required for linking payment to callback
            $table->string('customer_reference')->unique();
            // Fields from EasyKash callback — all optional
            $table->string('user_id')->nullable();          // PAID, FAILED, etc.
            $table->string('status')->nullable();          // PAID, FAILED, etc.
            $table->string('easykash_ref')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('product_type')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->json('callback_payload')->nullable();
            $table->boolean('is_paid')->default(false);

            // Fields used when creating payment
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_transactions');
    }
};
