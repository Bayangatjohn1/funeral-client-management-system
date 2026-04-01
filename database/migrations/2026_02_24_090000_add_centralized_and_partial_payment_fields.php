<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_encode_any_branch')) {
                $table->boolean('can_encode_any_branch')->default(false);
            }
            if (!Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position', 100)->nullable();
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'total_paid')) {
                $table->decimal('total_paid', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('funeral_cases', 'balance_amount')) {
                $table->decimal('balance_amount', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('funeral_cases', 'reported_branch_id')) {
                $table->foreignId('reported_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('funeral_cases', 'reporter_name')) {
                $table->string('reporter_name')->nullable();
            }
            if (!Schema::hasColumn('funeral_cases', 'reporter_contact')) {
                $table->string('reporter_contact', 50)->nullable();
            }
            if (!Schema::hasColumn('funeral_cases', 'reported_at')) {
                $table->dateTime('reported_at')->nullable();
            }
            if (!Schema::hasColumn('funeral_cases', 'encoded_by')) {
                $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            try {
                DB::statement('ALTER TABLE payments ADD INDEX payments_funeral_case_id_non_unique_index (funeral_case_id)');
            } catch (\Throwable $e) {
                // Ignore if index already exists or cannot be created in this state.
            }
        }

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropUnique('payments_funeral_case_id_unique');
            } catch (\Throwable $e) {
                // Ignore if unique index does not exist in this environment.
            }

            if (!Schema::hasColumn('payments', 'recorded_by')) {
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        DB::table('users')
            ->where('role', 'staff')
            ->whereIn('branch_id', function ($query) {
                $query->select('id')->from('branches')->where('branch_code', 'BR001');
            })
            ->update(['can_encode_any_branch' => true]);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE funeral_cases MODIFY payment_status ENUM('UNPAID','PARTIAL','PAID') NOT NULL DEFAULT 'UNPAID'");
        }

        DB::table('funeral_cases')
            ->select('id', 'total_amount')
            ->orderBy('id')
            ->chunkById(200, function ($cases) {
                foreach ($cases as $case) {
                    $paid = (float) DB::table('payments')
                        ->where('funeral_case_id', $case->id)
                        ->sum('amount');

                    $total = round((float) $case->total_amount, 2);
                    $paid = round($paid, 2);
                    $balance = round(max($total - $paid, 0), 2);

                    $status = 'UNPAID';
                    if ($paid > 0 && $paid < $total) {
                        $status = 'PARTIAL';
                    }
                    if ($paid >= $total && $total > 0) {
                        $status = 'PAID';
                        $balance = 0;
                    }

                    DB::table('funeral_cases')
                        ->where('id', $case->id)
                        ->update([
                            'total_paid' => $paid,
                            'balance_amount' => $balance,
                            'payment_status' => $status,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE funeral_cases MODIFY payment_status ENUM('UNPAID','PAID') NOT NULL DEFAULT 'UNPAID'");
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'recorded_by')) {
                $table->dropForeign(['recorded_by']);
                $table->dropColumn('recorded_by');
            }
            try {
                $table->dropIndex('payments_funeral_case_id_non_unique_index');
            } catch (\Throwable $e) {
                // Ignore if helper index does not exist.
            }
            $table->unique('funeral_case_id', 'payments_funeral_case_id_unique');
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            if (Schema::hasColumn('funeral_cases', 'encoded_by')) {
                $table->dropForeign(['encoded_by']);
                $table->dropColumn('encoded_by');
            }
            if (Schema::hasColumn('funeral_cases', 'reported_branch_id')) {
                $table->dropForeign(['reported_branch_id']);
                $table->dropColumn('reported_branch_id');
            }
            $dropColumns = [];
            foreach (['total_paid', 'balance_amount', 'reporter_name', 'reporter_contact', 'reported_at'] as $col) {
                if (Schema::hasColumn('funeral_cases', $col)) {
                    $dropColumns[] = $col;
                }
            }
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['can_encode_any_branch', 'contact_number', 'position', 'address'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $dropColumns[] = $col;
                }
            }
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
