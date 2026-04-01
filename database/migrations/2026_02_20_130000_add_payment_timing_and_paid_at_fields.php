<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->enum('payment_timing', ['SELECTION_DAY', 'AFTER_BURIAL'])
                ->nullable()
                ->after('payment_status');
            $table->dateTime('paid_at')->nullable()->after('payment_timing');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('paid_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropColumn(['payment_timing', 'paid_at']);
        });
    }
};
