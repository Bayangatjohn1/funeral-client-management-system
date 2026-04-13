<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->index(
                    ['branch_id', 'entry_source', 'interment_at'],
                    'funeral_cases_branch_entry_interment_idx'
                );
            } catch (\Throwable $e) {
                //
            }

            try {
                $table->index(
                    ['branch_id', 'entry_source', 'funeral_service_at'],
                    'funeral_cases_branch_entry_funeral_idx'
                );
            } catch (\Throwable $e) {
                //
            }

            try {
                $table->index(
                    ['branch_id', 'payment_status', 'balance_amount'],
                    'funeral_cases_branch_payment_balance_idx'
                );
            } catch (\Throwable $e) {
                //
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->dropIndex('funeral_cases_branch_entry_interment_idx');
            } catch (\Throwable $e) {
                //
            }

            try {
                $table->dropIndex('funeral_cases_branch_entry_funeral_idx');
            } catch (\Throwable $e) {
                //
            }

            try {
                $table->dropIndex('funeral_cases_branch_payment_balance_idx');
            } catch (\Throwable $e) {
                //
            }
        });
    }
};

