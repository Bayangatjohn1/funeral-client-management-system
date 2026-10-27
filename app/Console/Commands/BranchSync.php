<?php

namespace App\Console\Commands;

use App\Services\BranchSync\BranchSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BranchSync extends Command
{
    protected $signature = 'branch:sync {action=status : status, run, bootstrap, verify-ready, restage, issue-token, revoke-token, freeze, activate, cancel} {--branch=} {--reviewed : Operator verified LAN login, reconciliation, files and backup}';
    protected $description = 'Provision and synchronize an offline branch installation';

    public function handle(BranchSyncService $sync): int
    {
        try {
            $action = $this->argument('action');
            if (in_array($action, ['issue-token', 'revoke-token', 'freeze', 'activate', 'cancel'], true)) {
                if (config('branch_sync.mode') !== 'central') {
                    throw new \RuntimeException('Peer credentials can only be managed on central.');
                }
                $code = $this->option('branch');
                if (! is_string($code) || ! preg_match('/^[A-Za-z0-9_-]{1,10}$/', $code)
                    || ! DB::table('branches')->where('branch_code', $code)->exists()) {
                    throw new \RuntimeException('Specify an existing --branch code.');
                }
                if (in_array($action, ['freeze', 'activate', 'cancel'], true)) {
                    DB::transaction(function () use ($code, $action) {
                        $peer = DB::table('branch_sync_peers')->where('branch_code', $code)->lockForUpdate()->first();
                        if (! $peer) {
                            throw new \RuntimeException('Issue a preparation token first.');
                        }
                        $state = $peer->activation_state;
                        $allowed = ($action === 'freeze' && $state === 'prepared' && $peer->enabled)
                            || ($action === 'activate' && $state === 'frozen' && $peer->enabled && $peer->ready_at && $this->option('reviewed'))
                            || ($action === 'cancel' && in_array($state, ['prepared', 'frozen'], true) && (int) $peer->accepted_version === 0);
                        if (! $allowed) {
                            throw new \RuntimeException('Unsafe transition refused. Activation requires verify-ready and --reviewed; active installations require reconciliation, never cancellation.');
                        }
                        $next = ['freeze' => 'frozen', 'activate' => 'active', 'cancel' => 'cancelled'][$action];
                        DB::table('branch_sync_peers')->where('id', $peer->id)->update([
                            'activation_state' => $next, 'enabled' => $action === 'cancel' ? false : $peer->enabled,
                            'activated_at' => $action === 'activate' ? now() : $peer->activated_at,
                            'updated_at' => now(),
                        ]);
                    });
                    $this->info('Controlled branch transition completed: '.$action);
                } elseif ($action === 'revoke-token') {
                    DB::table('branch_sync_peers')->where('branch_code', $code)->update(['enabled' => false, 'updated_at' => now()]);
                    $this->info('Branch token revoked.');
                } else {
                    $token = bin2hex(random_bytes(32));
                    DB::transaction(function () use ($code, $token) {
                        $existing = DB::table('branch_sync_peers')->where('branch_code', $code)->lockForUpdate()->first();
                        DB::table('branch_sync_peers')->updateOrInsert(['branch_code' => $code], [
                            'token_hash' => hash('sha256', $token), 'enabled' => true,
                            'installation_id' => $existing?->installation_id ?? (string) Str::uuid(),
                            'activation_state' => $existing?->activation_state === 'cancelled' ? 'prepared' : ($existing?->activation_state ?? 'prepared'),
                            'ready_at' => $existing?->activation_state === 'cancelled' ? null : $existing?->ready_at,
                            'bootstrap_hash' => $existing?->activation_state === 'cancelled' ? null : $existing?->bootstrap_hash,
                            'created_at' => $existing?->created_at ?? now(), 'updated_at' => now(),
                        ]);
                    });
                    $this->warn('Store this token securely on this branch only. It is displayed once:');
                    $this->line($token);
                }
            } elseif ($action === 'verify-ready') {
                $sync->verifyReady();
                $this->info('Bootstrap checkpoint confirmed. Complete the LAN, totals, files and backup checklist before central activation.');
            } elseif ($action === 'bootstrap') {
                $sync->bootstrap();
                $this->info('Branch initialized. Verify data and files before staff cutover.');
            } elseif ($action === 'restage') {
                $sync->restage();
                $this->info('Unaccepted snapshot rebuilt from corrected local data. Run synchronization again.');
            } elseif ($action === 'run') {
                if (! $sync->run()) {
                    $this->error('Sync pending or failed. Local data is retained; inspect branch:sync status.');
                    return self::FAILURE;
                }
                $this->info('Synchronization completed.');
            } elseif ($action === 'status') {
                if (config('branch_sync.mode') === 'central') {
                    $this->table(['Branch', 'State', 'Enabled', 'Version', 'Last received'], DB::table('branch_sync_peers')
                        ->get(['branch_code', 'activation_state', 'enabled', 'accepted_version', 'last_received_at'])->map(fn ($row) => (array) $row)->all());
                } else {
                    $this->line(json_encode($sync->status(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
                }
            } else {
                throw new \RuntimeException('Unknown synchronization action.');
            }
            return self::SUCCESS;
        } catch (\Throwable $error) {
            $this->error(get_class($error) === \RuntimeException::class ? $error->getMessage()
                : 'Operation could not complete. Check deployment configuration and database readiness.');
            return self::FAILURE;
        }
    }
}
