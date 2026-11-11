<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('funeral_cases', fn (Blueprint $table) => $table->string('chapel_address')->nullable());
    }

    public function down(): void
    {
        Schema::table('funeral_cases', fn (Blueprint $table) => $table->dropColumn('chapel_address'));
    }
};
