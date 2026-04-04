<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure case_code uniqueness per branch (business key).
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->unique(['branch_id', 'case_code'], 'funeral_cases_branch_code_unique');
            } catch (\Throwable $e) {
                // Ignore if already present or data not compatible.
            }

            // Index to speed dashboards by branch + status.
            try {
                $table->index(['branch_id', 'case_status', 'payment_status'], 'funeral_cases_branch_status_payment_idx');
            } catch (\Throwable $e) {
                //
            }
        });

        // Payments history performance.
        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->index(['funeral_case_id', 'paid_at'], 'payments_case_paid_at_idx');
            } catch (\Throwable $e) {
                //
            }
            try {
                $table->index(['branch_id', 'payment_status_after_payment'], 'payments_branch_status_idx');
            } catch (\Throwable $e) {
                //
            }
        });

        // Audit log performance & referential integrity.
        Schema::table('audit_logs', function (Blueprint $table) {
            try {
                // Composite index for common filters.
                $table->index(['created_at', 'status', 'action_type', 'branch_id'], 'audit_logs_created_status_action_branch_idx');
            } catch (\Throwable $e) {
                //
            }

            if (Schema::hasColumn('audit_logs', 'target_branch_id')) {
                // Add FK for target_branch_id when branches table exists.
                try {
                    $table->foreign('target_branch_id', 'audit_logs_target_branch_fk')
                        ->references('id')
                        ->on('branches')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // FK may already exist or data mismatch; leave untouched.
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['audit_logs_created_status_action_branch_idx'] as $idx) {
                try { $table->dropIndex($idx); } catch (\Throwable $e) {}
            }
            try { $table->dropForeign('audit_logs_target_branch_fk'); } catch (\Throwable $e) {}
        });

        Schema::table('payments', function (Blueprint $table) {
            foreach (['payments_case_paid_at_idx', 'payments_branch_status_idx'] as $idx) {
                try { $table->dropIndex($idx); } catch (\Throwable $e) {}
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            try { $table->dropUnique('funeral_cases_branch_code_unique'); } catch (\Throwable $e) {}
            try { $table->dropIndex('funeral_cases_branch_status_payment_idx'); } catch (\Throwable $e) {}
        });
    }
};
