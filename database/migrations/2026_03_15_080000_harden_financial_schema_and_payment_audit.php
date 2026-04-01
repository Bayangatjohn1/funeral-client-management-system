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
            if (!Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number', 40)->nullable()->after('id');
            }
            if (!Schema::hasColumn('payments', 'balance_after_payment')) {
                $table->decimal('balance_after_payment', 12, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'payment_status_after_payment')) {
                $table->string('payment_status_after_payment', 20)->nullable()->after('balance_after_payment');
            }
        });

        $this->backfillPaymentAuditColumns();

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->unique('receipt_number', 'payments_receipt_number_unique');
            } catch (\Throwable $e) {
                // Ignore if index already exists in this environment.
            }
        });

        if (Schema::hasColumn('funeral_cases', 'payment_timing')) {
            Schema::table('funeral_cases', function (Blueprint $table) {
                $table->dropColumn('payment_timing');
            });
        }

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE funeral_cases MODIFY discount_type ENUM('NONE','SENIOR','PWD','CUSTOM') NOT NULL DEFAULT 'NONE'");

        $this->syncBranchIdsForCompositeIntegrity();
        $this->rebuildRestrictiveForeignKeys();
        $this->addCompositeIntegrityIndexes();
        $this->addCompositeIntegrityForeignKeys();
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->dropCompositeIntegrityForeignKeys();
            $this->dropCompositeIntegrityIndexes();
            $this->restoreLegacyForeignKeys();
            DB::statement("ALTER TABLE funeral_cases MODIFY discount_type ENUM('NONE','SENIOR','CUSTOM') NOT NULL DEFAULT 'NONE'");
        }

        if (!Schema::hasColumn('funeral_cases', 'payment_timing')) {
            Schema::table('funeral_cases', function (Blueprint $table) {
                $table->enum('payment_timing', ['SELECTION_DAY', 'AFTER_BURIAL'])
                    ->nullable()
                    ->after('payment_status');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropUnique('payments_receipt_number_unique');
            } catch (\Throwable $e) {
                // Ignore if index is already missing.
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['receipt_number', 'balance_after_payment', 'payment_status_after_payment'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    private function backfillPaymentAuditColumns(): void
    {
        DB::table('funeral_cases')
            ->select(['id', 'total_amount', 'branch_id'])
            ->orderBy('id')
            ->chunkById(100, function ($cases) {
                foreach ($cases as $case) {
                    $runningPaid = 0.0;

                    $payments = DB::table('payments')
                        ->where('funeral_case_id', $case->id)
                        ->orderByRaw('COALESCE(paid_at, paid_date, created_at) asc')
                        ->orderBy('id')
                        ->get();

                    foreach ($payments as $payment) {
                        $runningPaid = round($runningPaid + (float) $payment->amount, 2);
                        $balance = round(max((float) $case->total_amount - $runningPaid, 0), 2);
                        $status = $runningPaid <= 0
                            ? 'UNPAID'
                            : ($balance > 0 ? 'PARTIAL' : 'PAID');

                        $paidAt = $payment->paid_at ?: $payment->paid_date ?: $payment->created_at;
                        $year = now()->format('Y');
                        if (!empty($paidAt)) {
                            try {
                                $year = \Carbon\Carbon::parse($paidAt)->format('Y');
                            } catch (\Throwable $e) {
                                // Keep fallback year.
                            }
                        }

                        DB::table('payments')
                            ->where('id', $payment->id)
                            ->update([
                                'branch_id' => $case->branch_id,
                                'receipt_number' => $payment->receipt_number ?: sprintf('RCPT-%s-%06d', $year, $payment->id),
                                'balance_after_payment' => $balance,
                                'payment_status_after_payment' => $status,
                            ]);
                    }
                }
            });
    }

    private function syncBranchIdsForCompositeIntegrity(): void
    {
        DB::table('deceased')
            ->join('clients', 'deceased.client_id', '=', 'clients.id')
            ->whereColumn('deceased.branch_id', '!=', 'clients.branch_id')
            ->update(['deceased.branch_id' => DB::raw('clients.branch_id')]);

        DB::table('funeral_cases')
            ->join('clients', 'funeral_cases.client_id', '=', 'clients.id')
            ->whereColumn('funeral_cases.branch_id', '!=', 'clients.branch_id')
            ->update(['funeral_cases.branch_id' => DB::raw('clients.branch_id')]);

        DB::table('payments')
            ->join('funeral_cases', 'payments.funeral_case_id', '=', 'funeral_cases.id')
            ->whereColumn('payments.branch_id', '!=', 'funeral_cases.branch_id')
            ->update(['payments.branch_id' => DB::raw('funeral_cases.branch_id')]);
    }

    private function rebuildRestrictiveForeignKeys(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            try {
                $table->dropForeign(['branch_id']);
            } catch (\Throwable $e) {
                // Ignore if foreign key name differs or is already missing.
            }
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
        });

        Schema::table('deceased', function (Blueprint $table) {
            foreach (['branch_id', 'client_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                    // Ignore if foreign key name differs or is already missing.
                }
            }

            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach (['branch_id', 'client_id', 'deceased_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                    // Ignore if foreign key name differs or is already missing.
                }
            }

            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('deceased_id')->references('id')->on('deceased')->restrictOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            foreach (['funeral_case_id', 'branch_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                    // Ignore if foreign key name differs or is already missing.
                }
            }

            $table->foreign('funeral_case_id')->references('id')->on('funeral_cases')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
        });
    }

    private function addCompositeIntegrityIndexes(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            try {
                $table->unique(['id', 'branch_id'], 'clients_id_branch_id_unique');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            try {
                $table->unique(['id', 'client_id'], 'deceased_id_client_id_unique');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
            try {
                $table->unique(['id', 'branch_id'], 'deceased_id_branch_id_unique');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
            try {
                $table->unique(['client_id', 'branch_id'], 'deceased_client_id_branch_id_unique');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->unique(['id', 'branch_id'], 'funeral_cases_id_branch_id_unique');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });
    }

    private function addCompositeIntegrityForeignKeys(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            try {
                $table->foreign(['client_id', 'branch_id'], 'deceased_client_branch_foreign')
                    ->references(['id', 'branch_id'])
                    ->on('clients')
                    ->restrictOnDelete();
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->foreign(['client_id', 'branch_id'], 'funeral_cases_client_branch_foreign')
                    ->references(['id', 'branch_id'])
                    ->on('clients')
                    ->restrictOnDelete();
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
            try {
                $table->foreign(['deceased_id', 'branch_id'], 'funeral_cases_deceased_branch_foreign')
                    ->references(['id', 'branch_id'])
                    ->on('deceased')
                    ->restrictOnDelete();
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
            try {
                $table->foreign(['deceased_id', 'client_id'], 'funeral_cases_deceased_client_foreign')
                    ->references(['id', 'client_id'])
                    ->on('deceased')
                    ->restrictOnDelete();
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->foreign(['funeral_case_id', 'branch_id'], 'payments_case_branch_foreign')
                    ->references(['id', 'branch_id'])
                    ->on('funeral_cases')
                    ->restrictOnDelete();
            } catch (\Throwable $e) {
                // Ignore if foreign key already exists.
            }
        });
    }

    private function dropCompositeIntegrityForeignKeys(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropForeign('payments_case_branch_foreign');
            } catch (\Throwable $e) {
                // Ignore if already missing.
            }
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach ([
                'funeral_cases_client_branch_foreign',
                'funeral_cases_deceased_branch_foreign',
                'funeral_cases_deceased_client_foreign',
            ] as $foreignKey) {
                try {
                    $table->dropForeign($foreignKey);
                } catch (\Throwable $e) {
                    // Ignore if already missing.
                }
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            try {
                $table->dropForeign('deceased_client_branch_foreign');
            } catch (\Throwable $e) {
                // Ignore if already missing.
            }
        });
    }

    private function dropCompositeIntegrityIndexes(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            try {
                $table->dropUnique('funeral_cases_id_branch_id_unique');
            } catch (\Throwable $e) {
                // Ignore if already missing.
            }
        });

        Schema::table('deceased', function (Blueprint $table) {
            foreach ([
                'deceased_id_client_id_unique',
                'deceased_id_branch_id_unique',
                'deceased_client_id_branch_id_unique',
            ] as $indexName) {
                try {
                    $table->dropUnique($indexName);
                } catch (\Throwable $e) {
                    // Ignore if already missing.
                }
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            try {
                $table->dropUnique('clients_id_branch_id_unique');
            } catch (\Throwable $e) {
                // Ignore if already missing.
            }
        });
    }

    private function restoreLegacyForeignKeys(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['funeral_case_id', 'branch_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                    // Ignore if already missing.
                }
            }

            $table->foreign('funeral_case_id')->references('id')->on('funeral_cases')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            foreach (['branch_id', 'client_id', 'deceased_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                    // Ignore if already missing.
                }
            }

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('deceased_id')->references('id')->on('deceased')->cascadeOnDelete();
        });

        Schema::table('deceased', function (Blueprint $table) {
            foreach (['branch_id', 'client_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                    // Ignore if already missing.
                }
            }

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });

        Schema::table('clients', function (Blueprint $table) {
            try {
                $table->dropForeign(['branch_id']);
            } catch (\Throwable $e) {
                // Ignore if already missing.
            }

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });
    }
};
