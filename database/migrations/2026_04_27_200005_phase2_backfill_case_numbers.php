<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Assigns sequential case_number values per branch to all existing funeral_cases.
 * Cases are numbered starting from 1, ordered by created_at then id (stable sort).
 *
 * This runs before Phase 3 adds the UNIQUE(branch_id, case_number) constraint.
 * New cases must be assigned a case_number at creation time via application code.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('funeral_cases', 'case_number')) {
            return;
        }

        $branchIds = DB::table('funeral_cases')
            ->distinct()
            ->whereNotNull('branch_id')
            ->pluck('branch_id');

        foreach ($branchIds as $branchId) {
            $caseIds = DB::table('funeral_cases')
                ->where('branch_id', $branchId)
                ->whereNull('case_number')
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($caseIds as $seq => $caseId) {
                DB::table('funeral_cases')
                    ->where('id', $caseId)
                    ->update(['case_number' => $seq + 1]);
            }
        }
    }

    public function down(): void
    {
        // Reset all case_numbers to null.
        if (Schema::hasColumn('funeral_cases', 'case_number')) {
            DB::table('funeral_cases')->update(['case_number' => null]);
        }
    }
};
