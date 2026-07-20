<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (! Schema::hasColumn('packages', 'short_description')) {
                $table->text('short_description')->nullable()->after('name');
            }
        });

        Schema::table('package_inclusions', function (Blueprint $table) {
            if (! Schema::hasColumn('package_inclusions', 'service_type')) {
                $table->string('service_type', 40)->nullable()->after('package_id');
            }
            if (! Schema::hasColumn('package_inclusions', 'is_custom')) {
                $table->boolean('is_custom')->default(false)->after('service_type');
            }
            if (! Schema::hasColumn('package_inclusions', 'included_kilometers')) {
                $table->unsignedInteger('included_kilometers')->nullable()->after('inclusion_name');
            }
            if (! Schema::hasColumn('package_inclusions', 'price_per_excess_kilometer')) {
                $table->decimal('price_per_excess_kilometer', 12, 2)->nullable()->after('included_kilometers');
            }
            if (! Schema::hasColumn('package_inclusions', 'included_days')) {
                $table->unsignedSmallInteger('included_days')->nullable()->after('price_per_excess_kilometer');
            }
            if (! Schema::hasColumn('package_inclusions', 'price_per_extended_day')) {
                $table->decimal('price_per_extended_day', 12, 2)->nullable()->after('included_days');
            }
            if (! Schema::hasColumn('package_inclusions', 'casket_type')) {
                $table->string('casket_type')->nullable()->after('price_per_extended_day');
            }
        });

        Schema::table('package_freebies', function (Blueprint $table) {
            if (! Schema::hasColumn('package_freebies', 'freebie_catalog_id')) {
                $table->foreignId('freebie_catalog_id')->nullable()->after('package_id');
            }
            if (! Schema::hasColumn('package_freebies', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('freebie_name');
            }
            if (! Schema::hasColumn('package_freebies', 'unit')) {
                $table->string('unit', 40)->default('item')->after('quantity');
            }
            if (! Schema::hasColumn('package_freebies', 'is_custom')) {
                $table->boolean('is_custom')->default(true)->after('unit');
            }
        });

        DB::table('package_inclusions')
            ->whereNull('service_type')
            ->update([
                'service_type' => 'custom',
                'is_custom' => true,
            ]);

        DB::table('package_freebies')
            ->where(function ($query) {
                $query->whereNull('unit')->orWhere('unit', '');
            })
            ->update(['unit' => 'item']);
    }

    public function down(): void
    {
        Schema::table('package_freebies', function (Blueprint $table) {
            foreach (['freebie_catalog_id', 'quantity', 'unit', 'is_custom'] as $column) {
                if (Schema::hasColumn('package_freebies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('package_inclusions', function (Blueprint $table) {
            foreach ([
                'casket_type',
                'price_per_extended_day',
                'included_days',
                'price_per_excess_kilometer',
                'included_kilometers',
                'is_custom',
                'service_type',
            ] as $column) {
                if (Schema::hasColumn('package_inclusions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'short_description')) {
                $table->dropColumn('short_description');
            }
        });
    }
};
