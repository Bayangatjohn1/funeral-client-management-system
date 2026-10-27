<?php

// Isolated, destructive QA fixture ONLY. Never accepts an arbitrary database/host.
use App\Models\{Branch, Client, Deceased, FuneralCase, Package, Payment, PaymentCorrectionRequest, User};
use App\Services\BranchSync\SyncState;
use Illuminate\Support\Facades\{Artisan, DB};
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';
$worker = ($argv[1] ?? '') === 'worker';
$database = 'payment_integrity_validation_20261021';
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1',
    'DB_DATABASE' => $database, 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '',
    'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'QUEUE_CONNECTION' => 'sync',
    'BRANCH_SYNC_MODE' => 'standalone'] as $key => $value) {
    putenv($key.'='.$value); $_ENV[$key] = $_SERVER[$key] = $value;
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['app.env' => 'testing', 'database.default' => 'mysql', 'database.connections.mysql.url' => null,
    'database.connections.mysql.host' => '127.0.0.1', 'database.connections.mysql.port' => 3306,
    'database.connections.mysql.database' => $database, 'database.connections.mysql.username' => 'root',
    'database.connections.mysql.password' => '', 'session.driver' => 'array', 'cache.default' => 'array',
    'queue.default' => 'sync', 'branch_sync.mode' => 'standalone']);
DB::purge('mysql');

if ($worker) {
    $job = json_decode(base64_decode($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    config(['branch_sync.mode' => $job['mode'], 'branch_sync.branch_code' => 'BR001']);
    $user = User::findOrFail($job['user']);
    auth()->guard('web')->setUser($user);
    session()->start();
    session()->put(['account_security' => App\Support\AccountSecurity::fingerprint($user),
        'mfa_user_id' => $user->id, 'idle_last_activity' => now()->timestamp, 'idle_session_key' => 'concurrency-qa']);
    session()->save();
    $request = Illuminate\Http\Request::create($job['path'], 'POST', $job['payload'], server: [
        'HTTP_ACCEPT' => 'application/json', 'HTTP_REFERER' => 'http://localhost/payments/history',
        'HTTP_COOKIE' => config('session.cookie').'='.session()->getId(),
    ]);
    $request->cookies->set(config('session.cookie'), session()->getId());
    $request->setUserResolver(fn () => $user);
    $id = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    echo json_encode(['ready' => $id, 'pid' => getmypid()])."\n"; flush();
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) {
        if (str_contains(strtolower($sql), 'for update')) {
            echo json_encode(['locking_query' => true, 'transaction_level' => $connection->transactionLevel(),
                'pdo_transaction' => $connection->getPdo()->inTransaction()])."\n"; flush();
        }
    });
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    echo json_encode(['status' => $response->getStatusCode(), 'errors' => session()->get('errors')?->getBag('default')->messages() ?? [],
        'body' => $response->getStatusCode() >= 400 ? substr($response->getContent(), 0, 600) : null])."\n";
    $kernel->terminate($request, $response);
    exit;
}

$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `payment_integrity_validation_20261021` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
if (DB::selectOne('SELECT DATABASE() AS name')->name !== $database) throw new RuntimeException('Unsafe database');
Artisan::call('migrate:fresh', ['--force' => true]);
$branch = Branch::create(['branch_code' => 'BR001', 'branch_name' => 'Concurrency QA', 'branch_type' => 'main', 'is_active' => true]);
$package = Package::create(['name' => 'Concurrency QA', 'price' => 1000, 'is_active' => true]);
$staff = [];
foreach (['staff', 'staff', 'admin'] as $role) {
    $user = User::factory()->create(['role' => $role, 'admin_scope' => $role === 'admin' ? 'branch' : null,
        'branch_id' => $branch->id, 'is_active' => true, 'must_change_password' => false]);
    $user->two_factor_secret = (new PragmaRX\Google2FA\Google2FA)->generateSecretKey();
    $user->two_factor_confirmed_at = now(); $user->save(); $staff[] = $user;
}
SyncState::put('installation', (string) Str::uuid());
SyncState::put('references_at', now()->toIso8601String());
SyncState::put('activation_state', 'active');
$guard = new PDO('mysql:host=127.0.0.1;dbname='.$database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$observer = new PDO('mysql:host=127.0.0.1;dbname='.$database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$results = [];
$assert = function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$case = function () use ($branch, $package): FuneralCase {
    $i = FuneralCase::count() + 1;
    $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Concurrent client '.$i, 'relationship_to_deceased' => 'Spouse']);
    $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Concurrent deceased '.$i]);
    return FuneralCase::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id,
        'package_id' => $package->id, 'case_number' => $i, 'case_code' => 'CONCURRENT-'.$i, 'entry_source' => 'MAIN',
        'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => $package->name,
        'coffin_type' => 'Standard', 'wake_location' => 'QA', 'funeral_service_at' => today()->addDay(),
        'subtotal_amount' => 1000, 'discount_type' => 'NONE', 'discount_value_type' => 'AMOUNT',
        'discount_value' => 0, 'discount_amount' => 0, 'tax_rate' => 0, 'tax_amount' => 0,
        'total_amount' => 1000, 'total_paid' => 0, 'balance_amount' => 1000, 'payment_status' => 'UNPAID', 'case_status' => 'ACTIVE']);
};
$run = function (string $name, FuneralCase $case, array $jobs, string $lockTable = 'funeral_cases', ?int $lockId = null) use ($guard, $observer, $assert, &$results): array {
    $guard->beginTransaction();
    $lock = $guard->prepare('SELECT id FROM '.$lockTable.' WHERE id = ? FOR UPDATE');
    $lock->execute([$lockId ?? $case->id]);
    $lock->fetchAll();
    $lock->closeCursor();
    unset($lock);
    $processes = [];
    $started = microtime(true);
    try {
        foreach ($jobs as $job) {
            $process = new Process([PHP_BINARY, __FILE__, 'worker', base64_encode(json_encode($job))], dirname(__DIR__));
            $process->setTimeout(40); $process->start(); $processes[] = $process;
        }
        $waits = []; $evidenceType = 'innodb_lock_waits';
        do {
            $ids = [];
            foreach ($processes as $process) {
                $lines = explode("\n", trim($process->getOutput()));
                foreach ($lines as $line) { $row = json_decode($line, true); if (isset($row['ready'])) $ids[] = (int) $row['ready']; }
                if (!$process->isRunning()) throw new RuntimeException('Worker exited before lock overlap: '.$process->getOutput().$process->getErrorOutput());
            }
            if (count($ids) === 2) {
                $query = 'SELECT t.trx_mysql_thread_id AS connection_id, w.requesting_trx_id, w.blocking_trx_id FROM information_schema.INNODB_LOCK_WAITS w JOIN information_schema.INNODB_TRX t ON t.trx_id = w.requesting_trx_id WHERE t.trx_mysql_thread_id IN ('.implode(',', $ids).')';
                // MariaDB caches the InnoDB monitoring snapshot while a PDO statement remains alive.
                $statement = $observer->query($query);
                $waits = $statement->fetchAll(PDO::FETCH_ASSOC);
                $statement->closeCursor();
                unset($statement);
                if (count($waits) < 2) {
                    // MariaDB may report blocked native prepared SELECTs as Statistics, before INNODB_TRX lists them.
                    // Prove overlap using both live server queries AND worker transaction state, never elapsed time alone.
                    $active = array_values(array_filter($observer->query('SHOW FULL PROCESSLIST')->fetchAll(PDO::FETCH_ASSOC),
                        fn ($p) => in_array((int) $p['Id'], $ids, true) && $p['db'] === 'payment_integrity_validation_20261021'
                            && (int) $p['Time'] >= 1 && str_contains(strtolower($p['Info'] ?? ''), 'for update')));
                    $inTransactions = count(array_filter($processes, function ($process) {
                        foreach (explode("\n", trim($process->getOutput())) as $line) {
                            $event = json_decode($line, true);
                            if (($event['locking_query'] ?? false) && ($event['transaction_level'] ?? 0) > 0 && ($event['pdo_transaction'] ?? false)) return true;
                        }
                        return false;
                    }));
                    if (count($active) === 2 && $inTransactions === 2) {
                        $waits = $active; $evidenceType = 'live_prepared_locking_queries_and_active_transactions';
                    }
                }
            }
            if (count($waits) < 2) usleep(50000);
        } while (count($waits) < 2 && microtime(true) - $started < 20);
        $assert(count($ids) === 2 && $ids[0] !== $ids[1] && count($waits) === 2,
            'No proven overlapping independent database waits: '.$name.' '.json_encode(['ids' => $ids, 'waits' => $waits,
                'transactions' => $observer->query('SELECT trx_mysql_thread_id, trx_state, trx_query FROM information_schema.INNODB_TRX')->fetchAll(PDO::FETCH_ASSOC),
                'processes' => array_values(array_filter($observer->query('SHOW FULL PROCESSLIST')->fetchAll(PDO::FETCH_ASSOC), fn ($p) => $p['db'] === 'payment_integrity_validation_20261021')),
                'workers' => array_map(fn ($p) => $p->getOutput().$p->getErrorOutput(), $processes)]));
        $guard->commit();
        $responses = [];
        foreach ($processes as $process) {
            $process->wait();
            $assert($process->isSuccessful(), 'Worker failed: '.$process->getErrorOutput());
            $lines = array_filter(explode("\n", trim($process->getOutput())));
            $result = json_decode(end($lines), true);
            $assert(isset($result['status']), 'Missing HTTP result: '.$process->getOutput());
            $responses[] = $result;
        }
        $results[] = ['scenario' => $name, 'connection_ids' => $ids, 'overlap_evidence_type' => $evidenceType, 'overlapping_database_queries' => $waits,
            'http_statuses' => array_column($responses, 'status'), 'seconds' => round(microtime(true) - $started, 3)];
        return $responses;
    } finally {
        if ($guard->inTransaction()) $guard->rollBack();
        foreach ($processes as $process) if ($process->isRunning()) $process->stop(1);
    }
};

foreach (['standalone', 'branch'] as $mode) {
    foreach (['distinct' => [500, 500, 2, 1000], 'same_identity' => [400, 400, 1, 400], 'overpayment' => [700, 700, 1, 700]] as $name => [$a, $b, $count, $paid]) {
        $record = $case(); $identity = (string) Str::uuid(); $jobs = [];
        foreach ([$a, $b] as $i => $amount) $jobs[] = ['mode' => $mode, 'user' => $staff[$i]->id, 'path' => '/payments/pay',
            'payload' => ['funeral_case_id' => $record->id, 'submission_id' => $name === 'same_identity' ? $identity : (string) Str::uuid(),
                'amount_paid' => $amount, 'paid_at' => today()->setTime(10, 0)->format('Y-m-d H:i:s'), 'payment_method' => 'cash']];
        $responses = $run($mode.'_'.$name, $record, $jobs);
        $assert(Payment::where('funeral_case_id', $record->id)->count() === $count, 'Payment count '.$name.': '.json_encode($responses));
        $record->refresh();
        $assert((int) round($record->total_paid * 100) === $paid * 100 && (int) round($record->balance_amount * 100) === (1000 - $paid) * 100 && $record->payment_status === ($paid === 1000 ? 'PAID' : 'PARTIAL'), 'Incorrect totals '.$name);
        $statuses = array_column($responses, 'status'); sort($statuses);
        $assert($statuses === [302, 302], 'Unexpected responses '.json_encode($responses));
        $errors = count(array_filter($responses, fn ($response) => !empty($response['errors'])));
        $assert($errors === ($name === 'overpayment' ? 1 : 0), 'Unexpected validation outcome '.json_encode($responses));
        $results[array_key_last($results)]['outcome'] = ['payment_count' => $count, 'total_paid' => (float) $record->total_paid,
            'balance' => (float) $record->balance_amount, 'payment_status' => $record->payment_status, 'rejected_requests' => $errors];
    }
}

// Correction races use independent HTTP requests too, with the same overlap proof.
$record = $case();
$payment = Payment::create(['branch_id' => $branch->id, 'funeral_case_id' => $record->id, 'payment_record_no' => 'CORRECTION-QA',
    'amount' => 400, 'status' => 'POSTED', 'payment_method' => 'cash', 'paid_at' => now(), 'recorded_by' => $staff[0]->id]);
$record->recalculatePaymentTotals();
$jobs = [];
foreach ([0, 1] as $i) $jobs[] = ['mode' => 'standalone', 'user' => $staff[$i]->id, 'path' => '/payments/'.$payment->id.'/correction-request',
    'payload' => ['correction_type' => 'CORRECT', 'amount' => 300, 'reason' => 'Concurrent correction request QA.']];
$responses = $run('concurrent_correction_requests', $record, $jobs, 'payments', $payment->id);
$statuses = array_column($responses, 'status'); sort($statuses);
$assert($statuses === [302, 422], 'Unexpected correction request responses');
$assert(PaymentCorrectionRequest::where('payment_id', $payment->id)->count() === 1, 'Duplicate pending corrections');
$correction = PaymentCorrectionRequest::where('payment_id', $payment->id)->firstOrFail();
$jobs = array_fill(0, 2, ['mode' => 'standalone', 'user' => $staff[2]->id,
    'path' => '/admin/payment-corrections/'.$correction->id.'/approve', 'payload' => []]);
$responses = $run('concurrent_correction_approvals', $record, $jobs, 'payment_correction_requests', $correction->id);
$statuses = array_column($responses, 'status'); sort($statuses);
$assert($statuses === [302, 409], 'Unexpected correction approval responses');
$assert(Payment::where('replaces_payment_id', $payment->id)->count() === 1, 'Duplicate replacement');
$assert($payment->fresh()->status === 'VOIDED' && (float) $record->fresh()->total_paid === 300.0, 'Incorrect correction totals');
$results[array_key_last($results)]['outcome'] = ['replacement_count' => 1, 'original_status' => $payment->fresh()->status,
    'total_paid' => (float) $record->fresh()->total_paid, 'balance' => (float) $record->fresh()->balance_amount];

foreach (['standalone', 'branch'] as $mode) {
    foreach ([300, 600] as $correctedAmount) {
        foreach ([false, true] as $reverse) {
            $record = $case();
            $original = Payment::create(['branch_id' => $branch->id, 'funeral_case_id' => $record->id,
                'payment_record_no' => 'MIXED-'.$record->id, 'amount' => 400, 'status' => 'POSTED',
                'payment_method' => 'cash', 'paid_at' => now(), 'recorded_by' => $staff[0]->id]);
            $record->recalculatePaymentTotals();
            $correction = PaymentCorrectionRequest::create(['branch_id' => $branch->id, 'payment_id' => $original->id,
                'requested_by' => $staff[0]->id, 'correction_type' => 'CORRECT', 'reason' => 'Mixed concurrency QA.',
                'original_values' => ['amount' => 400], 'proposed_values' => ['amount' => $correctedAmount]]);
            $identity = (string) Str::uuid();
            $jobs = [
                ['mode' => $mode, 'user' => $staff[1]->id, 'path' => '/payments/pay', 'payload' => [
                    'funeral_case_id' => $record->id, 'submission_id' => $identity, 'amount_paid' => 500,
                    'paid_at' => today()->setTime(10, 0)->format('Y-m-d H:i:s'), 'payment_method' => 'cash']],
                ['mode' => $mode, 'user' => $staff[2]->id, 'path' => '/admin/payment-corrections/'.$correction->id.'/approve', 'payload' => []],
            ];
            if ($reverse) $jobs = array_reverse($jobs);
            $responses = $run($mode.'_payment_and_correction_'.$correctedAmount.'_'.(int) $reverse, $record, $jobs);
            $record->refresh(); $correction->refresh();
            $posted = (float) Payment::where('funeral_case_id', $record->id)->where('status', 'POSTED')->sum('amount');
            $assert($posted <= 1000 && (float) $record->total_paid === $posted && (float) $record->balance_amount === 1000 - $posted
                && $record->payment_status === 'PARTIAL', 'Mixed financial invariant failed');
            $normalCount = Payment::where('submission_id', $identity)->count();
            $replacements = Payment::where('replaces_payment_id', $original->id)->count();
            if ($correctedAmount === 300) {
                $assert($normalCount === 1 && $replacements === 1 && $posted === 800.0 && $correction->status === 'APPROVED',
                    'Two compatible mixed operations must both commit: '.json_encode($responses));
            } else {
                $assert(($normalCount === 1 && $replacements === 0 && $posted === 900.0 && $correction->status === 'PENDING')
                    || ($normalCount === 0 && $replacements === 1 && $posted === 600.0 && $correction->status === 'APPROVED'),
                    'Incompatible mixed operations must have one safe rejection: '.json_encode($responses));
            }
            $results[array_key_last($results)]['outcome'] = ['normal_payments' => $normalCount, 'replacements' => $replacements,
                'total_paid' => $posted, 'balance' => (float) $record->balance_amount, 'correction_status' => $correction->status];
        }
    }
}

$duplicate = (array) DB::table('payments')->whereNotNull('submission_id')->first();
unset($duplicate['id']);
$duplicate['payment_record_no'] = 'UNIQUE-CONSTRAINT-QA'; $duplicate['receipt_number'] = null;
$before = Payment::count(); $uniqueRejected = false;
DB::beginTransaction();
try {
    DB::table('payments')->insert($duplicate);
} catch (Illuminate\Database\QueryException $exception) {
    $uniqueRejected = (int) ($exception->errorInfo[1] ?? 0) === 1062
        && str_contains($exception->getMessage(), 'payments_branch_submission_unique');
} finally {
    DB::rollBack();
}
$assert($uniqueRejected && Payment::count() === $before, 'Submission unique constraint/rollback failed');

echo json_encode(['database' => $database, 'server' => DB::selectOne('SELECT VERSION() AS version')->version,
    'passed' => count($results), 'submission_unique_constraint_and_rollback_verified' => $uniqueRejected,
    'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
