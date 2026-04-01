<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicates = DB::table('payments')
            ->select('funeral_case_id')
            ->groupBy('funeral_case_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('funeral_case_id');

        foreach ($duplicates as $caseId) {
            $latestId = DB::table('payments')
                ->where('funeral_case_id', $caseId)
                ->max('id');

            DB::table('payments')
                ->where('funeral_case_id', $caseId)
                ->where('id', '!=', $latestId)
                ->delete();
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('funeral_case_id', 'payments_funeral_case_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_funeral_case_id_unique');
        });
    }
};
