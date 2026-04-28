<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes 6 schema integrity bugs identified in the design review:
 *
 *  1. Drop UNIQUE(client_id, branch_id) on deceased — was incorrectly preventing a
 *     client from having multiple deceased records in the same branch, contradicting
 *     the intended 1:M Client→Deceased relationship.
 *
 *  2. Add UNIQUE(deceased_id) on funeral_cases — enforces the 1:1 Deceased→FuneralCase
 *     relationship that Deceased::funeralCase() (hasOne) implies but the DB never enforced.
 *     Aborts with a clear error if duplicate deceased_id values are found first.
 *
 *  3. Drop duplicate UNIQUE(branch_id, case_code) — added twice under different names:
 *     'funeral_cases_branch_id_case_code_unique' (2026_02_21_220000) and
 *     'funeral_cases_branch_code_unique' (2026_04_01_001000). The first one is kept.
 *
 *  4. Add INDEX(actor_id) on audit_logs — "all actions by user X" was a full table scan.
 *
 *  5. Drop admin_type from users — added in Phase 1 but never read by any application
 *     code; all admin-type logic reads admin_scope exclusively.
 *
 *  6. Widen payments.method ENUM to include BANK_TRANSFER so it can accurately mirror
 *     payment_mode instead of always showing CASH for bank transfer payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix 1: drop the wrongly-restrictive unique constraint on deceased.
        //
        // MySQL error 1553: the unique index cannot be dropped while
        // deceased_client_branch_foreign (added by harden_financial_schema)
        // is using it as its backing index on the referencing side.
        // Correct order: drop FK → drop unique index → add plain index → re-add FK.
        //
        // NOTE: try/catch must wrap Schema::table() itself, not calls inside the
        // Blueprint closure — the SQL executes AFTER the closure returns.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            try {
                Schema::table('deceased', function (Blueprint $table) {
                    $table->dropForeign('deceased_client_branch_foreign');
                });
            } catch (\Throwable) {
                // FK already absent or named differently in this environment.
            }

            try {
                Schema::table('deceased', function (Blueprint $table) {
                    $table->dropUnique('deceased_client_id_branch_id_unique');
                });
            } catch (\Throwable) {
                // Already removed or never existed in this environment.
            }

            // Add a plain (non-unique) index so the FK can be re-created and
            // queries on (client_id, branch_id) still benefit from an index.
            try {
                Schema::table('deceased', function (Blueprint $table) {
                    $table->index(['client_id', 'branch_id'], 'deceased_client_id_branch_id_index');
                });
            } catch (\Throwable) {
                // Already exists.
            }

            // Re-add the cross-branch FK now backed by the plain index.
            try {
                Schema::table('deceased', function (Blueprint $table) {
                    $table->foreign(['client_id', 'branch_id'], 'deceased_client_branch_foreign')
                        ->references(['id', 'branch_id'])
                        ->on('clients')
                        ->restrictOnDelete();
                });
            } catch (\Throwable) {
                // Already exists.
            }
        }

        // Fix 2: add UNIQUE(deceased_id) to funeral_cases.
        // Abort if duplicate deceased_id values already exist — manual cleanup required.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $dupeCount = DB::select(
                'SELECT COUNT(*) AS cnt FROM (
                    SELECT deceased_id, COUNT(*) AS c
                    FROM funeral_cases
                    GROUP BY deceased_id
                    HAVING c > 1
                ) t'
            );
            if (! empty($dupeCount[0]->cnt)) {
                throw new \RuntimeException(
                    'Cannot add UNIQUE(deceased_id): duplicate deceased_id values exist in funeral_cases. ' .
                    'Resolve the duplicates manually before running this migration.'
                );
            }
        }

        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->unique('deceased_id', 'funeral_cases_deceased_id_unique');
            } catch (\Throwable) {
                // Already exists.
            }
        });

        // Fix 3: drop the duplicate case_code unique index added by 2026_04_01_001000.
        // The original 'funeral_cases_branch_id_case_code_unique' from 2026_02_21_220000 stays.
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->dropUnique('funeral_cases_branch_code_unique');
            } catch (\Throwable) {
                // Already removed or never existed.
            }
        });

        // Fix 4: add actor_id index on audit_logs.
        Schema::table('audit_logs', function (Blueprint $table) {
            try {
                $table->index('actor_id', 'audit_logs_actor_id_idx');
            } catch (\Throwable) {
                // Already exists.
            }
        });

        // Fix 5: drop admin_type column (never read; admin_scope is the authoritative column).
        if (Schema::hasColumn('users', 'admin_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('admin_type');
            });
        }

        // Fix 6: widen payments.method ENUM to include BANK_TRANSFER.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE payments MODIFY method ENUM('CASH','BANK_TRANSFER') NOT NULL DEFAULT 'CASH'");
        }
    }

    public function down(): void
    {
        // Restore payments.method to CASH-only; downgrade data first.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('payments')->where('method', 'BANK_TRANSFER')->update(['method' => 'CASH']);
            DB::statement("ALTER TABLE payments MODIFY method ENUM('CASH') NOT NULL DEFAULT 'CASH'");
        }

        // Restore admin_type column.
        if (! Schema::hasColumn('users', 'admin_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('admin_type', ['main_branch_admin', 'branch_admin'])
                    ->nullable()
                    ->after('role')
                    ->comment('Only set when role = admin');
            });
        }

        // Remove actor_id index.
        try {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_actor_id_idx');
            });
        } catch (\Throwable) {}

        // Restore the duplicate index (match original state).
        try {
            Schema::table('funeral_cases', function (Blueprint $table) {
                $table->unique(['branch_id', 'case_code'], 'funeral_cases_branch_code_unique');
            });
        } catch (\Throwable) {}

        // Remove UNIQUE(deceased_id).
        try {
            Schema::table('funeral_cases', function (Blueprint $table) {
                $table->dropUnique('funeral_cases_deceased_id_unique');
            });
        } catch (\Throwable) {}

        // Restore UNIQUE(client_id, branch_id) on deceased.
        // Must drop FK → drop plain index → add unique index → re-add FK.
        try {
            Schema::table('deceased', function (Blueprint $table) {
                $table->dropForeign('deceased_client_branch_foreign');
            });
        } catch (\Throwable) {}

        try {
            Schema::table('deceased', function (Blueprint $table) {
                $table->dropIndex('deceased_client_id_branch_id_index');
            });
        } catch (\Throwable) {}

        try {
            Schema::table('deceased', function (Blueprint $table) {
                $table->unique(['client_id', 'branch_id'], 'deceased_client_id_branch_id_unique');
            });
        } catch (\Throwable) {}

        try {
            Schema::table('deceased', function (Blueprint $table) {
                $table->foreign(['client_id', 'branch_id'], 'deceased_client_branch_foreign')
                    ->references(['id', 'branch_id'])
                    ->on('clients')
                    ->restrictOnDelete();
            });
        } catch (\Throwable) {}
    }
};
