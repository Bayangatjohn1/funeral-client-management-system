<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('funeral_cases', 'entry_source')) {
                $table->string('entry_source', 20)->default('MAIN')->after('encoded_by');
                $table->index('entry_source', 'funeral_cases_entry_source_index');
            }
        });

        $branchCodeById = Branch::query()->pluck('branch_code', 'id')->all();

        DB::table('funeral_cases')
            ->select(['id', 'branch_id'])
            ->orderBy('id')
            ->chunkById(200, function ($cases) use ($branchCodeById) {
                foreach ($cases as $case) {
                    $branchCode = strtoupper((string) ($branchCodeById[$case->branch_id] ?? ''));
                    $entrySource = $branchCode === 'BR001' ? 'MAIN' : 'OTHER_BRANCH';

                    DB::table('funeral_cases')
                        ->where('id', $case->id)
                        ->update(['entry_source' => $entrySource]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            if (Schema::hasColumn('funeral_cases', 'entry_source')) {
                try {
                    $table->dropIndex('funeral_cases_entry_source_index');
                } catch (\Throwable $e) {
                    // Ignore when index is already missing.
                }
                $table->dropColumn('entry_source');
            }
        });
    }
};
