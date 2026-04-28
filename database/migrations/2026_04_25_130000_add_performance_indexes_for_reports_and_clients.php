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
                    ['verification_status', 'created_at', 'branch_id'],
                    'funeral_cases_verified_created_branch_idx'
                );
            } catch (\Throwable $e) {
                //
            }

            try {
                $table->index(
                    ['branch_id', 'client_id', 'created_at'],
                    'funeral_cases_branch_client_created_idx'
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
                $table->dropIndex('funeral_cases_verified_created_branch_idx');
            } catch (\Throwable $e) {
                //
            }

            try {
                $table->dropIndex('funeral_cases_branch_client_created_idx');
            } catch (\Throwable $e) {
                //
            }
        });
    }
};
