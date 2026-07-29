<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('case_documents') && ! Schema::hasColumn('case_documents', 'contract_snapshot')) {
            Schema::table('case_documents', function (Blueprint $table) {
                $table->json('contract_snapshot')->nullable()->after('contract_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('case_documents') && Schema::hasColumn('case_documents', 'contract_snapshot')) {
            Schema::table('case_documents', function (Blueprint $table) {
                $table->dropColumn('contract_snapshot');
            });
        }
    }
};
