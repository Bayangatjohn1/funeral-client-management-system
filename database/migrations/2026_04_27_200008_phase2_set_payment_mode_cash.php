<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sets payment_mode = 'cash' on all existing payment records.
 * All historical payments were cash-only (ENUM('CASH') constraint).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'payment_mode')) {
            return;
        }

        DB::table('payments')
            ->whereNull('payment_mode')
            ->update(['payment_mode' => 'cash']);
    }

    public function down(): void
    {
        // Not reversible.
    }
};
