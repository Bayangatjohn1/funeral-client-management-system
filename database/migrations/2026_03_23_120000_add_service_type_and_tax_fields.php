<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'service_type')) {
                $table->string('service_type', 100)->nullable()->after('case_code');
            }
            if (!Schema::hasColumn('funeral_cases', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('funeral_cases', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach (['tax_amount', 'tax_rate', 'service_type'] as $column) {
                if (Schema::hasColumn('funeral_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
