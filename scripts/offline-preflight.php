<?php

// Read-only by default; --probe-storage adds one temporary write/delete. Never prints credentials.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$checks = [];
$observations = [
    'php_sapi' => PHP_SAPI,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'process_execution_available' => function_exists('proc_open'),
    'cache_store' => config('cache.default'),
    'session_driver' => config('session.driver'),
    'snapshot_plaintext_limit_bytes' => config('branch_sync.max_snapshot_bytes'),
    'file_limit_bytes' => config('branch_sync.max_file_bytes'),
    'note' => 'Observed SAPI only. CLI values do not establish web, reverse-proxy or hosting-plan limits.',
];
$record = function (string $name, bool $pass, string $detail) use (&$checks): void {
    $checks[] = compact('name', 'pass', 'detail');
};
$record('php', PHP_VERSION_ID >= 80200, PHP_VERSION);
foreach (['pdo_mysql', 'openssl', 'zlib', 'fileinfo', 'mbstring', 'zip'] as $extension) {
    $record('extension:'.$extension, extension_loaded($extension), 'Required by the application or backup/transport.');
}
$mode = config('branch_sync.mode');
$record('deployment_mode', in_array($mode, ['central', 'branch'], true), (string) $mode);
$record('debug_disabled', ! config('app.debug'), 'Debug output must be disabled in staging acceptance and production.');
$record('mysql_engine', in_array(config('database.default'), ['mysql', 'mariadb'], true), 'Live branch backup validation targets MySQL/MariaDB.');
$record('compiled_assets', is_file(public_path('build/manifest.json')), 'Upload public/build with the release.');
$record('offline_assets', is_file(public_path('vendor/offline/fonts.css')) && is_file(public_path('vendor/offline/flatpickr.min.js')), 'Upload public/vendor/offline with the release.');
$privateRoot = Illuminate\Support\Facades\Storage::disk('local')->path('');
if (in_array('--probe-storage', $argv, true)) {
    $probe = rtrim($privateRoot, '/\\').'/.offline-preflight-'.bin2hex(random_bytes(8));
    try {
        $record('private_storage_write_probe', @file_put_contents($probe, 'preflight', LOCK_EX) !== false,
            'Temporary file written and removed under the configured private disk; test web-server service identity separately.');
    } finally {
        if (is_file($probe)) {
            unlink($probe);
        }
    }
} else {
    $record('private_storage_permission_hint', is_writable($privateRoot),
        'PHP permission hint only; Windows ACL reporting can be inaccurate. --probe-storage verifies a temporary write/delete.');
}
try {
    if (in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
        $observations['database_version'] = Illuminate\Support\Facades\DB::selectOne('SELECT VERSION() AS version')->version;
        $packet = (int) Illuminate\Support\Facades\DB::selectOne('SELECT @@max_allowed_packet AS bytes')->bytes;
        $record('database_packet_capacity', $packet >= 64 * 1024 * 1024,
            'Observed '.$packet.' bytes; require >=64 MiB for worst-case configured snapshot capacity.');
    }
    foreach (['branch_sync_peers', 'branch_sync_state', 'branch_sync_records', 'branch_sync_outbox', 'branch_sync_file_grants'] as $table) {
        $record('schema:'.$table, Illuminate\Support\Facades\Schema::hasTable($table), 'Required synchronization table.');
    }
    $record('cutover_migration', Illuminate\Support\Facades\Schema::hasColumn('branch_sync_peers', 'activation_state'), 'Required controlled activation migration.');
    $record('payment_identity_migration', Illuminate\Support\Facades\Schema::hasColumn('payments', 'submission_id')
        && Illuminate\Support\Facades\Schema::hasColumn('payments', 'submission_hash'), 'Required nullable payment retry identity columns.');
    if ($mode === 'branch') {
        $record('branch_token', (bool) preg_match('/^[a-f0-9]{64}$/', config('branch_sync.token', '')), 'Presence/format only; token is never displayed.');
        $record('central_https', str_starts_with(config('branch_sync.central_url', ''), 'https://'), 'Actual certificate/proxy checks are separate acceptance steps.');
        $record('initialized', (bool) App\Services\BranchSync\SyncState::get('installation'), 'Bootstrap must complete before readiness confirmation.');
        $record('no_recovery_hold', ! App\Services\BranchSync\SyncState::get('recovery_required'), 'A hold requires operator reconciliation.');
    }
} catch (Throwable) {
    $record('database', false, 'Database/schema check failed; inspect local protected logs.');
}
echo json_encode(['checked_at' => gmdate(DATE_ATOM), 'sapi' => PHP_SAPI, 'observations' => $observations, 'checks' => $checks,
    'manual_gates' => ['Web-SAPI PHP limits', 'Trusted local TLS and LAN access from two computers', 'Scheduler heartbeat and restart persistence',
        'Off-device backup and original APP_KEY custody', 'Verified legacy export migration', 'Five-day physical pilot'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
exit(collect($checks)->contains(fn ($check) => ! $check['pass']) ? 1 : 0);
