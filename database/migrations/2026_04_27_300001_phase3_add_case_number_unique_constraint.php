<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds UNIQUE(branch_id, case_number) to funeral_cases.
 *
 * Prerequisites (must be completed before running this):
 *   - Phase 1 migration 100007 has run (case_number column exists).
 *   - Phase 2 migration 200005 has run (all existing rows have a case_number).
 *
 * After this runs, application code MUST assign case_number when creating new cases.
 * Suggested helper — see FuneralCase::nextCaseNumber(int $branchId): int
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('funeral_cases', 'case_number')) {
            return;
        }

        // Abort if any NULL case_numbers remain — backfill must run first.
        $nullCount = DB::table('funeral_cases')->whereNull('case_number')->count();

        if ($nullCount > 0) {
            throw new \RuntimeException(
                "Cannot add unique constraint: {$nullCount} funeral_cases row(s) still have NULL case_number. " .
                'Run migration 2026_04_27_200005_phase2_backfill_case_numbers first.'
            );
        }

        // Check for duplicate (branch_id, case_number) pairs before adding constraint.
        $dupeCount = DB::select(
            'SELECT COUNT(*) as cnt FROM (
                SELECT branch_id, case_number, COUNT(*) as c
                FROM funeral_cases
                GROUP BY branch_id, case_number
                HAVING c > 1
             ) t'
        );

        if (! empty($dupeCount[0]->cnt)) {
            throw new \RuntimeException(
                'Cannot add unique constraint: duplicate (branch_id, case_number) pairs exist. ' .
                'Resolve duplicates before running this migration.'
            );
        }

        try {
            Schema::table('funeral_cases', function (Blueprint $table) {
                $table->unique(['branch_id', 'case_number'], 'funeral_cases_branch_case_number_unique');
            });
        } catch (\Throwable $e) {
            // Already exists.
            if (! str_contains($e->getMessage(), 'Duplicate key name')) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('funeral_cases', function (Blueprint $table) {
                $table->dropUnique('funeral_cases_branch_case_number_unique');
            });
        } catch (\Throwable) {
            // Index may not exist.
        }
    }
};
