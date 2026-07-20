<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('case_documents') && ! Schema::hasColumn('case_documents', 'contract_number')) {
            Schema::table('case_documents', function (Blueprint $table) {
                $table->string('contract_number', 40)->nullable()->after('document_type');
                $table->unique('contract_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('case_documents') && Schema::hasColumn('case_documents', 'contract_number')) {
            Schema::table('case_documents', function (Blueprint $table) {
                $table->dropUnique(['contract_number']);
                $table->dropColumn('contract_number');
            });
        }
    }
};
