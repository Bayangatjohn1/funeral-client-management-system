<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('funeral_cases', 'pricing_snapshot')) {
                $table->text('pricing_snapshot')->nullable()->after('package_discount_snapshot');
            }
        });

        Schema::table('case_add_ons', function (Blueprint $table) {
            if (! Schema::hasColumn('case_add_ons', 'add_on_catalog_id')) {
                $table->foreignId('add_on_catalog_id')
                    ->nullable()
                    ->after('package_add_on_id')
                    ->constrained('add_on_catalogs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('case_add_ons', function (Blueprint $table) {
            if (Schema::hasColumn('case_add_ons', 'add_on_catalog_id')) {
                $table->dropConstrainedForeignId('add_on_catalog_id');
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            if (Schema::hasColumn('funeral_cases', 'pricing_snapshot')) {
                $table->dropColumn('pricing_snapshot');
            }
        });
    }
};
