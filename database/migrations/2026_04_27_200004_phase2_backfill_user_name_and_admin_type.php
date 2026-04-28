<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills first_name and last_name on users from the single name column,
 * and admin_type from admin_scope.
 *
 * admin_scope values → admin_type values:
 *   'main'   → 'main_branch_admin'
 *   'branch' → 'branch_admin'
 *   null     → null
 */
return new class extends Migration
{
    public function up(): void
    {
        $hasFirstName  = Schema::hasColumn('users', 'first_name');
        $hasAdminType  = Schema::hasColumn('users', 'admin_type');
        $hasAdminScope = Schema::hasColumn('users', 'admin_scope');

        if (! $hasFirstName && ! $hasAdminType) {
            return;
        }

        DB::table('users')->orderBy('id')->chunk(200, function ($rows) use ($hasFirstName, $hasAdminType, $hasAdminScope) {
            foreach ($rows as $row) {
                $update = [];

                if ($hasFirstName && empty($row->first_name)) {
                    $parts     = array_values(array_filter(explode(' ', trim($row->name ?? ''))));
                    $count     = count($parts);
                    $firstName = $parts[0] ?? '';
                    $lastName  = $count > 1 ? $parts[$count - 1] : null;

                    $update['first_name'] = $firstName;
                    $update['last_name']  = $lastName;
                }

                if ($hasAdminType && $hasAdminScope && empty($row->admin_type) && ! empty($row->admin_scope)) {
                    $update['admin_type'] = match ($row->admin_scope) {
                        'main'   => 'main_branch_admin',
                        'branch' => 'branch_admin',
                        default  => null,
                    };
                }

                if (! empty($update)) {
                    DB::table('users')->where('id', $row->id)->update($update);
                }
            }
        });
    }

    public function down(): void
    {
        // Not reversible.
    }
};
