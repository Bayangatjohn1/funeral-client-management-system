<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    protected static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch_scope', function (Builder $builder) {
            $allowed = BranchScope::allowed();

            // Null => no restriction (owner or unscoped contexts)
            if ($allowed === null) {
                return;
            }

            // Empty => deny all for safety
            if ($allowed === []) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $builder->whereIn($builder->getModel()->getTable() . '.branch_id', $allowed);
        });
    }
}
