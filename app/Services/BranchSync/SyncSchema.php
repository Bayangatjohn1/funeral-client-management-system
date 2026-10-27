<?php

namespace App\Services\BranchSync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class SyncSchema
{
    private ?array $identityCache = null;

    public function withIdentityCache(callable $operation): mixed
    {
        $previous = $this->identityCache;
        $this->identityCache = [];
        try {
            return $operation();
        } finally {
            // Never retain identities across snapshots, transactions or independent databases.
            $this->identityCache = $previous;
        }
    }
    private array $columnCache = [];

    public const FILES = [
        'deceased' => ['photo_path' => 'public', 'senior_proof_path' => 'public'],
        'case_attachments' => ['file_path' => 'public'],
        'case_documents' => ['file_path' => 'local'],
        'case_document_revisions' => ['file_path' => 'local'],
    ];
    // Order is a dependency order. No arbitrary remote table/column selection.
    public const REFERENCE = [
        'branches', 'users', 'packages', 'add_on_catalogs', 'casket_catalogs',
        'freebie_catalogs', 'package_inclusions', 'package_freebies', 'package_add_ons', 'add_on_catalog_package_add_on',
    ];

    public const OPERATIONAL = [
        'clients', 'deceased', 'funeral_cases', 'payments', 'service_details',
        'case_add_ons', 'case_notes', 'intake_drafts', 'payment_correction_requests',
        'case_attachments', 'case_documents', 'case_document_revisions', 'audit_logs',
    ];

    public const RELATIONS = [
        'branch_id' => 'branches', 'target_branch_id' => 'branches', 'reported_branch_id' => 'branches',
        'client_id' => 'clients', 'deceased_id' => 'deceased', 'package_id' => 'packages',
        'funeral_case_id' => 'funeral_cases', 'case_id' => 'funeral_cases', 'submitted_case_id' => 'funeral_cases',
        'payment_id' => 'payments', 'replaces_payment_id' => 'payments', 'replacement_payment_id' => 'payments',
        'case_document_id' => 'case_documents', 'casket_catalog_id' => 'casket_catalogs',
        'freebie_catalog_id' => 'freebie_catalogs', 'add_on_catalog_id' => 'add_on_catalogs',
        'package_add_on_id' => 'package_add_ons', 'actor_id' => 'users', 'author_id' => 'users',
        'created_by' => 'users', 'encoded_by' => 'users', 'recorded_by' => 'users',
        'generated_by' => 'users', 'uploaded_by' => 'users',
        'requested_by' => 'users', 'reviewed_by' => 'users', 'verified_by' => 'users',
        'privileged_for_user_id' => 'users',
        'superseded_by' => 'users', 'legal_hold_by' => 'users', 'legal_hold_released_by' => 'users',
    ];

    public const PARENTS = [
        'service_details' => ['funeral_case_id', 'funeral_cases'],
        'case_add_ons' => ['funeral_case_id', 'funeral_cases'],
        'case_attachments' => ['case_id', 'funeral_cases'],
        'case_documents' => ['case_id', 'funeral_cases'],
        'case_document_revisions' => ['case_document_id', 'case_documents'],
    ];

    public const SUBJECTS = [
        'client' => 'clients', 'deceased' => 'deceased', 'funeral_case' => 'funeral_cases',
        'payment' => 'payments', 'case_document' => 'case_documents', 'case_attachment' => 'case_attachments',
        'intake_draft' => 'intake_drafts', 'user' => 'users', 'package' => 'packages',
        'payment_correction_request' => 'payment_correction_requests',
    ];

    public const HOLD_FIELDS = ['legal_hold_at', 'legal_hold_by', 'legal_hold_reason', 'legal_hold_released_at', 'legal_hold_released_by'];

    public function tables(array $tables): array
    {
        return array_values(array_filter($tables, fn ($table) => Schema::hasTable($table)));
    }

    public function columns(string $table): array
    {
        $cacheKey = DB::getDefaultConnection().':'.$table;
        if (isset($this->columnCache[$cacheKey])) {
            return $this->columnCache[$cacheKey];
        }
        $columns = array_values(array_map(fn ($column) => $column['name'], array_filter(
            Schema::getColumns($table),
            fn ($column) => $column['name'] !== 'id' && empty($column['generation'])
        )));
        sort($columns);
        return $this->columnCache[$cacheKey] = $columns;
    }

    public function signature(): string
    {
        $schema = [];
        foreach ($this->tables([...self::REFERENCE, ...self::OPERATIONAL]) as $table) {
            $schema[$table] = $this->columns($table);
        }
        return hash('sha256', json_encode($schema, JSON_THROW_ON_ERROR));
    }

    public function uuid(string $table, int $id): string
    {
        $key = DB::getDefaultConnection().':uuid:'.$table.':'.$id;
        if (isset($this->identityCache[$key])) {
            return $this->identityCache[$key];
        }
        $record = DB::table('branch_sync_records')->where('entity', $table)->where('local_id', $id)->first();
        if ($record) {
            if ($this->identityCache !== null) {
                $this->identityCache[$key] = $record->uuid;
            }
            return $record->uuid;
        }
        $uuid = (string) Str::uuid();
        DB::table('branch_sync_records')->insertOrIgnore(['entity' => $table, 'local_id' => $id, 'uuid' => $uuid]);
        $value = DB::table('branch_sync_records')->where('entity', $table)->where('local_id', $id)->value('uuid');
        if ($this->identityCache !== null) {
            $this->identityCache[$key] = $value;
        }
        return $value;
    }

    public function localId(string $table, string $uuid): int
    {
        $key = DB::getDefaultConnection().':id:'.$table.':'.$uuid;
        if (isset($this->identityCache[$key])) {
            return $this->identityCache[$key];
        }
        $id = DB::table('branch_sync_records')->where('entity', $table)->where('uuid', $uuid)->value('local_id');
        if (! $id || ! DB::table($table)->where('id', $id)->exists()) {
            throw new RuntimeException('Unresolved sync reference: '.$table);
        }
        if ($this->identityCache !== null) {
            $this->identityCache[$key] = (int) $id;
        }
        return (int) $id;
    }

    public function owner(string $table, object $row): ?int
    {
        if (isset($row->branch_id)) {
            return (int) $row->branch_id;
        }
        if (isset(self::PARENTS[$table])) {
            [$column, $parent] = self::PARENTS[$table];
            $parentRow = DB::table($parent)->where('id', $row->$column)->first();
            return $parentRow ? $this->owner($parent, $parentRow) : null;
        }
        return null;
    }

    public function rows(string $table, int $branchId): array
    {
        $query = DB::table($table)->orderBy('id');
        if ($table === 'audit_logs') {
            // Access events from owners/other branches remain on central, rather than requiring their accounts locally.
            $query->where(fn ($scope) => $scope->whereNull('actor_id')->orWhereIn('actor_id', DB::table('users')
                ->where(fn ($users) => $users->where('branch_id', $branchId)
                    ->orWhereIn('admin_scope', ['system', 'main', 'all_branches']))->select('id')));
        }
        if (Schema::hasColumn($table, 'branch_id')) {
            return $query->where('branch_id', $branchId)->get()->all();
        }
        if (isset(self::PARENTS[$table])) {
            [$column, $parent] = self::PARENTS[$table];
            $ids = array_map(fn ($row) => $row->id, $this->rows($parent, $branchId));
            return $query->whereIn($column, $ids)->get()->all();
        }
        throw new RuntimeException('Operational table has no ownership rule: '.$table);
    }
}
