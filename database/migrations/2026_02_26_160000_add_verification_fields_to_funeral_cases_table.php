<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'verification_status')) {
                $table->string('verification_status', 20)->default('VERIFIED')->after('entry_source');
            }
            if (!Schema::hasColumn('funeral_cases', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verification_status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('funeral_cases', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('funeral_cases', 'verification_note')) {
                $table->string('verification_note', 500)->nullable()->after('verified_at');
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->index('verification_status', 'funeral_cases_verification_status_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
            try {
                $table->index('verified_at', 'funeral_cases_verified_at_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });

        DB::table('funeral_cases')
            ->whereNull('verification_status')
            ->update(['verification_status' => 'VERIFIED']);
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->dropIndex('funeral_cases_verification_status_index');
            } catch (\Throwable $e) {
                // Ignore if index does not exist.
            }
            try {
                $table->dropIndex('funeral_cases_verified_at_index');
            } catch (\Throwable $e) {
                // Ignore if index does not exist.
            }

            if (Schema::hasColumn('funeral_cases', 'verified_by')) {
                $table->dropForeign(['verified_by']);
            }

            $dropColumns = [];
            foreach (['verification_status', 'verified_by', 'verified_at', 'verification_note'] as $column) {
                if (Schema::hasColumn('funeral_cases', $column)) {
                    $dropColumns[] = $column;
                }
            }
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

