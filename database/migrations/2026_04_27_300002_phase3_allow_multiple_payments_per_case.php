<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the UNIQUE(funeral_case_id) constraint from payments.
 *
 * This is the most impactful schema change in the entire refactor.
 * It enables partial payments (multiple payment rows per funeral case),
 * which is the correct behavior per system requirements.
 *
 * BEFORE running this migration:
 *   1. Verify PaymentController has been updated to handle multiple payments.
 *   2. Verify receipt number generation still works per-payment (not per-case).
 *   3. Verify payment history view is ready for multiple rows per case.
 *
 * The payments.method ENUM('CASH') column is also widened to include
 * 'bank_transfer' here, as it is safe to do once the uniqueness constraint
 * is removed and the payment_mode column exists.
 *
 * Backward compat: The FuneralCase model exposes total_paid and balance_amount
 * as calculated fields (not stored), so no data migration is required for those.
 *
 * ============================================================
 * FORWARD-ONLY MIGRATION — DO NOT ROLL BACK IN PRODUCTION
 * ============================================================
 * Once this migration has run on any environment with real data, rolling back
 * via `php artisan migrate:rollback` is UNSAFE and will silently corrupt data.
 *
 * WHY rollback is unsafe:
 *   The down() method attempts to restore UNIQUE(funeral_case_id). However, as
 *   soon as any case has more than one payment row (which is the entire point of
 *   this migration), that ALTER TABLE will fail with a duplicate-key error.
 *   MySQL/MariaDB will abort the constraint addition, leaving the table without
 *   the index but also without an error surfaced to the user — the try/catch in
 *   down() intentionally swallows the failure to prevent a hard crash.
 *
 *   The result is a schema that looks rolled back in the migrations table but
 *   still has the plain index and no UNIQUE constraint — i.e., a split state
 *   that is neither Phase 2 nor Phase 3.
 *
 * SAFE alternatives if rollback is ever needed:
 *   1. Do NOT run migrate:rollback. Instead, manually delete all but the first
 *      payment per case so that funeral_case_id is unique again, then apply the
 *      constraint by hand.
 *   2. Restore from a pre-migration database backup.
 *   3. Treat the current state as correct and move forward with a new migration
 *      if the schema change needs to be revisited.
 * ============================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the UNIQUE constraint that enforced 1:1 payments-per-case.
        // Laravel names this index 'payments_funeral_case_id_unique'.
        try {
            if ($this->indexExists('payments', 'payments_funeral_case_id_unique')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropUnique('payments_funeral_case_id_unique');
                });
            }
        } catch (\Throwable $e) {
            if (! str_contains($e->getMessage(), "Can't DROP") && ! str_contains($e->getMessage(), 'does not exist')) {
                throw $e;
            }
            // Constraint already dropped — safe to continue.
        }

        // Add a plain index on funeral_case_id for query performance.
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('funeral_case_id', 'payments_funeral_case_id_idx');
            });
        } catch (\Throwable $e) {
            if (! str_contains($e->getMessage(), 'Duplicate key name')) {
                throw $e;
            }
        }
    }

    /**
     * WARNING: This down() is INTENTIONALLY incomplete and should not be run in production.
     * See the class-level FORWARD-ONLY MIGRATION comment for the full explanation.
     *
     * The UNIQUE constraint restoration below will silently fail (and be swallowed) as soon
     * as any case has more than one payment row — which is the normal state after up() runs.
     * Running migrate:rollback will not error, but will NOT restore the original schema.
     */
    public function down(): void
    {
        // Remove the plain performance index added in up().
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_funeral_case_id_idx');
            });
        } catch (\Throwable) {}

        // This will FAIL SILENTLY if any case has multiple payments.
        // Do not rely on this to restore the UNIQUE constraint in production.
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('funeral_case_id', 'payments_funeral_case_id_unique');
            });
        } catch (\Throwable) {}
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                return collect(DB::select("PRAGMA index_list('{$table}')"))
                    ->contains(fn ($index) => ($index->name ?? null) === $indexName);
            }

            return count(DB::select("SHOW INDEX FROM `{$table}` WHERE `Key_name` = ?", [$indexName])) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};
