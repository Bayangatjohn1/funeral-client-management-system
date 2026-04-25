<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'action_label')) {
                $table->string('action_label', 160)->nullable()->after('action');
            }
            if (!Schema::hasColumn('audit_logs', 'status')) {
                $table->string('status', 20)->default('success')->after('target_branch_id');
            }
            if (!Schema::hasColumn('audit_logs', 'remarks')) {
                $table->text('remarks')->nullable()->after('status');
            }
            if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->string('user_agent', 255)->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('audit_logs', 'transaction_id')) {
                $table->string('transaction_id', 64)->nullable()->after('user_agent');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            // Add lightweight indexes for faster defense forensics.
            if (!self::hasIndex('audit_logs', 'audit_logs_status_index')) {
                $table->index('status', 'audit_logs_status_index');
            }
            if (!self::hasIndex('audit_logs', 'audit_logs_transaction_id_index')) {
                $table->index('transaction_id', 'audit_logs_transaction_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach ([
                'audit_logs_status_index',
                'audit_logs_transaction_id_index',
            ] as $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (\Throwable $e) {
                    // index might not exist; ignore
                }
            }

            $dropColumns = [
                'action_label',
                'status',
                'remarks',
                'ip_address',
                'user_agent',
                'transaction_id',
            ];

            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private static function hasIndex(string $table, string $indexName): bool
    {
        try {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $results = DB::select("PRAGMA index_list('{$table}')");

                return collect($results)->contains(function ($index) use ($indexName) {
                    $name = $index->name ?? $index->Key_name ?? null;

                    return $name === $indexName;
                });
            }

            $results = DB::select("SHOW INDEX FROM `{$table}` WHERE `Key_name` = ?", [$indexName]);
            return count($results) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
