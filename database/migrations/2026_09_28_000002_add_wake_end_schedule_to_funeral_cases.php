<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->date('wake_end_date')->nullable()->after('wake_start_time');
            $table->time('wake_end_time')->nullable()->after('wake_end_date');
            $table->index(['branch_id', 'wake_start_date', 'wake_end_date'], 'funeral_cases_branch_wake_window_index');
        });

        // Legacy records had no explicit wake endpoint. Using the ceremony
        // schedule preserves the closest known operational boundary without
        // changing any saved pricing snapshot or financial amount.
        DB::table('funeral_cases')
            ->whereNull('wake_end_date')
            ->update([
                'wake_end_date' => DB::raw('funeral_service_at'),
                'wake_end_time' => DB::raw('funeral_service_time'),
            ]);
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropIndex('funeral_cases_branch_wake_window_index');
            $table->dropColumn(['wake_end_date', 'wake_end_time']);
        });
    }
};
