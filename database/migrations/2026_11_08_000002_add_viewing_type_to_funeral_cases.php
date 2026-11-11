<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->string('viewing_type', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropColumn('viewing_type');
        });
    }
};
