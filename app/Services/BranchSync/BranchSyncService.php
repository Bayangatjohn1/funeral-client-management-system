<?php

namespace App\Services\BranchSync;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BranchSyncService
{
    public function __construct(private readonly SyncSnapshot $snapshots, private readonly SyncSchema $schema) {}

    private function client(): PendingRequest
    {
        $url = config('branch_sync.central_url');
        if (! is_string($url) || ! str_starts_with($url, 'https://') || parse_url($url, PHP_URL_USER)
            || ! preg_match('/^[a-f0-9]{64}$/', config('branch_sync.token', ''))) {
            throw new RuntimeException('Configure an HTTPS central URL and a valid branch token.');
        }
        return Http::baseUrl(rtrim($url, '/').'/api/branch-sync/v1')->acceptJson()
            ->withToken(config('branch_sync.token'))->withHeaders([
                'X-Branch-Code' => config('branch_sync.branch_code'),
                'X-Sync-Pending' => DB::table('branch_sync_outbox')->whereNull('acknowledged_at')->exists()
                    || SyncState::get('dirty', true) ? '1' : '0',
            ])
            ->connectTimeout(10)->timeout(config('branch_sync.timeout'))->withoutRedirecting();
    }

    private function read(string $endpoint): array
    {
        $response = $this->client()->get($endpoint)->throw();
        return SyncTransport::open($response->json('sealed'), config('branch_sync.token'));
    }

    public function bootstrap(): void
    {
        $this->assertBranch();
        if (SyncState::get('installation')) {
            throw new RuntimeException('Already initialized. Restore and reconcile; do not bootstrap over existing records.');
        }
        foreach ($this->schema->tables(['branches', 'users', ...SyncSchema::OPERATIONAL]) as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Bootstrap requires a clean, migrated branch database without seeded records.');
            }
        }
        $reference = $this->read('references');
        $operational = $this->read('bootstrap');
        if ($reference['installation'] !== $operational['installation'] || (int) $reference['accepted_version'] !== 0) {
            throw new RuntimeException('Central bootstrap checkpoint changed.');
        }
        $this->downloadFiles($operational['snapshot']);
        DB::transaction(function () use ($reference, $operational) {
            // Historical migrations insert demo catalogs even without db:seed. Replace only on this empty installation.
            foreach (array_reverse($this->schema->tables(SyncSchema::REFERENCE)) as $table) {
                DB::table($table)->delete();
            }
            $this->snapshots->apply($reference['snapshot'], config('branch_sync.branch_code'), 'reference');
            $this->snapshots->apply($operational['snapshot'], config('branch_sync.branch_code'), 'operational');
            SyncState::put('installation', $reference['installation']);
            SyncState::put('activation_state', $reference['activation_state'] ?? 'active');
            SyncState::put('bootstrap_hash', hash('sha256', json_encode($operational['snapshot'], JSON_THROW_ON_ERROR)));
            SyncState::put('reference_hash', hash('sha256', json_encode($reference['snapshot'], JSON_THROW_ON_ERROR)));
            SyncState::put('references_at', now()->toIso8601String());
            SyncState::put('last_success_at', now()->toIso8601String());
            SyncState::put('sequence', 0);
            SyncState::put('capture_hash', null);
        });
    }

    public function capture(): ?int
    {
        $this->assertBranch();
        if (! SyncState::get('installation')) {
            throw new RuntimeException('Bootstrap this branch before synchronization.');
        }
        if (SyncState::get('activation_state', 'active') !== 'active') {
            return null;
        }
        return DB::transaction(function () {
            // This row is also locked by branch write middleware, giving a consistent committed snapshot.
            DB::table('branch_sync_state')->where('key', 'installation')->lockForUpdate()->first();
            $pending = DB::table('branch_sync_outbox')->whereNull('acknowledged_at')->first();
            if ($pending) {
                return $pending->id;
            }
            $snapshot = $this->snapshots->export(config('branch_sync.branch_code'), 'operational');
            $json = json_encode($snapshot, JSON_THROW_ON_ERROR);
            if (strlen($json) > config('branch_sync.max_snapshot_bytes')) {
                throw new RuntimeException('Snapshot limit reached. Keep local data and increase capacity after measurement; do not reset sync.');
            }
            $hash = hash('sha256', $json);
            if (SyncState::get('capture_hash') === $hash) {
                SyncState::put('dirty', false);
                return null;
            }
            $version = (int) SyncState::get('sequence', 0) + 1;
            $id = DB::table('branch_sync_outbox')->insertGetId([
                'version' => $version, 'fingerprint' => $hash,
                'payload' => OutboxPayload::encode($json), 'created_at' => now(), 'updated_at' => now(),
            ]);
            SyncState::put('sequence', $version);
            SyncState::put('capture_hash', $hash);
            SyncState::put('dirty', false);
            return $id;
        }, 3);
    }

    public function run(): bool
    {
        $this->assertBranch();
        // Explicit expiry bounds prevent concurrent workers on the same installation.
        $lock = Cache::lock('branch-sync-worker', 900);
        if (! $lock->get()) {
            throw new RuntimeException('A sync worker is already running.');
        }
        try {
            SyncState::put('worker_started_at', now()->toIso8601String());
            $startedAt = microtime(true);
            $id = $this->capture(); // Durable before attempting network access.
            $pending = $id ? DB::table('branch_sync_outbox')->find($id) : null;
            if ($pending?->next_attempt_at && now()->lt($pending->next_attempt_at)) {
                return false;
            }
            $reference = $this->read('references');
            SyncState::put('last_contact_at', now()->toIso8601String());
            SyncState::put('connection_failed', false);
            if ($reference['installation'] !== SyncState::get('installation')) {
                SyncState::put('recovery_required', true);
                throw new RuntimeException('Installation identity changed; recovery is required.');
            }
            $lastAcknowledged = (int) DB::table('branch_sync_outbox')->whereNotNull('acknowledged_at')->max('version');
            $centralVersion = (int) $reference['accepted_version'];
            if ($centralVersion !== $lastAcknowledged
                && (! $pending || $centralVersion !== (int) $pending->version || $reference['accepted_hash'] !== $pending->fingerprint)) {
                SyncState::put('recovery_required', true);
                throw new RuntimeException('Central checkpoint differs from the local backup. Reconcile before writing.');
            }
            DB::transaction(function () use ($reference) {
                DB::table('branch_sync_state')->where('key', 'installation')->lockForUpdate()->first();
                $referenceHash = hash('sha256', json_encode($reference['snapshot'], JSON_THROW_ON_ERROR));
                if (SyncState::get('reference_hash') !== $referenceHash) {
                    $this->snapshots->apply($reference['snapshot'], config('branch_sync.branch_code'), 'reference');
                    SyncState::put('reference_hash', $referenceHash);
                }
                SyncState::put('references_at', now()->toIso8601String());
                SyncState::put('activation_state', $reference['activation_state'] ?? 'active');
            });
            if ($pending) {
                $snapshot = OutboxPayload::decode($pending->payload);
                $files = $this->files($snapshot);
                $missing = [];
                foreach (array_chunk(array_column($files, 'hash'), 5000) as $hashes) {
                    $batchMissing = $this->client()->post('files/check', ['hashes' => $hashes])->throw()->json('missing');
                    if (! is_array($batchMissing) || array_diff($batchMissing, $hashes)) {
                        throw new RuntimeException('Invalid file inventory acknowledgment.');
                    }
                    $missing = [...$missing, ...$batchMissing];
                }
                foreach ($files as $file) {
                    if (! in_array($file['hash'], $missing, true)) {
                        continue;
                    }
                    if (microtime(true) - $startedAt > 240) {
                        throw new RuntimeException('File transfer time budget reached. Uploaded files will be reused on retry.');
                    }
                    $bytes = Storage::disk('local')->get('branch-sync/blobs/'.$file['hash']);
                    $response = $this->client()->attach('file', $bytes, $file['hash'].'.'.$file['extension'])
                        ->post('files/'.$file['hash'])->throw();
                    if ($response->json('hash') !== $file['hash']) {
                        throw new RuntimeException('File acknowledgment mismatch.');
                    }
                }
                $response = $this->client()->post('snapshots', ['sealed' => SyncTransport::seal([
                    'installation' => SyncState::get('installation'), 'version' => (int) $pending->version,
                    'snapshot' => $snapshot,
                ], config('branch_sync.token'))])->throw();
                if ((int) $response->json('version') !== (int) $pending->version || $response->json('hash') !== $pending->fingerprint) {
                    throw new RuntimeException('Snapshot acknowledgment mismatch.');
                }
                DB::table('branch_sync_outbox')->where('id', $pending->id)->update(['acknowledged_at' => now(), 'updated_at' => now()]);
                // Keep checkpoint history, without retaining repeated acknowledged copies of personal data.
                DB::table('branch_sync_outbox')->whereNotNull('acknowledged_at')->where('id', '<', $pending->id)->update(['payload' => '']);
            }
            SyncState::put('last_success_at', now()->toIso8601String());
            SyncState::put('last_error', null);
            SyncState::put('last_error_reason', null);
            return true;
        } catch (\Throwable $error) {
            if ($error instanceof \Illuminate\Http\Client\ConnectionException) {
                SyncState::put('connection_failed', true);
                SyncState::put('last_connection_failure_at', now()->toIso8601String());
            }
            if (isset($pending) && $pending) {
                DB::table('branch_sync_outbox')->where('id', $pending->id)->update([
                    'attempts' => $pending->attempts + 1,
                    'next_attempt_at' => now()->addSeconds(min(60, 15 * (2 ** min(2, $pending->attempts)))),
                    'updated_at' => now(),
                ]);
            }
            // HTTP exceptions can include response bodies; persist only a safe category.
            SyncState::put('last_error', 'Sync failed. Local records are retained. Check connectivity, credentials, checkpoint and server logs.');
            SyncState::put('last_error_reason', get_class($error) === RuntimeException::class ? $error->getMessage()
                : ($error instanceof \Illuminate\Http\Client\RequestException ? 'HTTP '.$error->response->status() : class_basename($error)));
            return false;
        } finally {
            SyncState::put('worker_started_at', null);
            $lock->release();
        }
    }

    public function status(): array
    {
        $pending = DB::table('branch_sync_outbox')->whereNull('acknowledged_at')->count();
        $dirty = (bool) SyncState::get('dirty', false);
        $started = SyncState::get('worker_started_at');
        $syncing = $started && now()->diffInSeconds(\Illuminate\Support\Carbon::parse($started), true) < 900;
        $contact = SyncState::get('last_contact_at');
        $connection = SyncState::get('connection_failed', false) ? 'Offline / central unreachable at last attempt'
            : ($contact && \Illuminate\Support\Carbon::parse($contact)->gte(now()->subMinutes(2)) ? 'Online at last check' : 'Connection not recently checked');
        $state = SyncState::get('recovery_required') ? 'Requires attention'
            : (SyncState::get('activation_state', 'active') !== 'active' ? 'Preparing branch'
                : ($syncing ? 'Syncing' : (SyncState::get('last_error') ? 'Sync failed'
                    : (($pending || $dirty) ? 'Pending sync' : (SyncState::get('last_success_at') ? 'Synced' : 'Awaiting first sync')))));
        return [
            'mode' => config('branch_sync.mode'), 'branch' => config('branch_sync.branch_code'),
            'initialized' => (bool) SyncState::get('installation'),
            'pending_snapshots' => $pending,
            'uncaptured_changes' => $dirty,
            'sync_state' => $state, 'connection_state' => $connection,
            'saved_locally' => $pending > 0 || $dirty, 'last_contact_at' => $contact,
            'last_success_at' => SyncState::get('last_success_at'), 'catalog_last_synced_at' => SyncState::get('references_at'),
            'last_error' => SyncState::get('last_error'),
            'recovery_required' => (bool) SyncState::get('recovery_required', false),
            'last_error_reason' => SyncState::get('last_error_reason'),
            'activation_state' => SyncState::get('activation_state', 'active'),
        ];
    }

    public function verifyReady(): void
    {
        $this->assertBranch();
        if (! SyncState::get('installation') || ! SyncState::get('bootstrap_hash')
            || SyncState::get('activation_state') !== 'frozen') {
            throw new RuntimeException('Only an initialized, frozen branch can confirm readiness.');
        }
        $response = $this->client()->post('ready', [
            'installation' => SyncState::get('installation'), 'bootstrap_hash' => SyncState::get('bootstrap_hash'),
        ])->throw();
        if ($response->json('ready') !== true) {
            throw new RuntimeException('Central did not confirm bootstrap readiness.');
        }
    }

    public function restage(): void
    {
        $this->assertBranch();
        $lock = Cache::lock('branch-sync-worker', 900);
        if (! $lock->get()) {
            throw new RuntimeException('A sync worker is already running.');
        }
        try {
            $reference = $this->read('references');
            DB::transaction(function () use ($reference) {
                DB::table('branch_sync_state')->where('key', 'installation')->lockForUpdate()->first();
                $pending = DB::table('branch_sync_outbox')->whereNull('acknowledged_at')->lockForUpdate()->first();
                $last = DB::table('branch_sync_outbox')->whereNotNull('acknowledged_at')->orderByDesc('version')->first();
                if (! $pending || $reference['installation'] !== SyncState::get('installation')
                    || (int) $reference['accepted_version'] !== (int) ($last?->version ?? 0)
                    || (int) $pending->version !== (int) $reference['accepted_version'] + 1
                    || $reference['accepted_hash'] !== $last?->fingerprint) {
                    throw new RuntimeException('Cannot restage: central must confirm this version has not been accepted. Run sync or reconcile the checkpoint first.');
                }
                $json = json_encode($this->snapshots->export(config('branch_sync.branch_code'), 'operational'), JSON_THROW_ON_ERROR);
                if (strlen($json) > config('branch_sync.max_snapshot_bytes')) {
                    throw new RuntimeException('Corrected snapshot exceeds the configured capacity.');
                }
                $hash = hash('sha256', $json);
                DB::table('branch_sync_outbox')->where('id', $pending->id)->update([
                    'payload' => OutboxPayload::encode($json), 'fingerprint' => $hash,
                    'attempts' => 0, 'next_attempt_at' => null, 'updated_at' => now(),
                ]);
                SyncState::put('capture_hash', $hash);
                SyncState::put('dirty', false);
            });
        } finally {
            $lock->release();
        }
    }

    private function files(array $snapshot): array
    {
        $files = [];
        foreach ($snapshot['entities'] as $records) {
            foreach ($records as $record) {
                foreach ($record['files'] ?? [] as $file) {
                    if ($file) {
                        $files[$file['hash']] = $file;
                    }
                }
            }
        }
        return array_values($files);
    }

    private function downloadFiles(array $snapshot): void
    {
        foreach ($this->files($snapshot) as $file) {
            $bytes = $this->client()->get('files/'.$file['hash'])->throw()->body();
            if (! hash_equals($file['hash'], hash('sha256', $bytes))) {
                throw new RuntimeException('Bootstrap file checksum mismatch.');
            }
            Storage::disk('local')->put('branch-sync/incoming/'.config('branch_sync.branch_code').'/'.$file['hash'], $bytes);
        }
    }

    private function assertBranch(): void
    {
        if (config('branch_sync.mode') !== 'branch' || ! preg_match('/^[A-Za-z0-9_-]{1,10}$/', config('branch_sync.branch_code', ''))) {
            throw new RuntimeException('Configure branch mode and a valid branch code first.');
        }
    }
}
