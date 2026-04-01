<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'relationship_to_deceased')) {
                $table->string('relationship_to_deceased')->nullable()->after('full_name');
            }
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
            if (!Schema::hasColumn('deceased', 'civil_status')) {
                $table->string('civil_status', 30)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('deceased', 'senior_citizen_status')) {
                $table->boolean('senior_citizen_status')->default(false)->after('civil_status');
            }
            if (!Schema::hasColumn('deceased', 'senior_citizen_id_number')) {
                $table->string('senior_citizen_id_number', 100)->nullable()->after('senior_citizen_status');
            }
            if (!Schema::hasColumn('deceased', 'pwd_status')) {
                $table->boolean('pwd_status')->default(false)->after('senior_citizen_id_number');
            }
            if (!Schema::hasColumn('deceased', 'pwd_id_number')) {
                $table->string('pwd_id_number', 100)->nullable()->after('pwd_status');
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'service_requested_at')) {
                $table->date('service_requested_at')->nullable()->after('case_code');
            }
            if (!Schema::hasColumn('funeral_cases', 'wake_location')) {
                $table->string('wake_location')->nullable()->after('coffin_type');
            }
            if (!Schema::hasColumn('funeral_cases', 'funeral_service_at')) {
                $table->date('funeral_service_at')->nullable()->after('wake_location');
            }
            if (!Schema::hasColumn('funeral_cases', 'additional_services')) {
                $table->text('additional_services')->nullable()->after('funeral_service_at');
            }
            if (!Schema::hasColumn('funeral_cases', 'additional_service_amount')) {
                $table->decimal('additional_service_amount', 12, 2)->default(0)->after('additional_services');
            }
            if (!Schema::hasColumn('funeral_cases', 'initial_payment_type')) {
                $table->string('initial_payment_type', 20)->nullable()->after('payment_timing');
            }
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'service_requested_at',
                'wake_location',
                'funeral_service_at',
                'additional_services',
                'additional_service_amount',
                'initial_payment_type',
            ] as $column) {
                if (Schema::hasColumn('funeral_cases', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'civil_status',
                'senior_citizen_status',
                'senior_citizen_id_number',
                'pwd_status',
                'pwd_id_number',
            ] as $column) {
                if (Schema::hasColumn('deceased', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'relationship_to_deceased',
                'email',
                'valid_id_type',
                'valid_id_number',
            ] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
