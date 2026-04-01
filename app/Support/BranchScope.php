<?php

namespace App\Support;

/**
 * Request-scoped holder for allowed branch IDs.
 *
 * The BranchScopeMiddleware populates this for the current request.
 * Models using the BranchScoped trait will read from here to apply
 * a global branch_id filter.
 */
class BranchScope
{
    /**
     * @var array<int>|null Null means no scoping (e.g., owner). Empty array means deny all.
     */
    private static ?array $allowedBranchIds = null;

    public static function set(?array $branchIds): void
    {
        self::$allowedBranchIds = $branchIds === null ? null : array_values(array_unique(array_map('intval', $branchIds)));
    }

    public static function allowed(): ?array
    {
        return self::$allowedBranchIds;
    }

    public static function clear(): void
    {
        self::$allowedBranchIds = null;
    }
}
