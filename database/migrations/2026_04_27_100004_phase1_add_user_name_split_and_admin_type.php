<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds first_name, last_name, and admin_type to users.
 * The existing name and admin_scope columns are preserved.
 * admin_type maps to: main_branch_admin | branch_admin (vs admin_scope: main | branch).
 * Phase 2 backfills from name → first_name/last_name, admin_scope → admin_type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            // Canonical admin_type with descriptive enum values.
            if (! Schema::hasColumn('users', 'admin_type')) {
                $table->enum('admin_type', ['main_branch_admin', 'branch_admin'])
                    ->nullable()
                    ->after('role')
                    ->comment('Only set when role = admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['first_name', 'last_name', 'admin_type'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
