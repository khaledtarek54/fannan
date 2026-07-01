<?php

use App\Enums\FileType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_gallery_works', function (Blueprint $table) {
            $table->enum('type', array_column(FileType::cases(), 'value'))->default(FileType::IMAGE->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_gallery_works', function (Blueprint $table) {
            //
        });
    }
};
