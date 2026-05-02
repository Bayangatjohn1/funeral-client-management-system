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
        $count = FuneralCase::completePastInterments();

        $this->info("Completed {$count} past interment case(s).");

        return 0;
    }
}
