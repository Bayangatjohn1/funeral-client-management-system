<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admin_scope')) {
                $table->string('admin_scope', 20)->nullable()->after('role');
            }
        });

        $mainBranchId = DB::table('branches')
            ->where('branch_code', 'BR001')
            ->value('id');

        if ($mainBranchId) {
            DB::table('users')
                ->where('role', 'admin')
                ->where('branch_id', $mainBranchId)
                ->update(['admin_scope' => 'main']);
        }

        DB::table('users')
            ->where('role', 'admin')
            ->whereNull('admin_scope')
            ->update(['admin_scope' => 'branch']);

        DB::table('users')
            ->where('role', '!=', 'admin')
            ->update(['admin_scope' => null]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_scope')) {
                $table->dropColumn('admin_scope');
            }
        });
    }
};
