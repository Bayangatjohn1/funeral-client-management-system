<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds case_number (sequential integer per branch) to funeral_cases.
 * This is nullable initially so existing rows can be backfilled by Phase 2.
 * Phase 3 adds the UNIQUE(branch_id, case_number) constraint after backfill.
 *
 * case_number differs from case_code:
 *   case_number = auto-assigned sequential INT per branch (e.g. 1, 2, 3...)
 *   case_code   = human-readable identifier string (e.g. FC-2026-001)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('funeral_cases', 'case_number')) {
                $table->unsignedInteger('case_number')
                    ->nullable()
                    ->after('package_id')
                    ->comment('Sequential per-branch case counter; UNIQUE(branch_id, case_number) added in Phase 3');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (Schema::hasColumn('funeral_cases', 'case_number')) {
                $table->dropColumn('case_number');
            }
        });
    }
};
