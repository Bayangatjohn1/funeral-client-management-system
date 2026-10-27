<?php

namespace Tests\Feature;

use App\Models\SystemBackup;
use App\Services\SystemBackupService;
use App\Services\BranchSync\SyncState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Storage, Crypt};
use Tests\TestCase;

class AutomaticBackupAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_branch_backup_retains_its_failure_record_after_transaction_rollback(): void
    {
        Storage::fake('local'); Storage::fake('public');
        config(['branch_sync.mode' => 'branch', 'backup.dump_binary' => 'C:/nonexistent/backup-qa-dump.exe']);
        SyncState::put('installation', 'backup-acceptance-test');
        try {
            app(SystemBackupService::class)->create('scheduled');
            $this->fail('The controlled dump failure must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
        $this->assertDatabaseCount('system_backups', 1);
        $backup = SystemBackup::firstOrFail();
        $this->assertSame('failed', $backup->status);
        $this->assertNotEmpty($backup->failure_message);
        $this->assertNull($backup->verified_at);
    }

    public function test_a_previously_verified_missing_archive_is_no_longer_reported_verified(): void
    {
        Storage::fake('local');
        $backup = SystemBackup::create(['type' => 'scheduled', 'status' => 'verified', 'disk' => 'local',
            'path' => 'system-backups/missing.backup', 'checksum' => str_repeat('a', 64), 'verified_at' => now()]);
        try {
            app(SystemBackupService::class)->verify($backup);
            $this->fail('Missing archive must fail verification.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('missing', $exception->getMessage());
        }
        $this->assertSame('failed', $backup->fresh()->status);
        $this->assertNotEmpty($backup->fresh()->failure_message);
    }

    public function test_backup_scheduler_mutex_matches_the_fifteen_minute_service_lock(): void
    {
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        \Illuminate\Support\Facades\Artisan::all();
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $backup = $events->first(fn ($event) => str_contains($event->command ?? '', 'system:backup'));
        $this->assertNotNull($backup);
        $this->assertTrue($backup->withoutOverlapping);
        $this->assertSame(15, $backup->expiresAt);
    }

    public function test_stale_interrupted_attempt_is_classified_unconfirmed_on_the_next_attempt(): void
    {
        Storage::fake('local'); Storage::fake('public');
        $old = SystemBackup::create(['type' => 'scheduled', 'status' => 'running', 'disk' => 'local',
            'created_at' => now()->subMinutes(16)]);
        $old->forceFill(['created_at' => now()->subMinutes(16)])->save();
        config(['branch_sync.mode' => 'branch', 'backup.dump_binary' => 'C:/nonexistent/backup-qa-dump.exe']);
        SyncState::put('installation', 'backup-acceptance-test');
        try { app(SystemBackupService::class)->create('scheduled'); } catch (\RuntimeException) {}
        $this->assertSame('failed', $old->fresh()->status);
        $this->assertStringContainsString('unconfirmed', $old->fresh()->failure_message);
        $this->assertNull($old->fresh()->verified_at);
    }

    public function test_corruption_wrong_key_and_invalid_zip_are_rejected_and_recorded(): void
    {
        Storage::fake('local');
        foreach (['checksum', 'wrong_key', 'invalid_zip'] as $fault) {
            $bytes = $fault === 'wrong_key'
                ? (new \Illuminate\Encryption\Encrypter(str_repeat('q', 32), 'AES-256-CBC'))->encryptString('not a recovery archive')
                : Crypt::encryptString('not a valid ZIP');
            Storage::disk('local')->put('system-backups/'.$fault.'.backup', $bytes);
            $backup = SystemBackup::create(['type' => 'scheduled', 'status' => 'verified', 'disk' => 'local',
                'path' => 'system-backups/'.$fault.'.backup', 'verified_at' => now(),
                'checksum' => $fault === 'checksum' ? str_repeat('a', 64) : hash('sha256', $bytes)]);
            try {
                app(SystemBackupService::class)->verify($backup);
                $this->fail('Invalid backup must never pass verification.');
            } catch (\RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
            $this->assertSame('failed', $backup->fresh()->status);
            $this->assertNotEmpty($backup->fresh()->failure_message);
        }
    }
}
