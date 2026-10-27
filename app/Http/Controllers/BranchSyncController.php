<?php

namespace App\Http\Controllers;

use App\Services\BranchSync\SyncSnapshot;
use App\Services\BranchSync\SyncTransport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BranchSyncController extends Controller
{
    public function references(Request $request, SyncSnapshot $snapshots)
    {
        $peer = $request->attributes->get('sync_peer');
        DB::table('branch_sync_peers')->where('id', $peer->id)->update([
            'last_seen_at' => now(), 'pending_changes' => $request->header('X-Sync-Pending') !== '0', 'updated_at' => now(),
        ]);
        return response()->json(['sealed' => SyncTransport::seal([
            'installation' => $peer->installation_id, 'accepted_version' => $peer->accepted_version,
            'accepted_hash' => $peer->accepted_hash,
            'activation_state' => $peer->activation_state,
            'snapshot' => DB::transaction(fn () => $snapshots->export($peer->branch_code, 'reference')),
        ], $request->bearerToken())]);
    }

    public function bootstrap(Request $request, SyncSnapshot $snapshots)
    {
        return DB::transaction(function () use ($request, $snapshots) {
            $peer = DB::table('branch_sync_peers')->where('id', $request->attributes->get('sync_peer')->id)->lockForUpdate()->first();
            abort_unless($peer->enabled && hash_equals($peer->token_hash, hash('sha256', $request->bearerToken())), 401);
            abort_unless(in_array($peer->activation_state, ['frozen', 'active'], true), 409, 'Freeze central operational recording before bootstrap.');
            abort_unless((int) $peer->accepted_version === 0, 409, 'This installation has already synchronized. Use the recovery procedure.');
            $snapshot = $snapshots->export($peer->branch_code, 'operational');
            $hash = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
            DB::table('branch_sync_peers')->where('id', $peer->id)->update(['bootstrap_hash' => $hash, 'ready_at' => null]);
            return response()->json(['sealed' => SyncTransport::seal([
                'installation' => $peer->installation_id,
                'snapshot' => $snapshot,
            ], $request->bearerToken())]);
        });
    }

    public function ready(Request $request)
    {
        $request->validate(['installation' => 'required|uuid', 'bootstrap_hash' => 'required|string|size:64']);
        DB::transaction(function () use ($request) {
            $peer = DB::table('branch_sync_peers')->where('id', $request->attributes->get('sync_peer')->id)->lockForUpdate()->first();
            abort_unless($peer->enabled && hash_equals($peer->token_hash, hash('sha256', $request->bearerToken()))
                && $peer->activation_state === 'frozen' && (int) $peer->accepted_version === 0
                && $peer->installation_id === $request->input('installation')
                && $peer->bootstrap_hash && hash_equals($peer->bootstrap_hash, $request->input('bootstrap_hash')), 409);
            DB::table('branch_sync_peers')->where('id', $peer->id)->update(['ready_at' => now(), 'updated_at' => now()]);
        });
        return response()->json(['ready' => true]);
    }

    public function push(Request $request, SyncSnapshot $snapshots)
    {
        $request->validate(['sealed' => ['required', 'string', 'max:'.(config('branch_sync.max_snapshot_bytes') * 2)]]);
        try {
            $envelope = SyncTransport::open($request->input('sealed'), $request->bearerToken());
            $peer = $request->attributes->get('sync_peer');
            $hash = hash('sha256', json_encode($envelope['snapshot'] ?? null, JSON_THROW_ON_ERROR));
            if (($envelope['installation'] ?? '') !== $peer->installation_id
                || ! is_int($envelope['version'] ?? null) || $envelope['version'] < 1) {
                throw new RuntimeException('Installation or version is invalid.');
            }
            $duplicate = DB::transaction(function () use ($peer, $envelope, $hash, $snapshots) {
                $current = DB::table('branch_sync_peers')->where('id', $peer->id)->lockForUpdate()->first();
                if (! $current->enabled || $current->activation_state !== 'active' || ! hash_equals($current->token_hash, $peer->token_hash)) {
                    throw new RuntimeException('Branch credentials were revoked while the request was in flight.');
                }
                if ($envelope['version'] === (int) $current->accepted_version && $current->accepted_hash === $hash) {
                    return true;
                }
                if ($envelope['version'] !== (int) $current->accepted_version + 1) {
                    throw new RuntimeException('Sync version conflict; reconcile before retrying.');
                }
                $snapshots->apply($envelope['snapshot'], $peer->branch_code, 'operational');
                DB::table('branch_sync_peers')->where('id', $peer->id)->update([
                    'accepted_version' => $envelope['version'], 'accepted_hash' => $hash,
                    'last_received_at' => now(), 'updated_at' => now(),
                ]);
                return false;
            }, 3);
            return response()->json(['version' => $envelope['version'], 'hash' => $hash, 'duplicate' => $duplicate]);
        } catch (\Throwable $error) {
            \Illuminate\Support\Facades\Log::warning('Branch sync snapshot rejected', [
                'branch' => $request->attributes->get('sync_peer')->branch_code,
                'reason' => get_class($error) === RuntimeException::class ? $error->getMessage() : class_basename($error),
            ]);
            // Do not return SQL, credentials, patient data or arbitrary exception text.
            return response()->json(['message' => 'Snapshot rejected. Check schema, ownership, sequence, files and payment reconciliation.'], 409);
        }
    }

    public function upload(Request $request, string $hash)
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $hash), 422);
        $request->validate(['file' => ['required', 'file', 'max:'.(int) (config('branch_sync.max_file_bytes') / 1024)]]);
        $bytes = file_get_contents($request->file('file')->getRealPath());
        abort_unless(hash_equals($hash, hash('sha256', $bytes)), 422);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true), 422);
        $peer = $request->attributes->get('sync_peer');
        abort_unless(Storage::disk('local')->put('branch-sync/incoming/'.$peer->branch_code.'/'.$hash, $bytes), 503);
        return response()->json(['hash' => $hash]);
    }

    public function missingFiles(Request $request)
    {
        $request->validate(['hashes' => ['required', 'array', 'max:5000'], 'hashes.*' => ['required', 'regex:/^[a-f0-9]{64}$/']]);
        $code = $request->attributes->get('sync_peer')->branch_code;
        $missing = array_values(array_filter(array_unique($request->input('hashes')),
            fn ($hash) => ! Storage::disk('local')->exists('branch-sync/incoming/'.$code.'/'.$hash)));
        return response()->json(['missing' => $missing]);
    }

    public function download(Request $request, string $hash, SyncSnapshot $snapshots)
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $hash), 404);
        $peer = $request->attributes->get('sync_peer');
        // A branch token may download only files referenced by that branch's records.
        abort_unless(DB::table('branch_sync_file_grants')->where('branch_code', $peer->branch_code)->where('hash', $hash)->exists()
            && Storage::disk('local')->exists('branch-sync/blobs/'.$hash), 404);
        return response(Storage::disk('local')->get('branch-sync/blobs/'.$hash), 200, ['Content-Type' => 'application/octet-stream']);
    }
}
