<?php

namespace App\Console\Commands;

use App\Models\FuneralCase;
use Illuminate\Console\Command;

class CompletePastInterments extends Command
{
    protected $signature = 'cases:complete-past-interments';

    protected $description = 'Complete active funeral cases whose scheduled interment datetime has passed';

    public function handle(): int
    {
        $completed = FuneralCase::completePastInterments();

        $this->info("Completed {$completed} past interment case(s).");

        return self::SUCCESS;
    }
}
