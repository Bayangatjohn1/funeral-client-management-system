<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Keeps notification payloads short-lived without retaining stale branch data
 * after a committed case, payment, or synchronization update.
 */
class TopbarNotificationCache
{
    public static function scopeVersion(array $branchIds): string
    {
        $versions = [];

        foreach (array_values(array_unique(array_map('intval', $branchIds))) as $branchId) {
            if ($branchId > 0) {
                $versions[$branchId] = (int) Cache::get(self::branchKey($branchId), 1);
            }
        }

        ksort($versions);

        return sha1(json_encode($versions, JSON_THROW_ON_ERROR));
    }

    /** Schedule invalidation only after the enclosing transaction commits. */
    public static function invalidateAfterCommit(array $branchIds): void
    {
        $branchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds))));
        if ($branchIds === []) {
            return;
        }

        DB::afterCommit(function () use ($branchIds): void {
            foreach ($branchIds as $branchId) {
                $key = self::branchKey($branchId);
                Cache::forever($key, ((int) Cache::get($key, 1)) + 1);
            }
        });
    }

    private static function branchKey(int $branchId): string
    {
        return 'topbar:notifications:branch:'.$branchId.':version';
    }
}
