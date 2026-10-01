<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_scope')) {
            return;
        }

        DB::transaction(function (): void {
            DB::table('users')
                ->where('role', 'admin')
                ->whereIn('admin_scope', ['main', 'all_branches'])
                ->update([
                    'admin_scope' => 'system',
                    'branch_id' => null,
                ]);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'admin_scope')) {
            return;
        }

        DB::transaction(function (): void {
            $mainBranchId = DB::table('branches')
                ->where('branch_code', 'BR001')
                ->value('id');

            DB::table('users')
                ->where('role', 'admin')
                ->where('admin_scope', 'system')
                ->update([
                    'admin_scope' => 'main',
                    'branch_id' => $mainBranchId,
                ]);
        });
    }
};
