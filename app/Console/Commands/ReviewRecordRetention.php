<?php

namespace App\Console\Commands;

use App\Models\FuneralCase;
use Illuminate\Console\Command;

class ReviewRecordRetention extends Command
{
    protected $signature = 'retention:review';
    protected $description = 'Calculate retention dates and mark eligible cases for disposal review';

    public function handle(): int
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            if (config('branch_sync.mode') === 'branch') {
                \Illuminate\Support\Facades\DB::table('branch_sync_state')->where('key', 'installation')->lockForUpdate()->first();
                if (\App\Services\BranchSync\SyncState::get('recovery_required')
                    || \App\Services\BranchSync\SyncState::get('activation_state', 'active') !== 'active') {
                    return self::SUCCESS;
                }
            } elseif (config('branch_sync.mode') === 'central') {
                \Illuminate\Support\Facades\DB::table('branch_sync_peers')->orderBy('id')->lockForUpdate()->get();
            }
            FuneralCase::withoutGlobalScope('branch_scope')->locallyOwned()->where('case_status', 'COMPLETED')->whereNull('completed_at')->chunkById(100, function ($cases) {
                foreach ($cases as $case) {
                    $completedAt = $case->interment_at ?? $case->updated_at;
                    $case->forceFill(['completed_at' => $completedAt, 'retention_end_date' => $completedAt?->copy()->addYears(5)->toDateString()])->saveQuietly();
                    if (config('branch_sync.mode') === 'branch') {
                        \App\Services\BranchSync\SyncState::put('dirty', true);
                    }
                }
        });
        $updated = FuneralCase::withoutGlobalScope('branch_scope')->locallyOwned()->where('case_status', 'COMPLETED')->whereNull('legal_hold_at')->whereNotNull('retention_end_date')->whereDate('retention_end_date', '<=', today())->where('retention_status', 'retained')->update(['retention_status' => 'pending_disposal_review']);
            if ($updated && config('branch_sync.mode') === 'branch') {
                \App\Services\BranchSync\SyncState::put('dirty', true);
            }
        $this->info("{$updated} case(s) marked for disposal review.");
        return self::SUCCESS;
        });
    }
}
