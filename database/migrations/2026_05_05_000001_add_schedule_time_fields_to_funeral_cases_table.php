<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('funeral_cases', 'wake_start_date')) {
                $table->date('wake_start_date')->nullable()->after('wake_location');
            }

            if (! Schema::hasColumn('funeral_cases', 'wake_start_time')) {
                $table->time('wake_start_time')->nullable()->after('wake_start_date');
            }

            if (! Schema::hasColumn('funeral_cases', 'funeral_service_time')) {
                $table->time('funeral_service_time')->nullable()->after('funeral_service_at');
            }

            if (! Schema::hasColumn('funeral_cases', 'interment_time')) {
                $table->time('interment_time')->nullable()->after('interment_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach (['interment_time', 'funeral_service_time', 'wake_start_time', 'wake_start_date'] as $column) {
                if (Schema::hasColumn('funeral_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
