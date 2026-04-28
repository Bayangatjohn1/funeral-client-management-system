<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds two composite indexes covering the most common dashboard/report filter patterns
 * that were missing from earlier index migrations:
 *
 *  1. funeral_cases(branch_id, case_status) — staff dashboard counts ongoing (DRAFT/ACTIVE) cases
 *     and the funeral-case index page filters by case_status. Previously required a partial
 *     scan of the broader (branch_id, payment_status, balance_amount) index or a full table scan.
 *
 *  2. payments(branch_id, payment_status_after_payment) — payment history KPI query counts
 *     UNPAID records; no index existed on this column combination.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->index(
                    ['branch_id', 'case_status'],
                    'funeral_cases_branch_case_status_idx'
                );
            } catch (\Throwable) {
                // Already exists.
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->index(
                    ['branch_id', 'payment_status_after_payment'],
                    'payments_branch_status_after_idx'
                );
            } catch (\Throwable) {
                // Already exists.
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->dropIndex('funeral_cases_branch_case_status_idx');
            } catch (\Throwable) {}
        });

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropIndex('payments_branch_status_after_idx');
            } catch (\Throwable) {}
        });
    }
};
