<?php

namespace App\Console\Commands;

use App\Services\SystemBackupService;
use Illuminate\Console\Command;

class CreateSystemBackup extends Command
{
    protected $signature = 'system:backup {--type=scheduled}';
    protected $description = 'Create and verify an encrypted system backup';

    public function handle(SystemBackupService $service): int
    {
        $backup = $service->create((string) $this->option('type'));
        $this->info("Backup {$backup->id} verified.");
        return self::SUCCESS;
    }
}
