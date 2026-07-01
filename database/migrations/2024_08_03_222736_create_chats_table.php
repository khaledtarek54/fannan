<?php

use App\Enums\MessageType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_user_id')->constrained('users');
            $table->boolean('is_read')->default(false);
            $table->enum('type', array_column(MessageType::cases(), 'value'))->default(MessageType::TEXT->value);
            $table->text('message');
            $table->foreignId('reply_to')->nullable()->constrained('chats');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id', 'deleted_at']);
            $table->index(['id', 'from_user_id']);
            $table->index(['id', 'to_user_id']);
            $table->index(['id', 'from_user_id', 'to_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
