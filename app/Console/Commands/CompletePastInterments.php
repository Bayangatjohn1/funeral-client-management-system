<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuneralCase;

class CompletePastInterments extends Command
{
    protected $signature = 'cases:complete-past-interments';
    protected $description = 'Auto-complete active funeral cases when the internment date is reached';

    public function handle()
    {
        $count = \Illuminate\Support\Facades\DB::transaction(function () {
            if (config('branch_sync.mode') === 'branch') {
                \Illuminate\Support\Facades\DB::table('branch_sync_state')->where('key', 'installation')->lockForUpdate()->first();
                if (\App\Services\BranchSync\SyncState::get('recovery_required')
                    || \App\Services\BranchSync\SyncState::get('activation_state', 'active') !== 'active') {
                    return 0;
                }
            } elseif (config('branch_sync.mode') === 'central') {
                \Illuminate\Support\Facades\DB::table('branch_sync_peers')->orderBy('id')->lockForUpdate()->get();
            }
            $completed = FuneralCase::completePastInterments();
            if ($completed && config('branch_sync.mode') === 'branch') {
                \App\Services\BranchSync\SyncState::put('dirty', true);
            }
            return $completed;
        });

        $this->info("Completed {$count} past interment case(s).");

        return 0;
    }
}
