<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds normalized name, date_of_birth, and is_senior columns to deceased.
 * Existing full_name, born, and senior_citizen_status columns are preserved.
 * Phase 2 backfills these columns. Phase 4 (future) removes old columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            if (! Schema::hasColumn('deceased', 'first_name')) {
                $table->string('first_name')->nullable()->after('client_id');
            }

            if (! Schema::hasColumn('deceased', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('deceased', 'last_name')) {
                $table->string('last_name')->nullable()->after('middle_name');
            }

            if (! Schema::hasColumn('deceased', 'suffix')) {
                $table->string('suffix', 20)->nullable()->after('last_name')
                    ->comment('e.g. Jr, Sr, III, IV');
            }

            // Canonical date_of_birth (mirrors existing born column).
            if (! Schema::hasColumn('deceased', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('age');
            }

            // Canonical is_senior flag (mirrors senior_citizen_status).
            if (! Schema::hasColumn('deceased', 'is_senior')) {
                $table->boolean('is_senior')->default(false)->after('date_of_birth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'date_of_birth', 'is_senior'] as $col) {
                if (Schema::hasColumn('deceased', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
