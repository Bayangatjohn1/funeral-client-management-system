<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('hearse_catalogs')) {
            Schema::create('hearse_catalogs', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        Schema::table('funeral_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('funeral_cases', 'hearse_catalog_id')) {
                $table->foreignId('hearse_catalog_id')->nullable()->after('package_id')
                    ->constrained('hearse_catalogs')->nullOnDelete();
            }
            if (! Schema::hasColumn('funeral_cases', 'hearse_name_snapshot')) {
                $table->string('hearse_name_snapshot', 150)->nullable()->after('hearse_catalog_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (Schema::hasColumn('funeral_cases', 'hearse_catalog_id')) {
                $table->dropConstrainedForeignId('hearse_catalog_id');
            }
            if (Schema::hasColumn('funeral_cases', 'hearse_name_snapshot')) {
                $table->dropColumn('hearse_name_snapshot');
            }
        });
        Schema::dropIfExists('hearse_catalogs');
    }
};
