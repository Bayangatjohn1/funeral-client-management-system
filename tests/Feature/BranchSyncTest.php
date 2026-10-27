<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\CaseAttachment;
use App\Models\IntakeDraft;
use App\Models\AddOnCatalog;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\BranchSync\BranchSyncService;
use App\Services\BranchSync\SyncSchema;
use App\Services\BranchSync\SyncSnapshot;
use App\Services\BranchSync\SyncState;
use App\Services\BranchSync\SyncTransport;
use App\Services\BranchSync\OutboxPayload;
use App\Support\BranchScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BranchSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $token;
    private string $installation;

    protected function setUp(): void
    {
        parent::setUp();
        BranchScope::clear();
        Storage::fake('local');
        Storage::fake('public');
        $this->token = str_repeat('a', 64);
        $this->installation = (string) Str::uuid();
    }

    private function branch(string $code = 'BR001'): Branch
    {
        return Branch::firstOrCreate(['branch_code' => $code], ['branch_name' => $code, 'is_active' => true]);
    }

    private function fixtures(int $count = 1, int $payments = 1): array
    {
        $branch = $this->branch();
        $package = Package::create(['name' => 'Offline Package', 'price' => 1000, 'is_active' => true]);
        $cases = [];
        $offset = (int) FuneralCase::max('case_number');
        for ($i = $offset + 1; $i <= $offset + $count; $i++) {
            $client = Client::create(['branch_id' => $branch->id, 'full_name' => 'Client '.$i, 'relationship_to_deceased' => 'Spouse']);
            $deceased = Deceased::create(['branch_id' => $branch->id, 'client_id' => $client->id, 'full_name' => 'Deceased '.$i]);
            $case = FuneralCase::create([
                'branch_id' => $branch->id, 'client_id' => $client->id, 'deceased_id' => $deceased->id,
                'package_id' => $package->id, 'case_number' => $i, 'case_code' => 'CASE-'.$i,
                'service_type' => 'Burial', 'service_requested_at' => today(), 'service_package' => 'Offline Package',
                'coffin_type' => 'Standard', 'wake_location' => 'Test Wake', 'funeral_service_at' => today()->addDay(),
                'subtotal_amount' => 1000, 'discount_type' => 'NONE', 'discount_value_type' => 'AMOUNT',
                'discount_value' => 0, 'discount_amount' => 0, 'tax_rate' => 0, 'tax_amount' => 0,
                'total_amount' => 1000, 'total_paid' => 0, 'balance_amount' => 1000,
                'payment_status' => 'UNPAID', 'case_status' => 'ACTIVE', 'entry_source' => 'MAIN', 'verification_status' => 'VERIFIED',
            ]);
            for ($p = 1; $p <= $payments; $p++) {
                Payment::create(['branch_id' => $branch->id, 'funeral_case_id' => $case->id,
                    'payment_record_no' => 'PAY-BR001-2026-'.$i.'-'.$p,
                    'amount' => 1000 / $payments, 'status' => 'POSTED', 'received_by' => 'Cashier Name']);
            }
            $case->recalculatePaymentTotals();
            $cases[] = $case;
        }
        return [$branch, $cases];
    }

    private function peer(): void
    {
        config(['branch_sync.mode' => 'central']);
        DB::table('branch_sync_peers')->insert([
            'branch_code' => 'BR001', 'token_hash' => hash('sha256', $this->token),
            'installation_id' => $this->installation, 'enabled' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function headers(string $code = 'BR001'): array
    {
        return ['Authorization' => 'Bearer '.$this->token, 'X-Branch-Code' => $code];
    }

    private function push(array $snapshot, int $version = 1)
    {
        return $this->postJson('/api/branch-sync/v1/snapshots', ['sealed' => SyncTransport::seal([
            'installation' => $this->installation, 'version' => $version, 'snapshot' => $snapshot,
        ], $this->token)], $this->headers());
    }

    public function test_controlled_cutover_keeps_central_writable_until_frozen_and_requires_readiness(): void
    {
        [$branch] = $this->fixtures();
        config(['branch_sync.mode' => 'central']);
        $this->artisan('branch:sync', ['action' => 'issue-token', '--branch' => 'BR001'])->assertSuccessful();
        $peer = DB::table('branch_sync_peers')->first();
        $this->assertSame('prepared', $peer->activation_state);
        $this->assertSame(1, FuneralCase::locallyOwned()->count());
        // Use known test credential without printing the issued secret.
        DB::table('branch_sync_peers')->update(['token_hash' => hash('sha256', $this->token)]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($staff)->post('/intake/main', [])->assertStatus(302);
        $this->getJson('/api/branch-sync/v1/bootstrap', $this->headers())->assertStatus(409);
        $this->artisan('branch:sync', ['action' => 'freeze', '--branch' => 'BR001'])->assertSuccessful();
        $this->assertSame(0, FuneralCase::locallyOwned()->count());
        $this->actingAs($staff)->post('/intake/main', [])->assertStatus(409);
        $this->artisan('branch:sync', ['action' => 'activate', '--branch' => 'BR001', '--reviewed' => true])->assertFailed();
        $bootstrap = $this->getJson('/api/branch-sync/v1/bootstrap', $this->headers())->assertOk();
        $data = SyncTransport::open($bootstrap->json('sealed'), $this->token);
        $this->postJson('/api/branch-sync/v1/ready', ['installation' => $data['installation'],
            'bootstrap_hash' => str_repeat('0', 64)], $this->headers())->assertStatus(409);
        $this->postJson('/api/branch-sync/v1/ready', ['installation' => $data['installation'],
            'bootstrap_hash' => hash('sha256', json_encode($data['snapshot'], JSON_THROW_ON_ERROR))], $this->headers())->assertOk();
        $this->artisan('branch:sync', ['action' => 'activate', '--branch' => 'BR001'])->assertFailed();
        $this->artisan('branch:sync', ['action' => 'activate', '--branch' => 'BR001', '--reviewed' => true])->assertSuccessful();
        $this->artisan('branch:sync', ['action' => 'cancel', '--branch' => 'BR001'])->assertFailed();
        $this->artisan('branch:sync', ['action' => 'revoke-token', '--branch' => 'BR001'])->assertSuccessful();
        $this->actingAs($staff)->post('/intake/main', [])->assertStatus(409);
        $this->assertSame($peer->installation_id, DB::table('branch_sync_peers')->value('installation_id'));
    }

    public function test_failed_pre_activation_provisioning_can_be_cancelled_without_deleting_checkpoints(): void
    {
        $this->fixtures();
        $this->peer();
        DB::table('branch_sync_peers')->update(['activation_state' => 'frozen']);
        $this->artisan('branch:sync', ['action' => 'cancel', '--branch' => 'BR001'])->assertSuccessful();
        $this->assertDatabaseCount('branch_sync_peers', 1);
        $this->assertSame($this->installation, DB::table('branch_sync_peers')->value('installation_id'));
        $this->assertSame(1, FuneralCase::locallyOwned()->count());
        $this->getJson('/api/branch-sync/v1/references', $this->headers())->assertUnauthorized();
    }

    public function test_frozen_branch_cannot_record_locally_or_capture_a_snapshot(): void
    {
        [$branch] = $this->fixtures();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001']);
        SyncState::put('installation', $this->installation);
        SyncState::put('references_at', now()->toIso8601String());
        SyncState::put('activation_state', 'frozen');
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->actingAs($staff)->get('/staff')->assertOk();
        $this->post('/intake/main', [])->assertStatus(409);
        $this->assertNull(app(BranchSyncService::class)->capture());
        $this->assertDatabaseCount('branch_sync_outbox', 0);
        DB::table('funeral_cases')->update(['interment_at' => now()->subDay()]);
        SyncState::put('dirty', false);
        $this->artisan('cases:complete-past-interments')->assertSuccessful();
        $this->assertSame('ACTIVE', FuneralCase::first()->case_status);
        SyncState::put('activation_state', 'active');
        $this->artisan('cases:complete-past-interments')->assertSuccessful();
        $this->assertSame('COMPLETED', FuneralCase::first()->case_status);
        $this->assertTrue(SyncState::get('dirty'));
    }

    public function test_snapshot_capacity_failure_retains_local_records_and_checkpoint(): void
    {
        $this->fixtures();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001', 'branch_sync.max_snapshot_bytes' => 100]);
        SyncState::put('installation', $this->installation);
        $this->assertFalse(app(BranchSyncService::class)->run());
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('branch_sync_outbox', 0);
        $this->assertSame(0, (int) SyncState::get('sequence', 0));
        $this->assertSame('Sync failed', app(BranchSyncService::class)->status()['sync_state']);
    }

    public function test_capacity_measurement_and_replay_with_synthetic_history(): void
    {
        $count = max(10, min(10000, (int) (getenv('OFFLINE_CAPACITY_CASES') ?: 100)));
        $this->fixtures($count, 5);
        memory_reset_peak_usage();
        $start = microtime(true);
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $export = microtime(true) - $start;
        $json = json_encode($snapshot, JSON_THROW_ON_ERROR);
        if (strlen($json) > config('branch_sync.max_snapshot_bytes')) {
            config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001']);
            SyncState::put('installation', $this->installation);
            $this->assertFalse(app(BranchSyncService::class)->run());
            $this->assertDatabaseCount('branch_sync_outbox', 0);
            $this->assertSame($count * 5, DB::table('payments')->count());
            file_put_contents(storage_path('framework/offline-capacity-'.DB::getDriverName().'-'.$count.'.json'), json_encode([
                'engine' => DB::getDriverName(), 'cases' => $count, 'payments' => $count * 5,
                'snapshot_bytes' => strlen($json), 'export_seconds' => $export,
                'peak_memory_bytes' => memory_get_peak_usage(true), 'rejected_over_limit' => true,
                'note' => 'No network attempted; source transactions and sequence retained. Synthetic history without attachments.',
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return;
        }
        $start = microtime(true);
        $sealed = SyncTransport::seal(['installation' => $this->installation, 'version' => 1, 'snapshot' => $snapshot], $this->token);
        $encryption = microtime(true) - $start;
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001']);
        SyncState::put('installation', $this->installation);
        $start = microtime(true);
        $outboxId = app(BranchSyncService::class)->capture();
        $capture = microtime(true) - $start;
        $outboxBytes = strlen(DB::table('branch_sync_outbox')->where('id', $outboxId)->value('payload'));
        $this->assertSame($snapshot, OutboxPayload::decode(DB::table('branch_sync_outbox')->where('id', $outboxId)->value('payload')));
        $this->peer();
        $start = microtime(true);
        $this->push($snapshot)->assertOk();
        $apply = microtime(true) - $start;
        $this->push($snapshot)->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame($count * 5, DB::table('payments')->count());
        file_put_contents(storage_path('framework/offline-capacity-'.DB::getDriverName().'-'.$count.'.json'), json_encode([
            'engine' => DB::getDriverName(), 'cases' => $count, 'payments' => $count * 5,
            'snapshot_bytes' => strlen($json), 'wire_bytes' => strlen($sealed),
            'export_seconds' => $export, 'encryption_seconds' => $encryption, 'http_apply_seconds' => $apply,
            'capture_seconds' => $capture, 'encrypted_outbox_bytes' => $outboxBytes,
            'peak_memory_bytes' => memory_get_peak_usage(true),
            'compression' => 'BRS2 gzip before authenticated encryption',
            'database_allocated_bytes' => DB::getDriverName() === 'mysql'
                ? (int) DB::selectOne('SELECT COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) AS bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->bytes
                : (int) DB::selectOne('PRAGMA page_count')->page_count * (int) DB::selectOne('PRAGMA page_size')->page_size,
            'immutable_file_spool_bytes' => array_sum(array_map(fn ($path) => Storage::disk('local')->size($path), Storage::disk('local')->allFiles('branch-sync'))),
            'transfer_seconds_at_1mbps_theoretical' => strlen($sealed) * 8 / 1000000,
            'note' => 'Synthetic history; in-process HTTP, not an actual network transfer. No attachment blobs.',
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    public function test_transport_and_file_capacity_boundaries_are_enforced(): void
    {
        $limit = config('branch_sync.max_snapshot_bytes');
        $payload = ['padding' => str_repeat('x', $limit - 14)];
        $this->assertSame($payload, SyncTransport::open(SyncTransport::seal($payload, $this->token), $this->token));
        try {
            SyncTransport::open(SyncTransport::seal(['padding' => str_repeat('x', $limit + 1024)], $this->token), $this->token);
            $this->fail('Oversized transport must fail.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('limit', $error->getMessage());
        }
        $this->fixtures();
        $this->peer();
        $bytes = "%PDF-1.4\n".str_repeat(' ', config('branch_sync.max_file_bytes') - 9);
        $hash = hash('sha256', $bytes);
        $this->postJson('/api/branch-sync/v1/files/'.$hash,
            ['file' => UploadedFile::fake()->createWithContent('boundary.pdf', $bytes)], $this->headers())->assertOk();
        $this->assertSame(config('branch_sync.max_file_bytes'), Storage::disk('local')->size('branch-sync/incoming/BR001/'.$hash));
        $file = UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf');
        $this->postJson('/api/branch-sync/v1/files/'.str_repeat('0', 64), ['file' => $file], $this->headers())->assertUnprocessable();
        $this->assertSame(0, (int) DB::table('branch_sync_peers')->value('accepted_version'));
    }

    public function test_compressed_transport_accepts_legacy_payloads_and_rejects_expansion_bombs(): void
    {
        $cipher = new \Illuminate\Encryption\Encrypter(hash('sha256', $this->token, true), 'AES-256-CBC');
        $this->assertSame(['protocol' => 1], SyncTransport::open($cipher->encryptString('{"protocol":1}'), $this->token));
        config(['branch_sync.max_snapshot_bytes' => 100]);
        $bomb = $cipher->encryptString('BRS2:'.gzencode(str_repeat('x', 1200)));
        $this->expectException(\RuntimeException::class);
        SyncTransport::open($bomb, $this->token);
    }

    public function test_three_day_outage_accumulates_local_transactions_and_acknowledges_both_versions(): void
    {
        $this->fixtures(20, 5);
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001',
            'branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        SyncState::put('installation', $this->installation);
        Http::fake(fn () => Http::failedConnection());
        $sync = app(BranchSyncService::class);
        $this->assertFalse($sync->run());
        $this->assertSame('Offline / central unreachable at last attempt', $sync->status()['connection_state']);
        $this->travel(72)->hours();
        $this->fixtures(100, 5);
        SyncState::put('dirty', true);
        $accepted = 0;
        $acceptedHash = null;
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) use (&$accepted, &$acceptedHash) {
            if (str_ends_with($request->url(), '/references')) {
                return Http::response(['sealed' => SyncTransport::seal([
                    'installation' => $this->installation, 'accepted_version' => $accepted, 'accepted_hash' => $acceptedHash,
                    'snapshot' => app(SyncSnapshot::class)->export('BR001', 'reference'),
                ], $this->token)]);
            }
            $envelope = SyncTransport::open($request['sealed'], $this->token);
            $accepted = $envelope['version'];
            $acceptedHash = hash('sha256', json_encode($envelope['snapshot'], JSON_THROW_ON_ERROR));
            return Http::response(['version' => $accepted, 'hash' => $acceptedHash]);
        });
        $this->assertTrue($sync->run());
        $this->assertSame('Pending sync', $sync->status()['sync_state']);
        $this->assertTrue($sync->run(), json_encode($sync->status(), JSON_THROW_ON_ERROR));
        $this->assertSame(2, $accepted);
        $this->assertSame('Synced', $sync->status()['sync_state']);
        $this->assertDatabaseCount('funeral_cases', 120);
        $this->assertDatabaseCount('payments', 600);
        $this->assertSame(120000.0, (float) DB::table('payments')->sum('amount'));
        $this->assertSame('', DB::table('branch_sync_outbox')->orderBy('version')->value('payload'));
    }

    public function test_snapshot_delivery_ten_times_does_not_duplicate_payments(): void
    {
        $this->fixtures();
        $this->peer();
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        for ($i = 0; $i < 10; $i++) {
            $this->push($snapshot)->assertOk()->assertJsonPath('duplicate', $i > 0);
        }
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(1, (int) DB::table('branch_sync_peers')->value('accepted_version'));
    }

    public function test_worker_batches_file_inventory_and_status_follows_persisted_acknowledgment(): void
    {
        $this->fixtures();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001',
            'branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        SyncState::put('installation', $this->installation);
        SyncState::put('dirty', true);
        $sync = app(BranchSyncService::class);
        $this->assertSame('Pending sync', $sync->status()['sync_state']);
        $id = $sync->capture();
        $pending = DB::table('branch_sync_outbox')->find($id);
        $snapshot = OutboxPayload::decode($pending->payload);
        // Worker-only transport fixture: central file contents/application are covered separately.
        for ($i = 0; $i < 5001; $i++) {
            $snapshot['entities']['case_attachments'][] = ['files' => ['path' => ['hash' => hash('sha256', (string) $i)]]];
        }
        $json = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $fingerprint = hash('sha256', $json);
        DB::table('branch_sync_outbox')->where('id', $id)->update(['payload' => Crypt::encryptString($json), 'fingerprint' => $fingerprint]);
        $batches = [];
        Http::fake(function ($request) use ($sync, $fingerprint, &$batches) {
            $this->assertSame('Syncing', $sync->status()['sync_state']);
            if (str_ends_with($request->url(), '/references')) {
                return Http::response(['sealed' => SyncTransport::seal([
                    'installation' => $this->installation, 'accepted_version' => 0, 'accepted_hash' => null,
                    'snapshot' => app(SyncSnapshot::class)->export('BR001', 'reference'),
                ], $this->token)]);
            }
            if (str_ends_with($request->url(), '/files/check')) {
                $batches[] = count($request['hashes']);
                return Http::response(['missing' => []]);
            }
            return Http::response(['version' => 1, 'hash' => $fingerprint]);
        });
        $this->assertTrue($sync->run());
        $this->assertSame([5000, 1], $batches);
        $this->assertSame('Synced', $sync->status()['sync_state']);
        $this->assertSame('Online at last check', $sync->status()['connection_state']);
        SyncState::put('recovery_required', true);
        $this->assertSame('Requires attention', $sync->status()['sync_state']);
    }

    public function test_mysql_packet_guard_refuses_oversized_outbox_without_changing_source(): void
    {
        if (DB::getDriverName() !== 'mysql' || (int) DB::selectOne('SELECT @@max_allowed_packet AS bytes')->bytes > 2 * 1024 * 1024) {
            $this->markTestSkipped('Exercises the actual small-packet localhost configuration without changing server settings.');
        }
        $this->fixtures();
        try {
            OutboxPayload::encode(json_encode(['synthetic' => bin2hex(random_bytes(1500000))], JSON_THROW_ON_ERROR));
            $this->fail('The packet guard must reject before issuing an oversized database statement.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('packet capacity', $error->getMessage());
        }
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('branch_sync_outbox', 0);
        $this->assertSame(0, (int) SyncState::get('sequence', 0));
    }

    public function test_mysql_encrypted_backup_restores_pending_outbox_files_and_replays_lost_ack(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires the guarded localhost MariaDB validation configuration.');
        }
        $this->fixtures(3, 5);
        $this->peer();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001',
            'branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token,
            'backup.disk' => 'local', 'backup.directory' => 'system-backups']);
        SyncState::put('installation', $this->installation);
        SyncState::put('references_at', now()->toIso8601String());
        Storage::disk('local')->put('branch-sync/blobs/recovery-fixture', 'immutable pending bytes');
        Storage::disk('public')->put('uploads/recovery.jpg', 'public recovery bytes');
        app(BranchSyncService::class)->capture();
        // mysqldump is a separate process: commit the fixture, then restore PHPUnit's transaction afterwards.
        DB::commit();
        try {
            $started = microtime(true);
            $backup = app(\App\Services\SystemBackupService::class)->create();
            $backupSeconds = microtime(true) - $started;
            $this->assertSame('verified', $backup->status);
            $zipPath = Storage::disk('local')->path('restoration.zip');
            file_put_contents($zipPath, Crypt::decryptString(Storage::disk('local')->get($backup->path)));
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($zipPath));
            $this->assertSame('immutable pending bytes', $zip->getFromName('private/branch-sync/blobs/recovery-fixture'));
            $this->assertSame('public recovery bytes', $zip->getFromName('public/uploads/recovery.jpg'));
            $sql = $zip->getFromName('database.sql');
            $this->assertStringContainsString('branch_sync_outbox', $sql);
            $restoreFiles = Storage::disk('local')->path('isolated-restored-files');
            $this->assertTrue($zip->extractTo($restoreFiles));
            $this->assertSame('immutable pending bytes', file_get_contents($restoreFiles.'/private/branch-sync/blobs/recovery-fixture'));
            $this->assertSame('public recovery bytes', file_get_contents($restoreFiles.'/public/uploads/recovery.jpg'));
            $zip->close();
            $mysql = config('database.connections.mysql');
            $process = new \Symfony\Component\Process\Process([
                'C:/xampp/mysql/bin/mysql.exe', '--host=127.0.0.1', '--user='.$mysql['username'],
                '--database=offline_restore_20261021', '--binary-mode',
            ], null, ['MYSQL_PWD' => (string) $mysql['password']]);
            $started = microtime(true);
            $process->setInput($sql)->setTimeout(120)->mustRun();
            $restoreSeconds = microtime(true) - $started;
            config(['database.connections.sync_restore' => array_replace($mysql, ['database' => 'offline_restore_20261021'])]);
            DB::setDefaultConnection('sync_restore');
            $this->assertSame(15, DB::table('payments')->count());
            $this->assertSame($this->installation, SyncState::get('installation'));
            $pending = DB::table('branch_sync_outbox')->first();
            $this->assertNull($pending->acknowledged_at);
            $this->assertSame(1, (int) SyncState::get('sequence'));
            $references = app(SyncSnapshot::class)->export('BR001', 'reference');
            Http::fake([
                'central.test/api/branch-sync/v1/references' => Http::response(['sealed' => SyncTransport::seal([
                    'installation' => $this->installation, 'accepted_version' => 1,
                    'accepted_hash' => $pending->fingerprint, 'snapshot' => $references,
                ], $this->token)]),
                'central.test/api/branch-sync/v1/snapshots' => Http::response(['version' => 1, 'hash' => $pending->fingerprint, 'duplicate' => true]),
            ]);
            $this->assertTrue(app(BranchSyncService::class)->run());
            $this->assertNotNull(DB::table('branch_sync_outbox')->first()->acknowledged_at);
            $this->assertSame(15, DB::table('payments')->count());
            $this->assertSame(3000.0, (float) DB::table('payments')->sum('amount'));
            file_put_contents(storage_path('framework/offline-restore-measurement.json'), json_encode([
                'engine' => 'MariaDB 10.4.32', 'cases' => 3, 'payments' => 15,
                'backup_seconds' => $backupSeconds, 'restore_sql_seconds' => $restoreSeconds,
                'encrypted_archive_bytes' => $backup->size_bytes, 'sql_bytes' => strlen($sql),
                'restored_pending_version' => 1, 'acknowledged_after_replay' => true,
                'public_and_private_files_restored' => true,
                'note' => 'Actual local mysqldump, encrypted archive, isolated SQL/file restore. Reconnection acknowledgment uses fake HTTP; not a full physical-server recovery.',
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } finally {
            DB::setDefaultConnection('mysql');
            DB::purge('sync_restore');
            // Confined to the explicitly guarded disposable validation database.
            Artisan::call('migrate:fresh', ['--database' => 'mysql', '--force' => true]);
            DB::beginTransaction();
        }
    }

    public function test_invalid_financial_snapshot_rolls_back_the_entire_batch(): void
    {
        $this->fixtures();
        $this->peer();
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $snapshot['entities']['clients'][0]['data']['address'] = 'Must roll back';
        $snapshot['entities']['payments'][0]['data']['amount'] = 999;
        $this->push($snapshot)->assertStatus(409);
        $this->assertNull(DB::table('clients')->value('address'));
        $this->assertSame('1000.00', Payment::first()->amount);
        $this->assertSame(0, (int) DB::table('branch_sync_peers')->value('accepted_version'));
    }

    public function test_credentials_and_branch_isolation_are_enforced(): void
    {
        $this->fixtures();
        $other = $this->branch('BR002');
        $this->peer();
        $this->getJson('/api/branch-sync/v1/references')->assertUnauthorized();
        $this->getJson('/api/branch-sync/v1/references', $this->headers('BR002'))->assertUnauthorized();
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $snapshot['entities']['clients'][0]['data']['branch_id'] = app(SyncSchema::class)->uuid('branches', $other->id);
        $this->push($snapshot)->assertStatus(409);
        DB::table('branch_sync_peers')->update(['enabled' => false]);
        $this->getJson('/api/branch-sync/v1/references', $this->headers())->assertUnauthorized();
    }

    public function test_schema_tampering_unknown_tables_and_sequence_gaps_are_rejected(): void
    {
        $this->fixtures();
        $this->peer();
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $this->push($snapshot, 2)->assertStatus(409);
        $tampered = $snapshot;
        $tampered['entities']['users'] = [];
        $this->push($tampered)->assertStatus(409);
        $tampered = $snapshot;
        $tampered['schema'] = str_repeat('0', 64);
        $this->push($tampered)->assertStatus(409);
        $this->push($snapshot)->assertOk();
        $snapshot['entities']['clients'][0]['data']['address'] = 'Conflicting retry';
        $this->push($snapshot)->assertStatus(409);
    }

    public function test_deleted_financial_record_cannot_disappear_from_a_snapshot(): void
    {
        $this->fixtures();
        $this->peer();
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $snapshot['entities']['payments'] = [];
        $this->push($snapshot)->assertStatus(409);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_connection_failure_keeps_encrypted_outbox_and_retries_after_backoff(): void
    {
        $this->fixtures();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001',
            'branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        SyncState::put('installation', $this->installation);
        SyncState::put('sequence', 0);
        Http::fake(fn () => Http::failedConnection());
        $sync = app(BranchSyncService::class);
        $this->assertFalse($sync->run());
        $outbox = DB::table('branch_sync_outbox')->first();
        $this->assertNotNull($outbox);
        $this->assertNull($outbox->acknowledged_at);
        $this->assertSame(1, (int) $outbox->attempts);
        $this->assertStringNotContainsString('Client', $outbox->payload);
        $this->assertStringContainsString('Client', json_encode(OutboxPayload::decode($outbox->payload)));
        $references = app(SyncSnapshot::class)->export('BR001', 'reference');
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'central.test/api/branch-sync/v1/references' => Http::response(['sealed' => SyncTransport::seal([
                'installation' => $this->installation, 'accepted_version' => 0, 'accepted_hash' => null,
                'snapshot' => $references,
            ], $this->token)]),
            'central.test/api/branch-sync/v1/snapshots' => Http::response(['version' => 1, 'hash' => $outbox->fingerprint]),
        ]);
        $this->travel(72)->hours();
        $this->assertTrue($sync->run());
        $this->assertNotNull(DB::table('branch_sync_outbox')->first()->acknowledged_at);
        $this->assertNull($sync->status()['last_error']);
    }

    public function test_branch_payment_numbers_are_distinct_and_legacy_numbers_unchanged(): void
    {
        $this->assertSame('PAY-2026-000001', Payment::buildPaymentRecordNumber(1, now()->setYear(2026)));
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001']);
        $this->assertSame('PAY-BR001-2026-000001', Payment::buildPaymentRecordNumber(1, now()->setYear(2026)));
        config(['branch_sync.branch_code' => 'BR002']);
        $this->assertSame('PAY-BR002-2026-000001', Payment::buildPaymentRecordNumber(1, now()->setYear(2026)));
    }

    public function test_reference_payload_excludes_other_branch_accounts_and_reencrypts_mfa(): void
    {
        $branch = $this->branch();
        $other = $this->branch('BR002');
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $staff->two_factor_secret = 'ABCDEF123456';
        $staff->save();
        User::factory()->create(['role' => 'staff', 'branch_id' => $other->id]);
        $this->peer();
        $response = $this->getJson('/api/branch-sync/v1/references', $this->headers())->assertOk();
        $this->assertStringNotContainsString($staff->email, $response->getContent());
        $payload = SyncTransport::open($response->json('sealed'), $this->token);
        $this->assertCount(1, $payload['snapshot']['entities']['users']);
        $this->assertSame('ABCDEF123456', $payload['snapshot']['entities']['users'][0]['data']['two_factor_secret']);
        app(SyncSnapshot::class)->apply($payload['snapshot'], 'BR001', 'reference');
        $this->assertSame('ABCDEF123456', $staff->fresh()->two_factor_secret);
    }

    public function test_uuid_mapping_imports_100_cases_and_500_payments_into_an_independent_database(): void
    {
        $this->fixtures(100, 5);
        $references = app(SyncSnapshot::class)->export('BR001', 'reference');
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        config(['database.connections.sync_receiver' => DB::getDriverName() === 'mysql'
            ? array_replace(config('database.connections.mysql'), ['database' => 'offline_receiver_20261021'])
            : config('database.connections.sqlite')]);
        Artisan::call('migrate:fresh', ['--database' => 'sync_receiver', '--force' => true]);
        $sourceConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('sync_receiver');
        try {
            // Force different local primary keys to prove relationships use UUID mappings.
            DB::table('branches')->insert(['id' => 99, 'branch_code' => 'OTHER', 'branch_name' => 'Other', 'is_active' => true]);
            app(SyncSnapshot::class)->apply($references, 'BR001', 'reference');
            app(SyncSnapshot::class)->apply($snapshot, 'BR001', 'operational');
            app(SyncSnapshot::class)->apply($snapshot, 'BR001', 'operational');
            $this->assertSame(100, DB::table('funeral_cases')->count());
            $this->assertSame(500, DB::table('payments')->count());
            $this->assertSame(100000.0, (float) DB::table('payments')->sum('amount'));
            $this->assertSame(0.0, (float) DB::table('funeral_cases')->sum('balance_amount'));
            $this->assertSame(100, (int) DB::table('funeral_cases')->first()->branch_id);
        } finally {
            DB::setDefaultConnection($sourceConnection);
            DB::purge('sync_receiver');
        }
    }

    public function test_files_are_checksum_verified_branch_scoped_and_applied_only_after_upload(): void
    {
        [, $cases] = $this->fixtures();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII=');
        Storage::disk('public')->put('uploads/test.png', $png);
        CaseAttachment::create(['case_id' => $cases[0]->id, 'attachment_type' => 'tarpaulin',
            'file_name' => 'test.png', 'file_path' => 'uploads/test.png']);
        $this->peer();
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $hash = hash('sha256', $png);
        $this->push($snapshot)->assertStatus(409);
        $this->postJson('/api/branch-sync/v1/files/check', ['hashes' => [$hash]], $this->headers())
            ->assertOk()->assertJsonPath('missing.0', $hash);
        $this->post('/api/branch-sync/v1/files/'.$hash, ['file' => UploadedFile::fake()->createWithContent('test.png', $png)], $this->headers())
            ->assertOk()->assertJsonPath('hash', $hash);
        $this->push($snapshot)->assertOk();
        Storage::disk('public')->assertExists('branch-sync/BR001/'.$hash.'.png');
        $this->assertSame($png, Storage::disk('public')->get(CaseAttachment::first()->file_path));
        $this->post('/api/branch-sync/v1/files/'.str_repeat('0', 64), ['file' => UploadedFile::fake()->createWithContent('test.png', $png)], $this->headers())
            ->assertStatus(422);
        $this->get('/api/branch-sync/v1/files/'.$hash, $this->headers())->assertOk();
        $this->branch('BR002');
        DB::table('branch_sync_peers')->insert(['branch_code' => 'BR002', 'token_hash' => hash('sha256', $this->token),
            'installation_id' => (string) Str::uuid(), 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/api/branch-sync/v1/files/'.$hash, $this->headers('BR002'))->assertNotFound();
    }

    public function test_a_lost_acknowledgment_can_be_replayed_and_wrong_acknowledgments_are_not_accepted(): void
    {
        $this->fixtures();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001',
            'branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        SyncState::put('installation', $this->installation);
        $sync = app(BranchSyncService::class);
        $sync->capture();
        $pending = DB::table('branch_sync_outbox')->first();
        Http::fake([
            'central.test/api/branch-sync/v1/references' => Http::response(['sealed' => SyncTransport::seal([
                'installation' => $this->installation, 'accepted_version' => 1, 'accepted_hash' => $pending->fingerprint,
                'snapshot' => app(SyncSnapshot::class)->export('BR001', 'reference'),
            ], $this->token)]),
            'central.test/api/branch-sync/v1/snapshots' => Http::response(['version' => 1, 'hash' => 'incorrect']),
        ]);
        $this->assertFalse($sync->run());
        $this->assertNull(DB::table('branch_sync_outbox')->first()->acknowledged_at);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'central.test/api/branch-sync/v1/references' => Http::response(['sealed' => SyncTransport::seal([
                'installation' => $this->installation, 'accepted_version' => 1, 'accepted_hash' => $pending->fingerprint,
                'snapshot' => app(SyncSnapshot::class)->export('BR001', 'reference'),
            ], $this->token)]),
            'central.test/api/branch-sync/v1/snapshots' => Http::response(['version' => 1, 'hash' => $pending->fingerprint, 'duplicate' => true]),
        ]);
        $this->travel(2)->minutes();
        $this->assertTrue($sync->run());
        $this->assertNotNull(DB::table('branch_sync_outbox')->first()->acknowledged_at);
    }

    public function test_offline_payment_correction_reconciles_original_replacement_and_identity_on_independent_receiver(): void
    {
        [$branch, $cases] = $this->fixtures();
        $payment = Payment::firstOrFail();
        $identity = (string) Str::uuid();
        $payment->update(['submission_id' => $identity, 'submission_hash' => str_repeat('a', 64)]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001']);
        SyncState::put('installation', $this->installation);
        SyncState::put('references_at', now()->toIso8601String());
        config(['branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        Http::fake(fn () => Http::failedConnection());
        $this->actingAs($staff)->post(route('payments.corrections.store', $payment), [
            'correction_type' => 'CORRECT', 'amount' => 600, 'reason' => 'Correct the locally recorded receipt amount.',
        ])->assertSessionHasNoErrors();
        $correction = \App\Models\PaymentCorrectionRequest::firstOrFail();
        $this->actingAs($admin)->post(route('admin.payment-corrections.approve', $correction))->assertSessionHasNoErrors();
        $this->assertTrue(SyncState::get('dirty'));
        $this->assertFalse(app(BranchSyncService::class)->run());
        $this->assertNotNull(DB::table('branch_sync_outbox')->first());
        $references = app(SyncSnapshot::class)->export('BR001', 'reference');
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $source = DB::getDefaultConnection();
        config(['database.connections.sync_receiver' => DB::getDriverName() === 'mysql'
            ? array_replace(config('database.connections.mysql'), ['database' => 'offline_receiver_20261021'])
            : config('database.connections.sqlite')]);
        Artisan::call('migrate:fresh', ['--database' => 'sync_receiver', '--force' => true]);
        DB::setDefaultConnection('sync_receiver');
        try {
            config(['branch_sync.mode' => 'central']);
            DB::table('branches')->insert(['id' => 99, 'branch_code' => 'OTHER', 'branch_name' => 'Other', 'is_active' => true]);
            app(SyncSnapshot::class)->apply($references, 'BR001', 'reference');
            app(SyncSnapshot::class)->apply($snapshot, 'BR001', 'operational');
            app(SyncSnapshot::class)->apply($snapshot, 'BR001', 'operational');
            $original = DB::table('payments')->where('submission_id', $identity)->first();
            $replacement = DB::table('payments')->where('replaces_payment_id', $original->id)->first();
            $this->assertSame(2, DB::table('payments')->count());
            $this->assertSame('VOIDED', $original->status);
            $this->assertNull($replacement->submission_id);
            $this->assertSame(600.0, (float) $replacement->amount);
            $this->assertSame(400.0, (float) $replacement->balance_after_payment);
            $this->assertSame(600.0, (float) DB::table('funeral_cases')->value('total_paid'));
            $this->assertSame(400.0, (float) DB::table('funeral_cases')->value('balance_amount'));
            $this->assertSame('APPROVED', DB::table('payment_correction_requests')->value('status'));
            $this->assertSame($replacement->id, DB::table('payment_correction_requests')->value('replacement_payment_id'));
        } finally {
            DB::setDefaultConnection($source);
            DB::purge('sync_receiver');
        }
    }

    public function test_restored_checkpoint_mismatch_holds_further_branch_writes(): void
    {
        $this->fixtures();
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001',
            'branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        SyncState::put('installation', $this->installation);
        Http::fake(['*' => Http::response(['sealed' => SyncTransport::seal([
            'installation' => $this->installation, 'accepted_version' => 12, 'accepted_hash' => 'newer',
            'snapshot' => app(SyncSnapshot::class)->export('BR001', 'reference'),
        ], $this->token)])]);
        $this->assertFalse(app(BranchSyncService::class)->run());
        $this->assertTrue(SyncState::get('recovery_required'));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_bootstrap_references_and_existing_operations_into_a_clean_branch_database(): void
    {
        [$branch] = $this->fixtures();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $staff->two_factor_secret = 'JBSWY3DPEHPK3PXP';
        $staff->two_factor_confirmed_at = now();
        $staff->save();
        $reference = app(SyncSnapshot::class)->export('BR001', 'reference');
        $operations = app(SyncSnapshot::class)->export('BR001', 'operational');
        config(['database.connections.sync_receiver' => DB::getDriverName() === 'mysql'
            ? array_replace(config('database.connections.mysql'), ['database' => 'offline_receiver_20261021'])
            : config('database.connections.sqlite')]);
        Artisan::call('migrate:fresh', ['--database' => 'sync_receiver', '--force' => true]);
        $sourceConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('sync_receiver');
        try {
            config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001',
                'branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
            Http::fake([
                'central.test/api/branch-sync/v1/references' => Http::response(['sealed' => SyncTransport::seal([
                    'installation' => $this->installation, 'accepted_version' => 0, 'accepted_hash' => null, 'snapshot' => $reference,
                ], $this->token)]),
                'central.test/api/branch-sync/v1/bootstrap' => Http::response(['sealed' => SyncTransport::seal([
                    'installation' => $this->installation, 'snapshot' => $operations,
                ], $this->token)]),
            ]);
            $sync = app(BranchSyncService::class);
            $sync->bootstrap();
            $this->assertTrue($sync->status()['initialized']);
            $this->assertSame(1, DB::table('funeral_cases')->count());
            $this->assertSame(count($reference['entities']['packages']), DB::table('packages')->count());
            $this->assertSame('JBSWY3DPEHPK3PXP', User::first()->two_factor_secret);
            try {
                $sync->bootstrap();
                $this->fail('A second bootstrap must not replace live local data.');
            } catch (\RuntimeException $error) {
                $this->assertStringContainsString('Already initialized', $error->getMessage());
            }
        } finally {
            DB::setDefaultConnection($sourceConnection);
            DB::purge('sync_receiver');
        }
    }

    public function test_catalog_pull_preserves_case_price_snapshots_and_disables_removed_accounts(): void
    {
        [$branch, $cases] = $this->fixtures();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'reference');
        foreach ($snapshot['entities']['packages'] as &$package) {
            $package['data']['price'] = 99999;
        }
        unset($package);
        $snapshot['entities']['users'] = [];
        app(SyncSnapshot::class)->apply($snapshot, 'BR001', 'reference');
        $this->assertFalse($staff->fresh()->is_active);
        $this->assertSame('1000.00', $cases[0]->fresh()->total_amount);
    }

    public function test_standalone_mode_does_not_expose_sync_api(): void
    {
        config(['branch_sync.mode' => 'standalone']);
        $this->getJson('/api/branch-sync/v1/references', $this->headers())->assertNotFound();
    }

    public function test_branch_admin_catalog_writes_are_denied_in_branch_mode(): void
    {
        $branch = $this->branch();
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'branch', 'branch_id' => $branch->id, 'is_active' => true]);
        $package = Package::first();
        $this->configureBranch();
        $this->actingAs($admin)->getJson('/sync/status')->assertOk()->assertJsonPath('initialized', true);
        $this->actingAs($admin)->putJson(route('admin.packages.update', $package, absolute: false), ['price' => 1])->assertForbidden();
        $this->actingAs($admin)->patchJson(route('admin.packages.quickPrice', $package, absolute: false), ['price' => 1])->assertForbidden();
        $this->assertNotEquals(1, (float) $package->fresh()->price);
    }

    public function test_central_blocks_operational_writes_for_a_provisioned_branch_and_skips_auto_completion(): void
    {
        [$branch, $cases] = $this->fixtures();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $cases[0]->update(['interment_at' => now()->subDay()]);
        $this->peer();
        $this->actingAs($staff)->postJson(route('payments.store', absolute: false), [
            'funeral_case_id' => $cases[0]->id, 'amount' => 500,
        ])->assertStatus(409);
        $this->assertSame(0, FuneralCase::completePastInterments());
        $this->assertSame('ACTIVE', $cases[0]->fresh()->case_status);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_offline_login_window_and_recovery_hold_are_enforced_without_blocking_logout(): void
    {
        [$branch] = $this->fixtures();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->configureBranch();
        SyncState::put('recovery_required', true);
        $this->actingAs($staff)->postJson(route('payments.store', absolute: false), ['amount' => 10])->assertStatus(409);
        $this->actingAs($staff)->post(route('logout', absolute: false))->assertRedirect();
        SyncState::put('references_at', now()->subHours(169)->toIso8601String());
        $this->get('/login')->assertStatus(503);
    }

    public function test_branch_uses_authenticator_codes_and_keeps_account_management_central(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->configureBranch();
        $this->actingAs($staff)->postJson(route('security.verify', absolute: false), ['code' => 'recovery-code'])->assertStatus(409);
        $this->actingAs($staff)->get(route('security.setup', absolute: false))->assertStatus(409);
        $this->actingAs($staff)->putJson(route('password.update', absolute: false), ['password' => 'NewPassword1234'])->assertStatus(409);
        $this->actingAs($staff)->getJson('/sync/status')->assertOk();
    }

    public function test_branch_bootstrap_gate_rejects_uninitialized_installation(): void
    {
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001']);
        $this->get('/login')->assertStatus(503);
    }

    public function test_status_banner_renders_without_changing_dashboard_permissions(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->configureBranch();
        $this->actingAs($staff)->get('/staff')->assertOk()->assertSee('Branch synchronization')->assertSee('Catalog last checked');
        $this->actingAs($staff)->get('/owner')->assertForbidden();
    }

    public function test_backup_archive_includes_public_photos_and_private_pending_sync_files(): void
    {
        Storage::disk('local')->put('branch-sync/blobs/test', 'pending immutable file');
        Storage::disk('public')->put('uploads/test-photo.jpg', 'photo contents');
        Storage::disk('local')->put('test-dump.sql', 'SQL test dump');
        Storage::disk('local')->put('test-manifest.json', '{}');
        Storage::disk('local')->makeDirectory('backup-work/test');
        $archive = Storage::disk('local')->path('backup-work/test/test-backup.zip');
        $method = new \ReflectionMethod(\App\Services\SystemBackupService::class, 'makeArchive');
        $method->invoke(app(\App\Services\SystemBackupService::class), $archive,
            Storage::disk('local')->path('test-dump.sql'), Storage::disk('local')->path('test-manifest.json'));
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($archive));
        $this->assertSame('photo contents', $zip->getFromName('public/uploads/test-photo.jpg'));
        $this->assertSame('pending immutable file', $zip->getFromName('private/branch-sync/blobs/test'));
        $zip->close();
    }

    public function test_local_changes_during_a_pending_snapshot_are_captured_in_the_next_version(): void
    {
        $this->fixtures();
        $this->configureBranch();
        config(['branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        $sync = app(BranchSyncService::class);
        $sync->capture();
        DB::table('clients')->update(['address' => 'Changed while pending']);
        SyncState::put('dirty', true);
        $accepted = 0;
        $acceptedHash = null;
        $received = [];
        Http::fake(function ($request) use (&$accepted, &$acceptedHash, &$received) {
            if (str_ends_with($request->url(), '/references')) {
                return Http::response(['sealed' => SyncTransport::seal([
                    'installation' => $this->installation, 'accepted_version' => $accepted, 'accepted_hash' => $acceptedHash,
                    'snapshot' => app(SyncSnapshot::class)->export('BR001', 'reference'),
                ], $this->token)]);
            }
            $envelope = SyncTransport::open($request['sealed'], $this->token);
            $accepted = $envelope['version'];
            $acceptedHash = hash('sha256', json_encode($envelope['snapshot'], JSON_THROW_ON_ERROR));
            $received[] = $envelope['snapshot']['entities']['clients'][0]['data']['address'];
            return Http::response(['version' => $accepted, 'hash' => $acceptedHash]);
        });
        $this->assertTrue($sync->run());
        $this->assertTrue($sync->run());
        $this->assertSame([null, 'Changed while pending'], $received);
        $this->assertSame(2, (int) DB::table('branch_sync_outbox')->max('version'));
        $this->assertSame('', DB::table('branch_sync_outbox')->orderBy('id')->value('payload'));
    }

    public function test_audit_metadata_key_order_is_normalized_but_audit_edits_are_rejected(): void
    {
        [$branch] = $this->fixtures();
        $id = DB::table('audit_logs')->insertGetId([
            'branch_id' => $branch->id, 'action' => 'case.checked', 'entity_type' => 'case',
            'metadata' => '{"b":2,"a":1}', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        DB::table('audit_logs')->where('id', $id)->update(['metadata' => '{"a":1,"b":2}']);
        app(SyncSnapshot::class)->apply($snapshot, 'BR001', 'operational');
        $snapshot['entities']['audit_logs'][0]['data']['action'] = 'tampered';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('existing audit entry was changed');
        app(SyncSnapshot::class)->apply($snapshot, 'BR001', 'operational');
    }

    public function test_idle_branch_contact_does_not_mislabel_an_unchanged_snapshot_as_disconnected(): void
    {
        $this->fixtures();
        $this->peer();
        $this->getJson('/api/branch-sync/v1/references', [...$this->headers(), 'X-Sync-Pending' => '0'])->assertOk();
        $peer = DB::table('branch_sync_peers')->first();
        $this->assertNotNull($peer->last_seen_at);
        $this->assertFalse((bool) $peer->pending_changes);
        $this->assertNull($peer->last_received_at);
    }

    public function test_operator_can_restage_corrected_data_only_while_central_has_not_accepted_the_version(): void
    {
        $this->fixtures();
        $this->configureBranch();
        config(['branch_sync.central_url' => 'https://central.test', 'branch_sync.token' => $this->token]);
        $sync = app(BranchSyncService::class);
        $sync->capture();
        $original = DB::table('branch_sync_outbox')->first();
        DB::table('clients')->update(['address' => 'Corrected source']);
        Http::fake(['*' => Http::response(['sealed' => SyncTransport::seal([
            'installation' => $this->installation, 'accepted_version' => 0, 'accepted_hash' => null,
        ], $this->token)])]);
        $sync->restage();
        $restaged = DB::table('branch_sync_outbox')->first();
        $this->assertSame(1, (int) $restaged->version);
        $this->assertNotSame($original->fingerprint, $restaged->fingerprint);
        $this->assertSame('Corrected source', OutboxPayload::decode($restaged->payload)['entities']['clients'][0]['data']['address']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['sealed' => SyncTransport::seal([
            'installation' => $this->installation, 'accepted_version' => 1, 'accepted_hash' => $restaged->fingerprint,
        ], $this->token)])]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot restage');
        $sync->restage();
    }

    public function test_global_admin_attribution_stubs_do_not_distribute_credentials_or_break_password_validation(): void
    {
        $this->branch();
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => null,
            'password' => 'ActualCentralPassword123!', 'is_active' => true]);
        $first = app(SyncSnapshot::class)->export('BR001', 'reference');
        $second = app(SyncSnapshot::class)->export('BR001', 'reference');
        $stub = $first['entities']['users'][0]['data'];
        $this->assertFalse((bool) $stub['is_active']);
        $this->assertNull($stub['two_factor_secret']);
        $this->assertNotSame($admin->getRawOriginal('password'), $stub['password']);
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('ActualCentralPassword123!', $stub['password']));
        $this->assertSame($stub['password'], $second['entities']['users'][0]['data']['password']);
    }

    public function test_central_legal_hold_survives_a_stale_branch_snapshot_and_is_pulled_back_to_branch(): void
    {
        [, $cases] = $this->fixtures();
        $stale = app(SyncSnapshot::class)->export('BR001', 'operational');
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => null, 'is_active' => true]);
        $this->peer();
        $this->actingAs($admin)->post(route('admin.retention.place-hold', $cases[0], absolute: false),
            ['reason' => 'Preserve this case for an active review.'])->assertRedirect();
        $this->assertNotNull($cases[0]->fresh()->legal_hold_at);
        $this->push($stale)->assertOk();
        $this->assertSame('legal_hold', $cases[0]->fresh()->retention_status);
        $references = app(SyncSnapshot::class)->export('BR001', 'reference');
        DB::table('funeral_cases')->update(['legal_hold_at' => null, 'legal_hold_by' => null, 'retention_status' => 'retained']);
        config(['branch_sync.mode' => 'branch']);
        app(SyncSnapshot::class)->apply($references, 'BR001', 'reference');
        $this->assertNotNull($cases[0]->fresh()->legal_hold_at);
        $this->assertSame('legal_hold', $cases[0]->fresh()->retention_status);
        $this->assertSame('1000.00', $cases[0]->fresh()->total_paid);
    }

    public function test_spoofed_branch_fields_cannot_bypass_central_operational_write_protection(): void
    {
        [$branch, $cases] = $this->fixtures();
        $other = $this->branch('BR002');
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'admin_scope' => 'system', 'branch_id' => null, 'is_active' => true]);
        $this->peer();
        $this->actingAs($staff)->postJson(route('payments.store', absolute: false), [
            'branch_id' => $other->id, 'funeral_case_id' => $cases[0]->id, 'amount' => 50,
        ])->assertStatus(409);
        $this->actingAs($admin)->putJson(route('admin.cases.update', $cases[0], absolute: false), [
            'branch_id' => $other->id,
        ])->assertStatus(409);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_balancing_negative_payments_and_unknown_payment_statuses_are_rejected(): void
    {
        $this->fixtures(1, 2);
        $this->peer();
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $invalid = $snapshot;
        $invalid['entities']['payments'][0]['data']['amount'] = -100;
        $invalid['entities']['payments'][1]['data']['amount'] = 1100;
        $this->push($invalid)->assertStatus(409);
        $invalid = $snapshot;
        $invalid['entities']['payments'][0]['data']['status'] = 'UNKNOWN_STATUS';
        $this->push($invalid)->assertStatus(409);
        $this->assertSame(1000.0, (float) DB::table('payments')->sum('amount'));
        $this->assertSame(0, (int) DB::table('branch_sync_peers')->value('accepted_version'));
    }

    public function test_central_only_owner_access_audits_do_not_require_distributing_owner_accounts(): void
    {
        [$branch] = $this->fixtures();
        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);
        DB::table('audit_logs')->insert(['branch_id' => $branch->id, 'actor_id' => $owner->id,
            'action' => 'case.viewed', 'entity_type' => 'case', 'created_at' => now(), 'updated_at' => now()]);
        $snapshot = app(SyncSnapshot::class)->export('BR001', 'operational');
        $references = app(SyncSnapshot::class)->export('BR001', 'reference');
        $this->assertSame([], $snapshot['entities']['audit_logs']);
        $this->assertSame([], $references['entities']['users']);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_central_profile_deletion_cannot_orphan_an_offline_branch_user(): void
    {
        $branch = $this->branch();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true]);
        $this->peer();
        $this->actingAs($staff)->deleteJson(route('profile.destroy', absolute: false), ['password' => 'password'])->assertStatus(409);
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }

    private function configureBranch(): void
    {
        config(['branch_sync.mode' => 'branch', 'branch_sync.branch_code' => 'BR001']);
        SyncState::put('installation', $this->installation);
        SyncState::put('references_at', now()->toIso8601String());
    }
}
