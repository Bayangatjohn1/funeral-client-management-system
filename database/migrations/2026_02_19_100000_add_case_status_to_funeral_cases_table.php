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
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->enum('case_status', ['DRAFT', 'ACTIVE', 'COMPLETED'])->default('DRAFT')->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropColumn('case_status');
        });
    }
};
