<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropUnique('funeral_cases_case_code_unique');
            $table->unique(['branch_id', 'case_code'], 'funeral_cases_branch_id_case_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropUnique('funeral_cases_branch_id_case_code_unique');
            $table->unique('case_code');
        });
    }
};

