<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'branch_type')) {
                $table->enum('branch_type', ['main', 'branch'])
                    ->default('branch')
                    ->after('branch_code')
                    ->comment('main = head office, branch = satellite branch');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'branch_type')) {
                $table->dropColumn('branch_type');
            }
        });
    }
};
