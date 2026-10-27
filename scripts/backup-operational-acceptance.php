<?php

// Destructive QA is hard-limited to these two localhost databases and workspace runtime directory.
use App\Models\{Branch, Client, Deceased, FuneralCase, Package, Payment, PaymentCorrectionRequest, SystemBackup, User, CaseDocument};
use App\Services\{SystemBackupService};
use App\Services\BranchSync\{SyncState, BranchSyncService};
use Illuminate\Support\Facades\{Artisan, Cache, Crypt, DB, File, Http, Storage};
use Illuminate\Support\{Carbon, Str};
use Symfony\Component\Process\Process;

define('ARTISAN_BINARY', __FILE__);
require __DIR__.'/../vendor/autoload.php';
$root = str_replace('\\', '/', dirname(__DIR__)).'/storage/framework/backup-acceptance-runtime';
$action = $argv[1] ?? 'qa:run';
$mode = getenv('BACKUP_QA_MODE') ?: 'branch';
$clock = getenv('BACKUP_QA_CLOCK') ?: null;
$fault = getenv('BACKUP_QA_FAULT') ?: '';
foreach (['APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1',
    'DB_DATABASE' => 'backup_acceptance_20261025', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '',
    'CACHE_STORE' => 'file', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    'LARAVEL_STORAGE_PATH' => $root, 'BRANCH_SYNC_MODE' => $mode, 'BRANCH_SYNC_CODE' => 'BR001'] as $key => $value) {
    putenv($key.'='.$value); $_ENV[$key] = $_SERVER[$key] = $value;
}
foreach (['app/private', 'app/public', 'framework/cache/data', 'framework/views', 'logs'] as $directory) {
    if (!is_dir($root.'/'.$directory)) mkdir($root.'/'.$directory, 0777, true);
}
if ($clock) { Carbon::setTestNow(Carbon::parse($clock, 'Asia/Manila')); }
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'mysql', 'database.connections.mysql.url' => null,
    'database.connections.mysql.host' => '127.0.0.1', 'database.connections.mysql.port' => 3306,
    'database.connections.mysql.database' => 'backup_acceptance_20261025', 'database.connections.mysql.username' => 'root',
    'database.connections.mysql.password' => '', 'cache.default' => 'file', 'session.driver' => 'array',
    'filesystems.disks.local.root' => $root.'/app/private', 'filesystems.disks.public.root' => $root.'/app/public',
    'backup.disk' => 'local', 'backup.directory' => 'system-backups',
    'branch_sync.mode' => $mode, 'branch_sync.branch_code' => 'BR001',
    'branch_sync.central_url' => 'https://central.invalid', 'branch_sync.token' => str_repeat('a', 64)]);
DB::purge('mysql');
Http::fake(fn () => Http::failedConnection()); // All outbound app HTTP fails; localhost DB/dump stays available.

Artisan::all(); // ApplicationBuilder registers schedules when Artisan starts, not merely at provider boot.
$schedule = app(Illuminate\Console\Scheduling\Schedule::class);
$backupEvent = collect($schedule->events())->first(fn ($event) => str_contains($event->command ?? '', 'system:backup'));
if (!$backupEvent) throw new RuntimeException('Backup schedule was not registered');

if ($action === 'qa:inspect') {
    echo json_encode(['mode' => $mode, 'expression' => $backupEvent->expression,
        'due' => $backupEvent->isDue($app), 'mutex_minutes' => $backupEvent->expiresAt])."\n";
    exit;
}

if ($fault === 'dump') config(['backup.dump_binary' => $root.'/missing-dump-binary.exe']);
if ($fault === 'quota') {
    $disk = Mockery::mock(Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('put')->andReturn(false);
    Storage::set('qa-quota-denied', $disk);
    config(['backup.disk' => 'qa-quota-denied']);
}
DB::listen(function ($query) use ($root) {
    if (!str_contains($query->sql, '`system_backups`') || !preg_match('/^(insert|update)/i', $query->sql)) return;
    foreach ($query->bindings as $binding) {
        if (in_array($binding, ['running', 'completed', 'verified', 'failed'], true)) {
            file_put_contents($root.'/backup-phases.jsonl', json_encode(['pid' => getmypid(), 'stage' => $binding,
                'real_utc' => gmdate(DATE_ATOM), 'wall_seconds' => microtime(true)])."\n", FILE_APPEND | LOCK_EX);
            break;
        }
    }
});
if ($fault === 'interrupt') {
    DB::listen(function ($query) use ($root) {
        static $marked = false;
        if (!$marked && str_starts_with(strtolower($query->sql), 'update `system_backups`') && in_array('completed', $query->bindings, true)) {
            $marked = true;
            file_put_contents($root.'/interruption.json', json_encode(['pid' => getmypid(), 'real_utc' => gmdate(DATE_ATOM), 'phase' => 'archive-written-before-verification']));
            while (true) usleep(100000);
        }
    });
}
foreach ([Illuminate\Console\Events\ScheduledTaskStarting::class, Illuminate\Console\Events\ScheduledTaskFinished::class,
    Illuminate\Console\Events\ScheduledTaskFailed::class, Illuminate\Console\Events\ScheduledTaskSkipped::class] as $eventClass) {
    app('events')->listen($eventClass, function ($event) use ($root, $clock) {
        if (!str_contains($event->task->command ?? '', 'system:backup')) return;
        file_put_contents($root.'/schedule-events.jsonl', json_encode(['event' => class_basename($event),
            'real_utc' => gmdate(DATE_ATOM), 'controlled_due_time' => $clock, 'exit_code' => $event->task->exitCode])."\n", FILE_APPEND | LOCK_EX);
    });
}
if (in_array($action, ['schedule:run', 'schedule:work', 'system:backup', 'branch:sync', 'cases:complete-past-interments'], true)) {
    exit($app->make(Illuminate\Contracts\Console\Kernel::class)->handle(new Symfony\Component\Console\Input\ArgvInput,
        new Symfony\Component\Console\Output\ConsoleOutput));
}
if ($action !== 'qa:run') throw new RuntimeException('Unsupported QA action');
$pdo = new PDO('mysql:host=127.0.0.1', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (['backup_acceptance_20261025', 'backup_replacement_20261025'] as $name) {
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
if (DB::getDatabaseName() !== 'backup_acceptance_20261025') throw new RuntimeException('Unsafe QA database');
Artisan::call('migrate:fresh', ['--force' => true]);
File::put($root.'/schedule-events.jsonl', '');
File::put($root.'/backup-phases.jsonl', '');
$results = [];
$assert = function (bool $passed, string $name, array $evidence = []) use (&$results): void {
    $results[] = ['test' => $name, 'passed' => $passed, 'real_utc' => gmdate(DATE_ATOM), 'evidence' => $evidence];
    if (!$passed) throw new RuntimeException($name.' '.json_encode($evidence));
};
$stopTree = function (Process $process): void {
    if (!$process->isRunning()) return;
    if (PHP_OS_FAMILY === 'Windows') (new Process(['taskkill', '/F', '/T', '/PID', (string) $process->getPid()]))->run();
    else $process->stop(1);
};
$job = function (string $command, Carbon $time, string $fault = '', string $mode = 'branch') use ($root): Process {
    $process = new Process([PHP_BINARY, __FILE__, $command], dirname(__DIR__), [
        'BACKUP_QA_CLOCK' => $time->toDateTimeString(), 'BACKUP_QA_FAULT' => $fault, 'BACKUP_QA_MODE' => $mode,
    ]);
    $process->setTimeout(120); return $process;
};
$branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Backup QA', 'is_active' => true]);
$users = [];
foreach (['staff', 'admin'] as $role) {
    $user = User::factory()->create(['role' => $role, 'admin_scope' => $role === 'admin' ? 'branch' : null,
        'branch_id' => $branch->id, 'is_active' => true, 'password' => 'Acceptance123!', 'must_change_password' => false]);
    $user->two_factor_secret = (new PragmaRX\Google2FA\Google2FA)->generateSecretKey();
    $user->two_factor_confirmed_at = now(); $user->save(); $users[] = $user;
}
$package = Package::create(['name' => 'Backup QA', 'price' => 1000, 'is_active' => true]);
for ($i = 1; $i <= 3; $i++) {
    $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Backup client '.$i, 'relationship_to_deceased' => 'Spouse']);
    $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Backup deceased '.$i]);
    $case = FuneralCase::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id,
        'package_id' => $package->id, 'case_number' => $i, 'case_code' => 'BACKUP-'.$i, 'entry_source' => 'MAIN',
        'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => $package->name,
        'coffin_type' => 'Standard', 'wake_location' => 'QA Chapel', 'funeral_service_at' => today()->addDays(10),
        'subtotal_amount' => 1000, 'discount_type' => 'NONE', 'discount_value_type' => 'AMOUNT',
        'discount_value' => 0, 'discount_amount' => 0, 'tax_rate' => 0, 'tax_amount' => 0,
        'total_amount' => 1000, 'total_paid' => 0, 'balance_amount' => 1000, 'payment_status' => 'UNPAID', 'case_status' => 'ACTIVE']);
    for ($p = 1; $p <= 5; $p++) Payment::create(['branch_id' => $branch->id, 'funeral_case_id' => $case->id,
        'payment_record_no' => 'BACKUP-'.$i.'-'.$p, 'amount' => 200, 'status' => 'POSTED', 'payment_method' => 'cash', 'recorded_by' => $users[0]->id]);
    $case->recalculatePaymentTotals();
}
$original = Payment::firstOrFail(); $original->update(['status' => 'VOIDED']);
$replacement = $original->replicate(['payment_record_no', 'receipt_number']);
$replacement->fill(['status' => 'POSTED', 'amount' => 150, 'replaces_payment_id' => $original->id, 'payment_record_no' => 'BACKUP-REPLACEMENT'])->save();
$original->funeralCase->recalculatePaymentTotals();
PaymentCorrectionRequest::create(['payment_id' => $original->id, 'branch_id' => $branch->id, 'requested_by' => $users[0]->id,
    'reviewed_by' => $users[1]->id, 'correction_type' => 'CORRECT', 'status' => 'APPROVED',
    'original_values' => ['amount' => 200], 'proposed_values' => ['amount' => 150], 'reason' => 'Historical correction QA',
    'replacement_payment_id' => $replacement->id, 'reviewed_at' => now()]);
App\Support\AuditLogger::log('PAYMENT_CORRECTION_APPROVED', 'UPDATE', 'payment', $replacement->id, ['fixture' => true], $branch->id);
Storage::disk('local')->put('case-documents/backup-qa.pdf', "%PDF-1.4\nprivate QA document");
Storage::disk('public')->put('uploads/backup-qa.jpg', 'public QA upload');
Storage::disk('local')->put('branch-sync/blobs/pending-qa', 'immutable pending QA bytes');
CaseDocument::create(['case_id' => $case->id, 'document_type' => 'funeral_contract', 'file_name' => 'backup-qa.pdf',
    'file_path' => 'case-documents/backup-qa.pdf', 'generated_by' => $users[1]->id, 'generated_at' => now()]);
$installation = (string) Str::uuid();
SyncState::put('installation', $installation); SyncState::put('references_at', now()->toIso8601String());
SyncState::put('activation_state', 'active');
app(BranchSyncService::class)->capture();
$checkpoint = DB::table('branch_sync_outbox')->first();
$mappingCount = DB::table('branch_sync_records')->count();
$time = Carbon::now('Asia/Manila')->startOfHour()->addHour();

foreach ([['branch', $time->copy(), true], ['branch', $time->copy()->addMinute(), false],
    ['central', $time->copy()->setTime(1, 0), true], ['central', $time->copy()->setTime(1, 1), false]] as [$m, $t, $due]) {
    $process = $job('qa:inspect', $t, '', $m); $process->run(); $data = json_decode(trim($process->getOutput()), true);
    $assert(($data['due'] ?? null) === $due, 'schedule_definition_'.$m.'_'.$t->format('H:i'), $data ?? []);
}
// Real schedule:work automatically invokes schedule:run, which dispatches the registered hourly event.
Carbon::setTestNow($time);
$before = SystemBackup::count(); $worker = $job('schedule:work', $time); $worker->start(); $started = microtime(true);
try {
    do { usleep(100000); $verified = SystemBackup::where('id', '>', $before)->where('status', 'verified')->first(); }
    while (!$verified && $worker->isRunning() && microtime(true) - $started < 35);
    $assert((bool) $verified, 'automatic_laravel_worker_dispatch_offline', ['controlled_due_time' => $time->toDateTimeString(),
        'seconds' => round(microtime(true) - $started, 3), 'worker_output' => $worker->getOutput().$worker->getErrorOutput()]);
} finally { $stopTree($worker); }
$record = $verified;
$events = file_get_contents($root.'/schedule-events.jsonl');
$assert(str_contains($events, 'ScheduledTaskFinished'), 'scheduler_completion_event_recorded');

foreach (['scheduler_mutex', 'service_mutex', 'sync_mutex', 'dump', 'quota'] as $phase) {
    $time->addHour(); Carbon::setTestNow($time); $before = SystemBackup::count();
    $lock = null;
    if ($phase === 'scheduler_mutex') $assert($backupEvent->mutex->create($backupEvent), 'scheduler_mutex_acquired');
    if ($phase === 'service_mutex' || $phase === 'sync_mutex') {
        $lock = Cache::lock($phase === 'service_mutex' ? 'system-backup:create' : 'branch-sync-worker', 900);
        $assert($lock->get(), $phase.'_acquired');
    }
    try { $process = $job('schedule:run', $time, in_array($phase, ['dump', 'quota']) ? $phase : ''); $process->run(); }
    finally { if ($lock) $lock->release(); if ($phase === 'scheduler_mutex') $backupEvent->mutex->forget($backupEvent); }
    $latest = SystemBackup::latest('id')->first();
    $assert(in_array($phase, ['dump', 'quota']) ? ($latest->id > $before && $latest->status === 'failed') : SystemBackup::count() === $before,
        'controlled_'.$phase, ['records_before' => $before, 'records_after' => SystemBackup::count(),
            'last_status' => $latest->status, 'output' => $process->getOutput().$process->getErrorOutput()]);
}
// Kill only the owned QA worker tree after archive write, before verification/commit.
$time->addHour(); Carbon::setTestNow($time);
if (is_file($root.'/interruption.json')) unlink($root.'/interruption.json');
$worker = $job('schedule:work', $time, 'interrupt'); $worker->start(); $started = microtime(true);
try {
    while (!is_file($root.'/interruption.json') && $worker->isRunning() && microtime(true) - $started < 35) usleep(100000);
    $assert(is_file($root.'/interruption.json'), 'interrupt_phase_reached');
} finally { $stopTree($worker); }
$interrupted = SystemBackup::latest('id')->first();
$assert($interrupted->status === 'running' && !$interrupted->verified_at, 'interrupted_attempt_not_false_verified',
    ['attempt_id' => $interrupted->id, 'state_before_next_dispatch' => $interrupted->status]);
$time->addHour(); Carbon::setTestNow($time); $process = $job('schedule:run', $time); $process->run();
$assert(SystemBackup::latest('id')->first()->status === 'verified' && $interrupted->fresh()->status === 'failed',
    'automatic_next_dispatch_retries_and_classifies_interruption', ['previous_reason' => $interrupted->fresh()->failure_message]);
$before = SystemBackup::count();
$central = $job('schedule:run', $time->copy()->setTime(1, 0), '', 'central'); $central->run();
$last = SystemBackup::latest('id')->first();
$assert(SystemBackup::count() === $before + 1 && $last->status === 'verified' && ($last->metadata['deployment_mode'] ?? null) === 'central',
    'separate_central_daily_scheduler_dispatch', ['output' => $central->getOutput().$central->getErrorOutput()]);

$encrypted = Storage::disk('local')->get($record->path);
$assert(hash_equals($record->checksum, hash('sha256', $encrypted)), 'encrypted_archive_checksum');
$zipPath = $root.'/verified-restore.zip'; file_put_contents($zipPath, Crypt::decryptString($encrypted));
$zip = new ZipArchive; $assert($zip->open($zipPath, ZipArchive::CHECKCONS) === true, 'decrypt_and_zip_consistency');
$assert($zip->getFromName('private/branch-sync/blobs/pending-qa') === 'immutable pending QA bytes'
    && $zip->getFromName('public/uploads/backup-qa.jpg') === 'public QA upload'
    && $zip->getFromName('private/case-documents/backup-qa.pdf') === "%PDF-1.4\nprivate QA document", 'required_recovery_files');
$sql = $zip->getFromName('database.sql');
$assert($zip->extractTo($root.'/replacement-files'), 'isolated_file_restore'); $zip->close();
$mysql = new Process(['C:/xampp/mysql/bin/mysql.exe', '--host=127.0.0.1', '--user=root', '--database=backup_replacement_20261025', '--binary-mode']);
$mysql->setInput($sql)->setTimeout(120)->mustRun();
config(['database.connections.backup_replacement' => array_replace(config('database.connections.mysql'), ['database' => 'backup_replacement_20261025'])]);
DB::setDefaultConnection('backup_replacement');
config(['filesystems.disks.local.root' => $root.'/replacement-files/private',
    'filesystems.disks.public.root' => $root.'/replacement-files/public']);
Storage::forgetDisk('local'); Storage::forgetDisk('public');
$assert(Storage::disk('local')->get('case-documents/backup-qa.pdf') === "%PDF-1.4\nprivate QA document"
    && Storage::disk('public')->get('uploads/backup-qa.jpg') === 'public QA upload'
    && Storage::disk('local')->get('branch-sync/blobs/pending-qa') === 'immutable pending QA bytes', 'replacement_application_storage_uses_restored_files');
$assert(DB::table('funeral_cases')->count() === 3 && DB::table('payments')->count() === 16
    && DB::table('payment_correction_requests')->where('status', 'APPROVED')->count() === 1
    && DB::table('audit_logs')->where('action', 'PAYMENT_CORRECTION_APPROVED')->exists(), 'restored_cases_payments_correction_and_audit');
$assert((float) DB::table('funeral_cases')->sum('total_paid') === 2950.0
    && (float) DB::table('funeral_cases')->sum('balance_amount') === 50.0
    && (float) DB::table('payments')->where('status', 'POSTED')->sum('amount') === 2950.0, 'restored_financial_totals');
$restored = DB::table('branch_sync_outbox')->where('version', $checkpoint->version)->first();
$assert($restored->fingerprint === $checkpoint->fingerprint && !$restored->acknowledged_at
    && SyncState::get('installation') === $installation && DB::table('branch_sync_records')->count() === $mappingCount
    && (int) SyncState::get('sequence') === (int) $checkpoint->version
    && hash('sha256', json_encode(App\Services\BranchSync\OutboxPayload::decode($restored->payload), JSON_THROW_ON_ERROR)) === $checkpoint->fingerprint,
    'restored_pending_checkpoint_and_mappings_unchanged');
$restoredUser = User::findOrFail($users[0]->id);
$assert(Illuminate\Support\Facades\Hash::check('Acceptance123!', $restoredUser->password)
    && $restoredUser->two_factor_secret === $users[0]->two_factor_secret && $restoredUser->role === 'staff'
    && (int) $restoredUser->branch_id === (int) $branch->id && $restoredUser->is_active
    && User::findOrFail($users[1]->id)->isBranchAdmin(), 'restored_password_role_and_key_encrypted_mfa');
$assert(DB::table('case_documents')->where('case_id', $case->id)->where('generated_by', $users[1]->id)->exists()
    && DB::table('payments')->where('id', $replacement->id)->where('replaces_payment_id', $original->id)->exists()
    && DB::table('payment_correction_requests')->where('replacement_payment_id', $replacement->id)->where('payment_id', $original->id)->exists(),
    'restored_document_and_correction_relationships');
// Operator restore procedure explicitly holds the clone; checkpoints are unchanged.
SyncState::put('recovery_required', true);
auth()->guard('web')->setUser($restoredUser); session()->start();
session()->put(['account_security' => App\Support\AccountSecurity::fingerprint($restoredUser), 'mfa_user_id' => $restoredUser->id,
    'idle_last_activity' => now()->timestamp, 'idle_session_key' => 'backup-qa']); session()->save();
$request = Illuminate\Http\Request::create('/intake/main', 'POST', [], [config('session.cookie') => session()->getId()], server: ['HTTP_ACCEPT' => 'application/json']);
$request->setUserResolver(fn () => $restoredUser);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class); $response = $kernel->handle($request);
$assert($response->getStatusCode() === 409, 'isolated_restored_clone_write_hold', ['http_status' => $response->getStatusCode()]);
$kernel->terminate($request, $response);
DB::setDefaultConnection('mysql');
echo json_encode(['real_finished_utc' => gmdate(DATE_ATOM), 'runtime_root' => $root, 'results' => $results,
    'backup_phase_timings' => array_map(fn ($line) => json_decode($line, true), array_filter(explode("\n", trim(file_get_contents($root.'/backup-phases.jsonl'))))),
    'scheduler_evidence' => array_map(fn ($line) => json_decode($line, true), array_filter(explode("\n", trim(file_get_contents($root.'/schedule-events.jsonl'))))),
    'limits' => ['Controlled process clock; not an installed Windows service or elapsed hourly recurrence.',
        'HTTP disabled by controlled transport failure; no actual WAN cable disconnection.',
        'Replacement uses an isolated DB/directory on the same machine, not replacement hardware.',
        'No independent off-device storage or transfer automation is configured.']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
