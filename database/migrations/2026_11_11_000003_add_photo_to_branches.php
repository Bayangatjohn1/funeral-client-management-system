<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->string('photo_mime', 40)->nullable();
            $table->string('photo_name', 180)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['photo_path', 'photo_mime', 'photo_name']));
    }
};
