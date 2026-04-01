<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'interment_at')) {
                $table->dateTime('interment_at')->nullable()->after('funeral_service_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (Schema::hasColumn('funeral_cases', 'interment_at')) {
                $table->dropColumn('interment_at');
            }
        });
    }
};
