<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', fn (Blueprint $table) => $table->string('client_relationship', 100)->nullable());
    }

    public function down(): void
    {
        Schema::table('funeral_cases', fn (Blueprint $table) => $table->dropColumn('client_relationship'));
    }
};
