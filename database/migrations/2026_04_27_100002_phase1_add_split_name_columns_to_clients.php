<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds normalized name columns to clients.
 * The existing full_name column is kept untouched for backward compatibility.
 * Phase 2 will backfill these columns. Phase 4 (future) removes full_name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'first_name')) {
                $table->string('first_name')->nullable()->after('branch_id');
            }

            if (! Schema::hasColumn('clients', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('clients', 'last_name')) {
                $table->string('last_name')->nullable()->after('middle_name');
            }

            if (! Schema::hasColumn('clients', 'suffix')) {
                $table->string('suffix', 20)->nullable()->after('last_name')
                    ->comment('e.g. Jr, Sr, III, IV');
            }

            // Canonical short column name for the relationship field.
            // The existing relationship_to_deceased column is kept as-is.
            if (! Schema::hasColumn('clients', 'relationship')) {
                $table->string('relationship')->nullable()->after('suffix');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'relationship'] as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
