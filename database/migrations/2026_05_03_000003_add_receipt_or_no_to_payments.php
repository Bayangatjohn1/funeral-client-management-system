<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'receipt_or_no')) {
                $table->string('receipt_or_no', 100)->nullable()->after('accounting_reference_no');
            }
        });

        if (Schema::hasColumn('payments', 'receipt_or_no') && Schema::hasColumn('payments', 'accounting_reference_no')) {
            DB::table('payments')
                ->whereNull('receipt_or_no')
                ->whereNotNull('accounting_reference_no')
                ->update(['receipt_or_no' => DB::raw('accounting_reference_no')]);
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'receipt_or_no')) {
                $table->dropColumn('receipt_or_no');
            }
        });
    }
};
