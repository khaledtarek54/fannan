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
        Schema::create('bidding_order_artists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('artist_id')->constrained('users');
            $table->foreignId('subcategory_id')->constrained('sub_categories');
            $table->double('cost');
            $table->boolean('is_accepted')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id', 'deleted_at']);
            $table->index(['id', 'order_id']);
            $table->index(['id', 'artist_id']);
            $table->index(['id', 'order_id', 'artist_id']);
            $table->index(['id', 'order_id', 'artist_id', 'subcategory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bidding_order_artists');
    }
};
