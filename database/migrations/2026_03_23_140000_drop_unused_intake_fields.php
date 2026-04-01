<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach (['email', 'valid_id_type', 'valid_id_number'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            foreach (['gender', 'civil_status', 'pwd_status', 'pwd_id_number'] as $column) {
                if (Schema::hasColumn('deceased', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach ([
                'transport_option',
                'transport_notes',
                'coffin_length_cm',
                'coffin_size',
                'embalming_required',
                'embalming_status',
                'embalming_at',
                'embalming_notes',
            ] as $column) {
                if (Schema::hasColumn('funeral_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'email')) {
                $table->string('email')->nullable()->after('contact_number');
            }
            if (!Schema::hasColumn('clients', 'valid_id_type')) {
                $table->string('valid_id_type', 100)->nullable()->after('email');
            }
            if (!Schema::hasColumn('clients', 'valid_id_number')) {
                $table->string('valid_id_number', 100)->nullable()->after('valid_id_type');
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            if (!Schema::hasColumn('deceased', 'gender')) {
                $table->string('gender', 20)->nullable()->after('died');
            }
            if (!Schema::hasColumn('deceased', 'civil_status')) {
                $table->string('civil_status', 30)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('deceased', 'pwd_status')) {
                $table->boolean('pwd_status')->default(false)->after('senior_citizen_id_number');
            }
            if (!Schema::hasColumn('deceased', 'pwd_id_number')) {
                $table->string('pwd_id_number', 100)->nullable()->after('pwd_status');
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'transport_option')) {
                $table->string('transport_option', 30)->nullable()->after('place_of_cemetery');
            }
            if (!Schema::hasColumn('funeral_cases', 'transport_notes')) {
                $table->string('transport_notes', 500)->nullable()->after('transport_option');
            }
            if (!Schema::hasColumn('funeral_cases', 'coffin_length_cm')) {
                $table->decimal('coffin_length_cm', 8, 2)->nullable()->after('transport_notes');
            }
            if (!Schema::hasColumn('funeral_cases', 'coffin_size')) {
                $table->string('coffin_size', 20)->nullable()->after('coffin_length_cm');
            }
            if (!Schema::hasColumn('funeral_cases', 'embalming_required')) {
                $table->boolean('embalming_required')->default(false)->after('coffin_size');
            }
            if (!Schema::hasColumn('funeral_cases', 'embalming_status')) {
                $table->string('embalming_status', 20)->nullable()->after('embalming_required');
            }
            if (!Schema::hasColumn('funeral_cases', 'embalming_at')) {
                $table->dateTime('embalming_at')->nullable()->after('embalming_status');
            }
            if (!Schema::hasColumn('funeral_cases', 'embalming_notes')) {
                $table->string('embalming_notes', 500)->nullable()->after('embalming_at');
            }
        });
    }
};
