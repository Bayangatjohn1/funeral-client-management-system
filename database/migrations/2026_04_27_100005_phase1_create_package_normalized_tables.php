<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates normalized package_inclusions and package_freebies tables.
 * The existing packages.inclusions and packages.freebies TEXT columns are preserved.
 * Phase 2 seeds these tables from the existing TEXT data.
 * These are the 3NF replacement for the denormalized TEXT columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('package_inclusions')) {
            Schema::create('package_inclusions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')
                    ->constrained('packages')
                    ->cascadeOnDelete();
                $table->string('inclusion_name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('package_id');
            });
        }

        if (! Schema::hasTable('package_freebies')) {
            Schema::create('package_freebies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')
                    ->constrained('packages')
                    ->cascadeOnDelete();
                $table->string('freebie_name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('package_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_freebies');
        Schema::dropIfExists('package_inclusions');
    }
};
