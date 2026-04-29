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
            if (!Schema::hasColumn('payments', 'payment_record_no')) {
                $table->string('payment_record_no', 40)->nullable()->after('receipt_number');
            }
            if (!Schema::hasColumn('payments', 'accounting_reference_no')) {
                $table->string('accounting_reference_no', 100)->nullable()->after('payment_record_no');
            }
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method', 30)->default('cash')->after('payment_mode');
            }
            if (!Schema::hasColumn('payments', 'bank_or_channel')) {
                $table->string('bank_or_channel', 80)->nullable()->after('reference_number');
            }
            if (!Schema::hasColumn('payments', 'other_bank_or_channel')) {
                $table->string('other_bank_or_channel', 100)->nullable()->after('bank_or_channel');
            }
            if (!Schema::hasColumn('payments', 'transaction_reference_no')) {
                $table->string('transaction_reference_no', 100)->nullable()->after('other_bank_or_channel');
            }
            if (!Schema::hasColumn('payments', 'sender_name')) {
                $table->string('sender_name', 120)->nullable()->after('transaction_reference_no');
            }
            if (!Schema::hasColumn('payments', 'transfer_datetime')) {
                $table->dateTime('transfer_datetime')->nullable()->after('sender_name');
            }
            if (!Schema::hasColumn('payments', 'received_by')) {
                $table->string('received_by', 120)->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('payments', 'encoded_by')) {
                $table->foreignId('encoded_by')->nullable()->after('received_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'remarks')) {
                $table->text('remarks')->nullable()->after('encoded_by');
            }
        });

        $this->backfillPaymentRecordNumbers();

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->unique('payment_record_no', 'payments_payment_record_no_unique');
            } catch (\Throwable $e) {
                // Index already exists in some environments.
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropUnique('payments_payment_record_no_unique');
            } catch (\Throwable $e) {
                // Ignore if missing.
            }

            if (Schema::hasColumn('payments', 'encoded_by')) {
                try {
                    $table->dropForeign(['encoded_by']);
                } catch (\Throwable $e) {
                    // Ignore if missing.
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $columns = [];
            foreach ([
                'payment_record_no',
                'accounting_reference_no',
                'payment_method',
                'bank_or_channel',
                'other_bank_or_channel',
                'transaction_reference_no',
                'sender_name',
                'transfer_datetime',
                'received_by',
                'encoded_by',
                'remarks',
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

    private function backfillPaymentRecordNumbers(): void
    {
        $legacyColumn = Schema::hasColumn('payments', 'receipt_no')
            ? 'receipt_no'
            : (Schema::hasColumn('payments', 'receipt_number') ? 'receipt_number' : null);

        if ($legacyColumn) {
            DB::table('payments')
                ->whereNull('payment_record_no')
                ->whereNotNull($legacyColumn)
                ->update(['payment_record_no' => DB::raw($legacyColumn)]);
        }

        if (Schema::hasColumn('payments', 'payment_mode')) {
            DB::table('payments')
                ->whereNull('payment_method')
                ->update(['payment_method' => DB::raw("COALESCE(payment_mode, 'cash')")]);
        }

        if (Schema::hasColumn('payments', 'recorded_by')) {
            DB::table('payments')
                ->whereNull('encoded_by')
                ->whereNotNull('recorded_by')
                ->update(['encoded_by' => DB::raw('recorded_by')]);
        }

        if (Schema::hasColumn('payments', 'reference_number')) {
            DB::table('payments')
                ->whereNull('transaction_reference_no')
                ->whereNotNull('reference_number')
                ->update(['transaction_reference_no' => DB::raw('reference_number')]);
        }
    }
};
