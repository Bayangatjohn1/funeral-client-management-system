<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->string('funeral_ceremony_location')->nullable();
            $table->boolean('ceremony_at_wake_location')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropColumn(['funeral_ceremony_location', 'ceremony_at_wake_location']);
        });
    }
};
