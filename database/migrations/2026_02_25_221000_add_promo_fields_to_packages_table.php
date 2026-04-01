<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'promo_label')) {
                $table->string('promo_label', 120)->nullable()->after('freebies');
            }
            if (!Schema::hasColumn('packages', 'promo_value_type')) {
                $table->string('promo_value_type', 20)->nullable()->after('promo_label');
            }
            if (!Schema::hasColumn('packages', 'promo_value')) {
                $table->decimal('promo_value', 12, 2)->nullable()->after('promo_value_type');
            }
            if (!Schema::hasColumn('packages', 'promo_starts_at')) {
                $table->dateTime('promo_starts_at')->nullable()->after('promo_value');
            }
            if (!Schema::hasColumn('packages', 'promo_ends_at')) {
                $table->dateTime('promo_ends_at')->nullable()->after('promo_starts_at');
            }
            if (!Schema::hasColumn('packages', 'promo_is_active')) {
                $table->boolean('promo_is_active')->default(false)->after('promo_ends_at');
            }
        });

        Schema::table('packages', function (Blueprint $table) {
            try {
                $table->index('promo_is_active', 'packages_promo_is_active_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
            try {
                $table->index(['promo_starts_at', 'promo_ends_at'], 'packages_promo_window_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            try {
                $table->dropIndex('packages_promo_is_active_index');
            } catch (\Throwable $e) {
                // Ignore if index is missing.
            }
            try {
                $table->dropIndex('packages_promo_window_index');
            } catch (\Throwable $e) {
                // Ignore if index is missing.
            }

            $dropColumns = [];
            foreach ([
                'promo_label',
                'promo_value_type',
                'promo_value',
                'promo_starts_at',
                'promo_ends_at',
                'promo_is_active',
            ] as $column) {
                if (Schema::hasColumn('packages', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

