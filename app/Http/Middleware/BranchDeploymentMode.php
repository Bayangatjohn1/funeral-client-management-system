<?php

namespace App\Http\Middleware;

use App\Services\BranchSync\SyncState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchDeploymentMode
{
    public function handle(Request $request, Closure $next)
    {
        $mode = config('branch_sync.mode');
        if ($mode === 'standalone') {
            return $next($request);
        }
        abort_unless(in_array($mode, ['central', 'branch'], true), 503, 'Invalid deployment mode.');
        $write = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $operational = $request->routeIs('intake.*', 'funeral-cases.*', 'clients.*', 'deceased.*', 'payments.*',
            'admin.cases.*', 'admin.payment-corrections.*')
            || ($mode === 'branch' && $request->routeIs('retention.*', 'admin.retention.*'))
            || $request->is('staff/intake*', 'staff/funeral-cases*');
        if ($mode === 'central') {
            if ($request->routeIs('profile.destroy') && $request->user()?->branch_id
                && DB::table('branches')->where('id', $request->user()->branch_id)
                    ->whereIn('branch_code', DB::table('branch_sync_peers')->whereIn('activation_state', ['frozen', 'active'])->select('branch_code'))->exists()) {
                abort(409, 'Deactivate this account through User Management to preserve branch records and synchronization.');
            }
            if ($write && $operational) {
                return DB::transaction(function () use ($request, $next) {
                    // Serialize central writes with freeze/activation. Freeze cannot race an in-flight payment.
                    DB::table('branch_sync_peers')->orderBy('id')->lockForUpdate()->get();
                    $branchIds = [];
                    if ($request->user()?->isStaff() || $request->user()?->isBranchAdmin()) {
                        $branchIds[] = $request->user()->branch_id;
                    }
                    if (is_numeric($request->input('branch_id'))) {
                        $branchIds[] = (int) $request->input('branch_id');
                    }
                    foreach ($request->route()?->parameters() ?? [] as $parameter) {
                        if ($parameter instanceof \Illuminate\Database\Eloquent\Model && $parameter->branch_id) {
                            $branchIds[] = $parameter->branch_id;
                        }
                    }
                    foreach (['funeral_case_id' => 'funeral_cases', 'case_id' => 'funeral_cases', 'payment_id' => 'payments',
                        'intake_draft_id' => 'intake_drafts', 'client_id' => 'clients', 'deceased_id' => 'deceased'] as $field => $table) {
                        if (is_numeric($request->input($field))) {
                            $branchIds[] = DB::table($table)->where('id', (int) $request->input($field))->value('branch_id');
                        }
                    }
                    $branchIds = array_values(array_filter(array_unique($branchIds)));
                    $peers = DB::table('branch_sync_peers')->whereIn('activation_state', ['frozen', 'active'])->pluck('branch_code');
                    $migrated = DB::table('branches')->whereIn('branch_code', $peers);
                    abort_if($branchIds ? $migrated->whereIn('id', $branchIds)->exists() : $peers->isNotEmpty(),
                        409, 'Encode this transaction on the owning branch server. Central is a synchronized copy.');
                    return $next($request);
                }, 3);
            }
            return $next($request);
        }
        abort_unless(SyncState::get('installation'), 503, 'Initialize this branch server before use.');
        if (! $request->routeIs('logout')) {
            $last = SyncState::get('references_at');
            abort_unless($last && now()->diffInHours(\Illuminate\Support\Carbon::parse($last), true)
                <= max(1, config('branch_sync.max_offline_hours')), 503,
                'The account synchronization window expired. Reconnect this branch server to central.');
        }
        if ($request->user()) {
            $localBranch = DB::table('branches')->where('branch_code', config('branch_sync.branch_code'))->value('id');
            abort_unless((int) $request->user()->branch_id === (int) $localBranch
                && ($request->user()->isStaff() || $request->user()->isBranchAdmin()), 403,
                'Use a staff or branch administrator account assigned to this branch.');
        }
        // Account administration, enrollment, password changes and recovery are centrally administered.
        if ($request->is('register', 'forgot-password', 'reset-password*', 'admin/users*', 'admin/system-admins*')
            || $request->routeIs('security.setup', 'security.enroll', 'security.password', 'security.password.update')
            || ($write && $request->is('profile*', 'password', 'account-security/password*', 'account-security/setup*'))) {
            abort(409, 'Manage accounts, enroll MFA and change passwords on the central system before branch use.');
        }
        if ($write && $request->routeIs('security.verify') && ! preg_match('/^\d{6}$/', (string) $request->input('code'))) {
            abort(409, 'Use an authenticator code here. Recovery codes are accepted on central only.');
        }
        if ($write && $operational) {
            abort_unless(SyncState::get('activation_state', 'active') === 'active', 409,
                'This branch is being prepared. Recording starts after central activation and a successful synchronization.');
            abort_if($operational && SyncState::get('recovery_required'), 409, 'A restored database checkpoint needs reconciliation before more changes can be saved.');
            return DB::transaction(function () use ($request, $next) {
                DB::table('branch_sync_state')->where('key', 'installation')->lockForUpdate()->first();
                abort_if(SyncState::get('recovery_required') || SyncState::get('activation_state', 'active') !== 'active', 409,
                    'Branch recording is on hold. Contact your administrator.');
                $response = $next($request);
                if ($response->getStatusCode() < 400) {
                    SyncState::put('dirty', true);
                }
                return $response;
            }, 3);
        }
        $response = $next($request);
        if ($write && $response->getStatusCode() < 400) {
            SyncState::put('dirty', true);
        }
        return $response;
    }
}
