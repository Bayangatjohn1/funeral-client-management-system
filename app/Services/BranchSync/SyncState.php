<?php

namespace App\Services\BranchSync;

use Illuminate\Support\Facades\DB;

class SyncState
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = DB::table('branch_sync_state')->where('key', $key)->value('value');
        return $value === null ? $default : json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function put(string $key, mixed $value): void
    {
        DB::table('branch_sync_state')->updateOrInsert(['key' => $key], [
            'value' => json_encode($value, JSON_THROW_ON_ERROR), 'updated_at' => now(), 'created_at' => now(),
        ]);
    }
}
