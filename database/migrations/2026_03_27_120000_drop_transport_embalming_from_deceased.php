<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            $columns = [
                'transport_option',
                'transport_notes',
                'embalming_required',
                'embalming_status',
                'embalming_at',
                'embalming_notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deceased', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            if (!Schema::hasColumn('deceased', 'transport_option')) {
                $table->string('transport_option', 30)->nullable()->after('place_of_cemetery');
            }
            if (!Schema::hasColumn('deceased', 'transport_notes')) {
                $table->string('transport_notes', 500)->nullable()->after('transport_option');
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
    }
};
