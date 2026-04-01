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
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('coffin_type');
            $table->enum('discount_type', ['NONE', 'SENIOR', 'CUSTOM'])->default('NONE')->after('subtotal_amount');
            $table->enum('discount_value_type', ['AMOUNT', 'PERCENT'])->default('AMOUNT')->after('discount_type');
            $table->decimal('discount_value', 12, 2)->default(0)->after('discount_value_type');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_value');
            $table->string('discount_note')->nullable()->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_amount',
                'discount_type',
                'discount_value_type',
                'discount_value',
                'discount_amount',
                'discount_note',
            ]);
        });
    }
};
