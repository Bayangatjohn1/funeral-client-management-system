<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills branch_type from branch_code.
 * Convention: branch_code = 'BR001' is always the main branch.
 * All other branches are type 'branch'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('branches', 'branch_type')) {
            return;
        }

        // Main branch
        DB::table('branches')
            ->where('branch_code', 'BR001')
            ->update(['branch_type' => 'main']);

        // All other branches
        DB::table('branches')
            ->where('branch_code', '!=', 'BR001')
            ->update(['branch_type' => 'branch']);
    }

    public function down(): void
    {
        // Backfill cannot be meaningfully reversed.
    }
};
