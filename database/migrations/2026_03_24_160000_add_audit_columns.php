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
            if (!Schema::hasColumn('audit_logs', 'actor_role')) {
                $table->string('actor_role', 20)->nullable()->after('actor_id');
            }
            if (!Schema::hasColumn('audit_logs', 'action_type')) {
                $table->string('action_type', 30)->nullable()->after('action');
            }
            if (!Schema::hasColumn('audit_logs', 'entity_type')) {
                $table->string('entity_type', 120)->nullable()->change();
            }
            if (!Schema::hasColumn('audit_logs', 'target_branch_id')) {
                $table->unsignedBigInteger('target_branch_id')->nullable()->after('branch_id');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $indexMap = [
                'action_type' => 'audit_logs_action_type_index',
                'entity_type' => 'audit_logs_entity_type_index',
                'branch_id' => 'audit_logs_branch_id_index',
                'target_branch_id' => 'audit_logs_target_branch_id_index',
            ];

            foreach ($indexMap as $column => $indexName) {
                if (!self::indexExists($table->getTable(), $indexName)) {
                    $table->index($column, $indexName);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $indexNames = [
                'audit_logs_action_type_index',
                'audit_logs_entity_type_index',
                'audit_logs_branch_id_index',
                'audit_logs_target_branch_id_index',
            ];

            foreach ($indexNames as $indexName) {
                try { $table->dropIndex($indexName); } catch (\Throwable $e) {}
            }
            foreach (['actor_role', 'action_type', 'target_branch_id'] as $col) {
                if (Schema::hasColumn('audit_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private static function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $results = DB::select("PRAGMA index_list('{$table}')");

            return collect($results)->contains(function ($index) use ($indexName) {
                $name = $index->name ?? $index->Key_name ?? null;

                return $name === $indexName;
            });
        }

        $results = DB::select("SHOW INDEX FROM `{$table}` WHERE `Key_name` = ?", [$indexName]);

        return count($results) > 0;
    }
};
