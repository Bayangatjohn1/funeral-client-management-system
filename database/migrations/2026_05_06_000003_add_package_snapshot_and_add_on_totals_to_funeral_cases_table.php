<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('funeral_cases', 'package_name_snapshot')) {
                $table->string('package_name_snapshot')->nullable()->after('package_id');
            }
            if (! Schema::hasColumn('funeral_cases', 'package_price_snapshot')) {
                $table->decimal('package_price_snapshot', 12, 2)->nullable()->after('package_name_snapshot');
            }
            if (! Schema::hasColumn('funeral_cases', 'package_description_snapshot')) {
                $table->text('package_description_snapshot')->nullable()->after('package_price_snapshot');
            }
            if (! Schema::hasColumn('funeral_cases', 'package_inclusions_snapshot')) {
                $table->text('package_inclusions_snapshot')->nullable()->after('package_description_snapshot');
            }
            if (! Schema::hasColumn('funeral_cases', 'package_freebies_snapshot')) {
                $table->text('package_freebies_snapshot')->nullable()->after('package_inclusions_snapshot');
            }
            if (! Schema::hasColumn('funeral_cases', 'package_promo_snapshot')) {
                $table->text('package_promo_snapshot')->nullable()->after('package_freebies_snapshot');
            }
            if (! Schema::hasColumn('funeral_cases', 'package_discount_snapshot')) {
                $table->decimal('package_discount_snapshot', 12, 2)->nullable()->after('package_promo_snapshot');
            }
            if (! Schema::hasColumn('funeral_cases', 'add_ons_total_amount')) {
                $table->decimal('add_ons_total_amount', 12, 2)->default(0)->after('additional_service_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach ([
                'add_ons_total_amount',
                'package_discount_snapshot',
                'package_promo_snapshot',
                'package_freebies_snapshot',
                'package_inclusions_snapshot',
                'package_description_snapshot',
                'package_price_snapshot',
                'package_name_snapshot',
            ] as $column) {
                if (Schema::hasColumn('funeral_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
