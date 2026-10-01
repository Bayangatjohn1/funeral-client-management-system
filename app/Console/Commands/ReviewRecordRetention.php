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
        FuneralCase::withoutGlobalScope('branch_scope')->where('case_status', 'COMPLETED')->whereNull('completed_at')->chunkById(100, function ($cases) {
            foreach ($cases as $case) {
                $completedAt = $case->interment_at ?? $case->updated_at;
                $case->forceFill(['completed_at' => $completedAt, 'retention_end_date' => $completedAt?->copy()->addYears(5)->toDateString()])->saveQuietly();
            }
        });
        $updated = FuneralCase::withoutGlobalScope('branch_scope')->where('case_status', 'COMPLETED')->whereNull('legal_hold_at')->whereNotNull('retention_end_date')->whereDate('retention_end_date', '<=', today())->where('retention_status', 'retained')->update(['retention_status' => 'pending_disposal_review']);
        $this->info("{$updated} case(s) marked for disposal review.");
        return self::SUCCESS;
    }
}
