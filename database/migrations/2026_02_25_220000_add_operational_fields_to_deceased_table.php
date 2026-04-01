<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            if (!Schema::hasColumn('deceased', 'wake_days')) {
                $table->unsignedTinyInteger('wake_days')->nullable()->after('interment');
            }
            if (!Schema::hasColumn('deceased', 'interment_at')) {
                $table->dateTime('interment_at')->nullable()->after('interment');
            }
            if (!Schema::hasColumn('deceased', 'transport_option')) {
                $table->string('transport_option', 30)->nullable()->after('place_of_cemetery');
            }
            if (!Schema::hasColumn('deceased', 'transport_notes')) {
                $table->string('transport_notes', 500)->nullable()->after('transport_option');
            }
            if (!Schema::hasColumn('deceased', 'coffin_length_cm')) {
                $table->decimal('coffin_length_cm', 6, 2)->nullable()->after('transport_notes');
            }
            if (!Schema::hasColumn('deceased', 'coffin_size')) {
                $table->string('coffin_size', 20)->nullable()->after('coffin_length_cm');
            }
            if (!Schema::hasColumn('deceased', 'embalming_required')) {
                $table->boolean('embalming_required')->default(true)->after('coffin_size');
            }
            if (!Schema::hasColumn('deceased', 'embalming_status')) {
                $table->string('embalming_status', 20)->nullable()->after('embalming_required');
            }
            if (!Schema::hasColumn('deceased', 'embalming_at')) {
                $table->dateTime('embalming_at')->nullable()->after('embalming_status');
            }
            if (!Schema::hasColumn('deceased', 'embalming_notes')) {
                $table->string('embalming_notes', 500)->nullable()->after('embalming_at');
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            try {
                $table->index('interment_at', 'deceased_interment_at_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
            try {
                $table->index('transport_option', 'deceased_transport_option_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
            try {
                $table->index('embalming_status', 'deceased_embalming_status_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("UPDATE deceased SET interment_at = CONCAT(interment, ' 09:00:00') WHERE interment IS NOT NULL AND interment_at IS NULL");
            DB::statement("UPDATE deceased SET wake_days = GREATEST(TIMESTAMPDIFF(DAY, died, interment), 1) WHERE wake_days IS NULL AND died IS NOT NULL AND interment IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            try {
                $table->dropIndex('deceased_interment_at_index');
            } catch (\Throwable $e) {
                // Ignore if index is missing.
            }
            try {
                $table->dropIndex('deceased_transport_option_index');
            } catch (\Throwable $e) {
                // Ignore if index is missing.
            }
            try {
                $table->dropIndex('deceased_embalming_status_index');
            } catch (\Throwable $e) {
                // Ignore if index is missing.
            }

            $dropColumns = [];
            foreach ([
                'wake_days',
                'interment_at',
                'transport_option',
                'transport_notes',
                'coffin_length_cm',
                'coffin_size',
                'embalming_required',
                'embalming_status',
                'embalming_at',
                'embalming_notes',
            ] as $column) {
                if (Schema::hasColumn('deceased', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

