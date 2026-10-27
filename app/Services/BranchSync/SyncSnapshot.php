<?php

namespace App\Services\BranchSync;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Support\TopbarNotificationCache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SyncSnapshot
{
    public function __construct(private readonly SyncSchema $schema) {}

    public function export(string $branchCode, string $channel): array
    {
        return $this->schema->withIdentityCache(fn () => $this->exportSnapshot($branchCode, $channel));
    }

    private function exportSnapshot(string $branchCode, string $channel): array
    {
        $branchId = DB::table('branches')->where('branch_code', $branchCode)->value('id');
        if (! $branchId) {
            throw new RuntimeException('Configured branch does not exist.');
        }
        $tables = $this->schema->tables($channel === 'reference' ? SyncSchema::REFERENCE : SyncSchema::OPERATIONAL);
        $entities = [];
        foreach ($tables as $table) {
            $rows = $channel === 'reference'
                ? DB::table($table)->orderBy('id')->get()->all()
                : $this->schema->rows($table, $branchId);
            $entities[$table] = [];
            foreach ($rows as $row) {
                // Other branches' accounts are never distributed. Global admins are attribution-only stubs.
                if ($table === 'users' && (int) $row->branch_id !== (int) $branchId
                    && ! in_array($row->admin_scope ?? '', ['system', 'main', 'all_branches'], true)) {
                    continue;
                }
                $data = array_intersect_key((array) $row, array_flip($this->schema->columns($table)));
                unset($data['remember_token']);
                if ($table === 'users') {
                    $localAccount = (int) $row->branch_id === (int) $branchId
                        && ! in_array($row->admin_scope ?? '', ['system', 'main', 'all_branches'], true)
                        && in_array($row->role, ['staff', 'admin'], true);
                    if ($localAccount) {
                        // Translate encrypted fields between independent APP_KEY values.
                        foreach (['two_factor_secret', 'two_factor_recovery_codes'] as $field) {
                            $data[$field] = $row->$field ? Crypt::decryptString($row->$field) : null;
                        }
                    } else {
                        $data['password'] = $this->stubPassword();
                        $data['is_active'] = false;
                        $data['two_factor_secret'] = null;
                        $data['two_factor_recovery_codes'] = null;
                        if (array_key_exists('privileged_for_user_id', $data)) {
                            $data['privileged_for_user_id'] = null;
                        }
                    }
                }
                foreach (SyncSchema::RELATIONS as $column => $target) {
                    if (isset($data[$column])) {
                        $data[$column] = $this->schema->uuid($target, (int) $data[$column]);
                    }
                }
                if ($table === 'intake_drafts') {
                    $data['payload'] = $this->translateDraft($data['payload'], true);
                }
                $record = ['uuid' => $this->schema->uuid($table, $row->id), 'data' => $data];
                if ($table === 'audit_logs' && $row->entity_id && isset(SyncSchema::SUBJECTS[$row->entity_type])) {
                    $subject = SyncSchema::SUBJECTS[$row->entity_type];
                    if (DB::table($subject)->where('id', $row->entity_id)->exists()) {
                        $record['subject'] = ['entity' => $subject, 'uuid' => $this->schema->uuid($subject, (int) $row->entity_id)];
                        unset($record['data']['entity_id']);
                    }
                }
                if (isset(SyncSchema::FILES[$table])) {
                    $record['files'] = [];
                    foreach (SyncSchema::FILES[$table] as $column => $disk) {
                        $available = $row->$column && (empty($row->deleted_at) || Storage::disk($disk)->exists($row->$column));
                        $record['files'][$column] = $available ? $this->freezeFile($disk, $row->$column, $branchCode) : null;
                        unset($record['data'][$column]);
                    }
                }
                $entities[$table][] = $record;
            }
        }
        $snapshot = ['protocol' => 1, 'schema' => $this->schema->signature(), 'branch' => $branchCode,
            'channel' => $channel, 'entities' => $entities];
        if ($channel === 'reference') {
            $snapshot['case_controls'] = [];
            foreach ($this->schema->rows('funeral_cases', $branchId) as $case) {
                $control = ['case_uuid' => $this->schema->uuid('funeral_cases', $case->id)];
                foreach ([...SyncSchema::HOLD_FIELDS, 'retention_status'] as $field) {
                    $control[$field] = $case->$field;
                    if ($control[$field] !== null && in_array($field, ['legal_hold_by', 'legal_hold_released_by'], true)) {
                        $control[$field] = $this->schema->uuid('users', $control[$field]);
                    }
                }
                $snapshot['case_controls'][] = $control;
            }
        }
        return $snapshot;
    }

    public function apply(array $snapshot, string $branchCode, string $channel): void
    {
        $this->schema->withIdentityCache(fn () => $this->applySnapshot($snapshot, $branchCode, $channel));
    }

    private function applySnapshot(array $snapshot, string $branchCode, string $channel): void
    {
        if (($snapshot['protocol'] ?? null) !== 1 || ($snapshot['schema'] ?? '') !== $this->schema->signature()
            || ($snapshot['branch'] ?? '') !== $branchCode || ($snapshot['channel'] ?? '') !== $channel) {
            throw new RuntimeException('Sync protocol, branch or database schema does not match.');
        }
        $expected = $this->schema->tables($channel === 'reference' ? SyncSchema::REFERENCE : SyncSchema::OPERATIONAL);
        if (array_keys($snapshot['entities'] ?? []) !== $expected) {
            throw new RuntimeException('Incomplete or unexpected snapshot tables.');
        }
        DB::transaction(function () use ($snapshot, $branchCode, $channel, $expected) {
            $branchId = DB::table('branches')->where('branch_code', $branchCode)->value('id');
            foreach ($expected as $table) {
                $seen = [];
                foreach ($snapshot['entities'][$table] as $record) {
                    if (! is_array($record) || ! Str::isUuid($record['uuid'] ?? '') || isset($seen[$record['uuid']])) {
                        throw new RuntimeException('Invalid or duplicate record identity.');
                    }
                    $seen[$record['uuid']] = true;
                    $data = $record['data'] ?? [];
                    if (! is_array($data) || array_diff(array_keys($data), $this->schema->columns($table))) {
                        throw new RuntimeException('Unexpected sync columns: '.$table);
                    }
                    $required = array_diff($this->schema->columns($table), ['remember_token'], array_keys(SyncSchema::FILES[$table] ?? []));
                    if (isset($record['subject'])) {
                        $required = array_diff($required, ['entity_id']);
                    }
                    if (array_diff($required, array_keys($data))) {
                        throw new RuntimeException('Incomplete sync record: '.$table);
                    }
                    foreach (SyncSchema::RELATIONS as $column => $target) {
                        if (isset($data[$column])) {
                            if (! is_string($data[$column]) || ! Str::isUuid($data[$column])) {
                                throw new RuntimeException('Invalid foreign identity.');
                            }
                            $data[$column] = $this->schema->localId($target, $data[$column]);
                        }
                    }
                    $mapping = DB::table('branch_sync_records')->where('uuid', $record['uuid'])->first();
                    if ($mapping && $mapping->entity !== $table) {
                        throw new RuntimeException('Record identity belongs to a different entity.');
                    }
                    $existing = $mapping ? DB::table($table)->where('id', $mapping->local_id)->first() : null;
                    if ($mapping && ! $existing) {
                        throw new RuntimeException('Mapped record was removed; reconciliation is required.');
                    }
                    if ($channel === 'operational') {
                        $this->assertOwnership($table, (object) $data, (int) $branchId);
                        if ($existing) {
                            $this->assertOwnership($table, $existing, (int) $branchId);
                        }
                        if ($table === 'funeral_cases' && config('branch_sync.mode') === 'central') {
                            foreach (SyncSchema::HOLD_FIELDS as $field) {
                                if (! $existing && $data[$field] !== null) {
                                    throw new RuntimeException('Legal holds are centrally administered.');
                                }
                                $data[$field] = $existing?->$field;
                            }
                            $data['retention_status'] = $data['legal_hold_at'] ? 'legal_hold'
                                : (($data['case_status'] === 'COMPLETED' && $data['retention_end_date']
                                    && \Illuminate\Support\Carbon::parse($data['retention_end_date'])->lte(today())) ? 'pending_disposal_review' : 'retained');
                        }
                    }
                    if ($table === 'users') {
                        foreach (['two_factor_secret', 'two_factor_recovery_codes'] as $field) {
                            $data[$field] = ($data[$field] ?? null) !== null ? Crypt::encryptString($data[$field]) : null;
                        }
                        // Local authenticator replay counters never move backwards after a pull.
                        if ($existing && ($existing->two_factor_secret ?? null) && ($data['two_factor_secret'] ?? null)
                            && Crypt::decryptString($existing->two_factor_secret) === Crypt::decryptString($data['two_factor_secret'])) {
                            $data['two_factor_last_step'] = max((int) $existing->two_factor_last_step, (int) ($data['two_factor_last_step'] ?? 0));
                        }
                        $data['remember_token'] = null;
                    }
                    if ($table === 'intake_drafts') {
                        $data['payload'] = $this->translateDraft($data['payload'], false);
                    }
                    if (isset($record['subject'])) {
                        $subject = $record['subject'];
                        if ($table !== 'audit_logs' || ($subject['entity'] ?? '') !== (SyncSchema::SUBJECTS[$data['entity_type']] ?? null)
                            || ! Str::isUuid($subject['uuid'] ?? '')) {
                            throw new RuntimeException('Invalid audit subject.');
                        }
                        $data['entity_id'] = $this->schema->localId($subject['entity'], $subject['uuid']);
                    }
                    if (isset(SyncSchema::FILES[$table])) {
                        if (array_keys($record['files'] ?? []) !== array_keys(SyncSchema::FILES[$table])) {
                            throw new RuntimeException('Missing file manifest.');
                        }
                        foreach (SyncSchema::FILES[$table] as $column => $disk) {
                            if (array_key_exists($column, $data)) {
                                throw new RuntimeException('Raw file paths are not accepted over sync.');
                            }
                            $data[$column] = $record['files'][$column] === null ? null
                                : $this->receiveFile($disk, $record['files'][$column], $branchCode);
                        }
                    }
                    if ($existing) {
                        if ($table === 'audit_logs') {
                            // Attribution IDs are remapped, but audit entries remain immutable.
                            foreach ($data as $key => $value) {
                                $same = $key === 'metadata'
                                    ? $this->canonicalJson($existing->$key) === $this->canonicalJson($value)
                                    : (string) ($existing->$key ?? '') === (string) $value;
                                if (! $same) {
                                    throw new RuntimeException('An existing audit entry was changed.');
                                }
                            }
                        } else {
                            DB::table($table)->where('id', $existing->id)->update($data);
                        }
                    } else {
                        $id = DB::table($table)->insertGetId($data);
                        DB::table('branch_sync_records')->insert(['entity' => $table, 'local_id' => $id, 'uuid' => $record['uuid']]);
                    }
                }
            }
            if ($channel === 'reference') {
                foreach ($snapshot['case_controls'] ?? [] as $control) {
                    if (! is_array($control) || ! Str::isUuid($control['case_uuid'] ?? '')
                        || array_diff(array_keys($control), ['case_uuid', ...SyncSchema::HOLD_FIELDS, 'retention_status'])
                        || count($control) !== count(SyncSchema::HOLD_FIELDS) + 2) {
                        throw new RuntimeException('Invalid central case control.');
                    }
                    $mapping = DB::table('branch_sync_records')->where('entity', 'funeral_cases')->where('uuid', $control['case_uuid'])->first();
                    if (! $mapping) {
                        continue; // Initial bootstrap loads the corresponding case in the next transaction step.
                    }
                    $case = DB::table('funeral_cases')->where('id', $mapping->local_id)->first();
                    $localBranchId = DB::table('branches')->where('branch_code', $branchCode)->value('id');
                    if (! $case || (int) $case->branch_id !== (int) $localBranchId) {
                        throw new RuntimeException('Central case control is outside this branch.');
                    }
                    unset($control['case_uuid']);
                    foreach (['legal_hold_by', 'legal_hold_released_by'] as $field) {
                        if ($control[$field] !== null) {
                            $control[$field] = $this->schema->localId('users', $control[$field]);
                        }
                    }
                    DB::table('funeral_cases')->where('id', $case->id)->update($control);
                }
                // Removed/moved accounts must lose local access even when their old row is retained for attribution.
                $activeUsers = array_column($snapshot['entities']['users'], 'uuid');
                $removed = DB::table('branch_sync_records')->where('entity', 'users')->whereNotIn('uuid', $activeUsers)->pluck('local_id');
                DB::table('users')->whereIn('id', $removed)->update(['is_active' => false, 'remember_token' => null]);
                // Child catalog rows are complete sets, rather than accumulating obsolete inclusions/pivot links.
                foreach (['add_on_catalog_package_add_on', 'package_inclusions', 'package_freebies'] as $child) {
                    $present = array_column($snapshot['entities'][$child] ?? [], 'uuid');
                    $missing = DB::table('branch_sync_records')->where('entity', $child)->whereNotIn('uuid', $present)->get();
                    foreach ($missing as $mapping) {
                        DB::table($child)->where('id', $mapping->local_id)->delete();
                        DB::table('branch_sync_records')->where('id', $mapping->id)->delete();
                    }
                }
            }
            if ($channel === 'operational') {
                // A complete snapshot makes physical removal explicit; financial/audit loss is never silently accepted.
                foreach (array_reverse($expected) as $table) {
                    // Central also writes access/print audit entries. Neither side may erase the central audit trail.
                    if ($table === 'audit_logs') {
                        continue;
                    }
                    $present = array_column($snapshot['entities'][$table], 'uuid');
                    foreach ($this->schema->rows($table, (int) $branchId) as $row) {
                        $uuid = $this->schema->uuid($table, $row->id);
                        if (! in_array($uuid, $present, true)) {
                            if (in_array($table, ['clients', 'deceased', 'funeral_cases', 'payments', 'audit_logs'], true)) {
                                throw new RuntimeException('Snapshot omits protected records; reconcile the source backup.');
                            }
                            DB::table($table)->where('id', $row->id)->delete();
                            DB::table('branch_sync_records')->where('uuid', $uuid)->delete();
                        }
                    }
                }
                $this->reconcile((int) $branchId);
            }

            // Snapshot application uses query-builder writes, so model events do
            // not run. Advance the notification scope only after this transaction.
            TopbarNotificationCache::invalidateAfterCommit([(int) $branchId]);
        }, 3);
    }

    private function assertOwnership(string $table, object $row, int $branchId): void
    {
        if (! $branchId || $this->schema->owner($table, $row) !== $branchId) {
            throw new RuntimeException('Record is outside the authenticated branch.');
        }
        // Every operational parent and actor must also be in the same branch (global admin references are allowed).
        foreach (SyncSchema::RELATIONS as $column => $target) {
            if (isset($row->$column) && $target === 'users') {
                $actor = DB::table('users')->where('id', $row->$column)->first();
                if (! $actor || ((int) $actor->branch_id !== $branchId
                    && ! in_array($actor->admin_scope ?? '', ['system', 'main', 'all_branches'], true))) {
                    throw new RuntimeException('Cross-branch actor reference rejected.');
                }
            }
            if (! isset($row->$column) || ! in_array($target, SyncSchema::OPERATIONAL, true)) {
                continue;
            }
            $parent = DB::table($target)->where('id', $row->$column)->first();
            if (! $parent || $this->schema->owner($target, $parent) !== $branchId) {
                throw new RuntimeException('Cross-branch operational relationship rejected.');
            }
        }
    }

    private function reconcile(int $branchId): void
    {
        foreach (DB::table('payments')->where('branch_id', $branchId)->get() as $payment) {
            if (! is_numeric($payment->amount) || (float) $payment->amount < 0
                || ! in_array($payment->status, [null, 'POSTED', 'VALID', 'VOID', 'VOIDED'], true)) {
                throw new RuntimeException('Invalid payment amount or transaction status.');
            }
        }
        foreach (DB::table('funeral_cases')->where('branch_id', $branchId)->get() as $case) {
            $payments = DB::table('payments')->where('funeral_case_id', $case->id)
                ->whereNull('deleted_at')
                ->where(fn ($query) => $query->whereNull('status')->orWhereNotIn('status', ['VOID', 'VOIDED']))->get();
            $cents = fn ($value) => (int) round((float) $value * 100);
            $paid = $payments->sum(fn ($payment) => $cents($payment->amount));
            $balance = max(0, $cents($case->total_amount) - $paid);
            $status = $paid <= 0 ? 'UNPAID' : ($balance > 0 ? 'PARTIAL' : 'PAID');
            if ($paid < 0 || $cents($case->total_amount) < 0 || $paid !== $cents($case->total_paid)
                || $balance !== $cents($case->balance_amount) || $case->payment_status !== $status) {
                throw new RuntimeException('Payment reconciliation failed; snapshot was not committed.');
            }
        }
    }

    private function translateDraft(?string $json, bool $export): ?string
    {
        if ($json === null) {
            return null;
        }
        $walk = function (array $values) use (&$walk, $export) {
            foreach ($values as $key => $value) {
                if (is_array($value) && in_array($key, ['selected_add_ons', 'selectedAddOns', 'custom_freebie_catalog_ids'], true)) {
                    $target = $key === 'custom_freebie_catalog_ids' ? 'freebie_catalogs' : 'add_on_catalogs';
                    $values[$key] = array_map(fn ($id) => $export ? $this->schema->uuid($target, (int) $id)
                        : (string) $this->schema->localId($target, $id), $value);
                } elseif (is_array($value)) {
                    $values[$key] = $walk($value);
                } elseif ($value !== null && $value !== '' && in_array($key, ['custom_casket_catalog_id', 'replacement_casket_catalog_id'], true)) {
                    $values[$key] = $export ? $this->schema->uuid('casket_catalogs', (int) $value)
                        : $this->schema->localId('casket_catalogs', $value);
                } elseif ($value !== null && $value !== '' && isset(SyncSchema::RELATIONS[$key])) {
                    $values[$key] = $export ? $this->schema->uuid(SyncSchema::RELATIONS[$key], (int) $value)
                        : $this->schema->localId(SyncSchema::RELATIONS[$key], $value);
                }
            }
            return $values;
        };
        return json_encode($walk(json_decode($json, true, 512, JSON_THROW_ON_ERROR)), JSON_THROW_ON_ERROR);
    }

    private function canonicalJson(?string $json): mixed
    {
        if ($json === null) {
            return null;
        }
        $sort = function ($value) use (&$sort) {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }
            return array_map($sort, $value);
        };
        return $sort(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    private function stubPassword(): string
    {
        $hash = SyncState::get('reference_stub_password');
        if (! $hash) {
            // Keep a valid hash so an attempted stub login fails validation rather than throwing a hasher error.
            $hash = \Illuminate\Support\Facades\Hash::make(bin2hex(random_bytes(32)));
            SyncState::put('reference_stub_password', $hash);
        }
        return $hash;
    }

    private function freezeFile(string $disk, string $path, string $branchCode): array
    {
        if (! Storage::disk($disk)->exists($path) || Storage::disk($disk)->size($path) > config('branch_sync.max_file_bytes')) {
            throw new RuntimeException('Required sync file is missing or exceeds the configured limit.');
        }
        $bytes = Storage::disk($disk)->get($path);
        $hash = hash('sha256', $bytes);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw new RuntimeException('Unsupported sync file extension.');
        }
        if (! Storage::disk('local')->put('branch-sync/blobs/'.$hash, $bytes)) {
            throw new RuntimeException('Unable to preserve the pending sync file.');
        }
        DB::table('branch_sync_file_grants')->insertOrIgnore(['branch_code' => $branchCode, 'hash' => $hash]);
        return ['hash' => $hash, 'extension' => $extension];
    }

    public function receiveFile(string $disk, array $file, string $branchCode): string
    {
        if (! in_array($disk, ['public', 'local'], true)
            || ! preg_match('/^[a-f0-9]{64}$/', $file['hash'] ?? '')
            || ! in_array($file['extension'] ?? '', ['jpg', 'jpeg', 'png', 'pdf'], true)) {
            throw new RuntimeException('Invalid sync file manifest.');
        }
        $blob = 'branch-sync/incoming/'.$branchCode.'/'.$file['hash'];
        // Bootstrap copies use the same immutable local blob store.
        if (! Storage::disk('local')->exists($blob)) {
            throw new RuntimeException('Sync file has not been uploaded.');
        }
        $bytes = Storage::disk('local')->get($blob);
        if (! hash_equals($file['hash'], hash('sha256', $bytes))) {
            throw new RuntimeException('Sync file checksum mismatch.');
        }
        $path = 'branch-sync/'.$branchCode.'/'.$file['hash'].'.'.$file['extension'];
        if (! Storage::disk($disk)->put($path, $bytes)) {
            throw new RuntimeException('Unable to commit sync file.');
        }
        return $path;
    }
}
