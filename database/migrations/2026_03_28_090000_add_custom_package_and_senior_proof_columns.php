<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            if (!Schema::hasColumn('deceased', 'senior_proof_path')) {
                $table->string('senior_proof_path')->nullable()->after('photo_path');
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'custom_package_name')) {
                $table->string('custom_package_name', 150)->nullable()->after('package_id');
            }
            if (!Schema::hasColumn('funeral_cases', 'custom_package_price')) {
                $table->decimal('custom_package_price', 12, 2)->default(0)->after('custom_package_name');
            }
            if (!Schema::hasColumn('funeral_cases', 'custom_package_inclusions')) {
                $table->text('custom_package_inclusions')->nullable()->after('custom_package_price');
            }
            if (!Schema::hasColumn('funeral_cases', 'custom_package_freebies')) {
                $table->text('custom_package_freebies')->nullable()->after('custom_package_inclusions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach ([
                'custom_package_name',
                'custom_package_price',
                'custom_package_inclusions',
                'custom_package_freebies',
            ] as $column) {
                if (Schema::hasColumn('funeral_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            if (Schema::hasColumn('deceased', 'senior_proof_path')) {
                $table->dropColumn('senior_proof_path');
            }
        });
    }
};
