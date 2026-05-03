<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cashless_type')) {
                $table->string('cashless_type', 30)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('payments', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('cashless_type');
            }
            if (! Schema::hasColumn('payments', 'other_bank_name')) {
                $table->string('other_bank_name', 100)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('payments', 'wallet_provider')) {
                $table->string('wallet_provider', 50)->nullable()->after('other_bank_name');
            }
            if (! Schema::hasColumn('payments', 'account_name')) {
                $table->string('account_name', 120)->nullable()->after('wallet_provider');
            }
            if (! Schema::hasColumn('payments', 'mobile_number')) {
                $table->string('mobile_number', 30)->nullable()->after('account_name');
            }
            if (! Schema::hasColumn('payments', 'approval_code')) {
                $table->string('approval_code', 100)->nullable()->after('reference_number');
            }
            if (! Schema::hasColumn('payments', 'card_type')) {
                $table->string('card_type', 20)->nullable()->after('approval_code');
            }
            if (! Schema::hasColumn('payments', 'terminal_provider')) {
                $table->string('terminal_provider', 80)->nullable()->after('card_type');
            }
            if (! Schema::hasColumn('payments', 'payment_channel')) {
                $table->string('payment_channel', 80)->nullable()->after('terminal_provider');
            }
            if (! Schema::hasColumn('payments', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('payment_channel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = [];
            foreach ([
                'cashless_type',
                'bank_name',
                'other_bank_name',
                'wallet_provider',
                'account_name',
                'mobile_number',
                'approval_code',
                'card_type',
                'terminal_provider',
                'payment_channel',
                'payment_notes',
            ] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
