<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds payment_mode and reference_number to the payments table.
 *
 * The existing method ENUM('CASH') column is kept for backward compatibility.
 * payment_mode is the canonical replacement with extended enum values.
 * reference_number is required only for bank_transfer payments.
 *
 * Phase 2 sets payment_mode = 'cash' on all existing records.
 * Phase 3 removes the UNIQUE(funeral_case_id) constraint to allow partial payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_mode')) {
                $table->enum('payment_mode', ['cash', 'bank_transfer'])
                    ->default('cash')
                    ->after('method');
            }

            if (! Schema::hasColumn('payments', 'reference_number')) {
                $table->string('reference_number', 100)
                    ->nullable()
                    ->after('payment_mode')
                    ->comment('Required when payment_mode = bank_transfer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['payment_mode', 'reference_number'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
