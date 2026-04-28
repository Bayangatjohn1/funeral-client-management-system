<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds soft-delete support to the four core business tables.
 *
 * All existing rows are unaffected (deleted_at = NULL means not deleted).
 *
 * WHY: Hard deletes on clients, deceased, funeral_cases, and payments are
 * unrecoverable and leave no audit trail. Given that the FK constraints are
 * RESTRICT, hard deletes would already fail for records with children — but
 * soft deletes give a safe fallback for leaf records and enable recovery.
 *
 * NOTE: Eloquent's SoftDeletes trait automatically adds whereNull('deleted_at')
 * to all model queries, so no existing queries need to change. To intentionally
 * access soft-deleted records use ::withTrashed() or ::onlyTrashed().
 */
return new class extends Migration
{
    private array $tables = ['clients', 'deceased', 'funeral_cases', 'payments'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'deleted_at')) {
                    $t->softDeletes();
                    $t->index('deleted_at', "{$table}_deleted_at_idx");
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                try {
                    $t->dropIndex("{$table}_deleted_at_idx");
                } catch (\Throwable) {}

                if (Schema::hasColumn($table, 'deleted_at')) {
                    $t->dropSoftDeletes();
                }
            });
        }
    }
};
