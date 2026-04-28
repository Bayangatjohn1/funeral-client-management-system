<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the service_details table to normalize wake/service/internment data
 * that is currently scattered across funeral_cases and deceased tables.
 *
 * Mapping from current schema:
 *   start_of_wake    ← funeral_cases.service_requested_at (best approximation)
 *   internment_date  ← funeral_cases.interment_at (date part)
 *   wake_days        ← deceased.wake_days
 *   wake_location    ← funeral_cases.wake_location
 *   cemetery_place   ← deceased.place_of_cemetery
 *   case_status      ← mapped from funeral_cases.case_status (DRAFT→pending, ACTIVE→ongoing, COMPLETED→completed)
 *
 * The source columns in funeral_cases and deceased are preserved for backward compatibility.
 * Phase 2 backfills this table from existing records.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_details')) {
            Schema::create('service_details', function (Blueprint $table) {
                $table->id();

                $table->foreignId('funeral_case_id')
                    ->unique()  // 1:1 per case
                    ->constrained('funeral_cases')
                    ->cascadeOnDelete();

                $table->date('start_of_wake')->nullable();
                $table->date('internment_date')->nullable();
                $table->unsignedSmallInteger('wake_days')->nullable();
                $table->string('wake_location')->nullable();
                $table->string('cemetery_place')->nullable();

                // Uses spec enum values (lowercase) separate from funeral_cases.case_status (UPPERCASE).
                $table->enum('case_status', ['pending', 'ongoing', 'completed'])->default('pending');

                $table->timestamps();

                $table->index('internment_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_details');
    }
};
