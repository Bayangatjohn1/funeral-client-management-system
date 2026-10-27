<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSecurity
{
    public static function fingerprint(User $user): string
    {
        return hash('sha256', implode('|', [(int) $user->security_version, $user->role, $user->admin_scope, $user->branch_id, (int) $user->is_active, $user->email, $user->password]));
    }

    // Call within the same transaction as the sensitive account change.
    public static function revoke(User $user): void
    {
        $user->security_version = (int) $user->security_version + 1;
        $user->remember_token = Str::random(60);
        $user->save();
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }

    public static function audit(string $action, User $target, array $metadata): void
    {
        // Fail closed: unlike the general-purpose logger, this must not swallow failures.
        AuditLog::create([
            'actor_id' => auth()->id(), 'actor_role' => auth()->user()?->role,
            'action' => $action, 'action_label' => Str::headline(str_replace('.', ' ', $action)),
            'action_type' => 'security', 'entity_type' => 'user', 'entity_id' => $target->id,
            'branch_id' => $target->branch_id, 'target_branch_id' => $target->branch_id,
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(),
            'status' => 'success', 'transaction_id' => (string) Str::uuid(), 'metadata' => $metadata,
        ]);
    }
}
