<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('funeral_cases')
                ->where('payment_timing', 'SELECTION_DAY')
                ->update(['payment_timing' => 'SAME_DAY']);
            return;
        }

        DB::statement("ALTER TABLE funeral_cases MODIFY payment_timing ENUM('SELECTION_DAY','SAME_DAY','AFTER_BURIAL') NULL");
        DB::table('funeral_cases')
            ->where('payment_timing', 'SELECTION_DAY')
            ->update(['payment_timing' => 'SAME_DAY']);
        DB::statement("ALTER TABLE funeral_cases MODIFY payment_timing ENUM('SAME_DAY','AFTER_BURIAL') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('funeral_cases')
                ->where('payment_timing', 'SAME_DAY')
                ->update(['payment_timing' => 'SELECTION_DAY']);
            return;
        }

        DB::statement("ALTER TABLE funeral_cases MODIFY payment_timing ENUM('SELECTION_DAY','SAME_DAY','AFTER_BURIAL') NULL");
        DB::table('funeral_cases')
            ->where('payment_timing', 'SAME_DAY')
            ->update(['payment_timing' => 'SELECTION_DAY']);
        DB::statement("ALTER TABLE funeral_cases MODIFY payment_timing ENUM('SELECTION_DAY','AFTER_BURIAL') NULL");
    }
};
