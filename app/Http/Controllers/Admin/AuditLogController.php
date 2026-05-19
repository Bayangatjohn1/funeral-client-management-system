<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuditLogController extends Controller
{
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        /** @var \App\Models\User $authUser */
        $authUser = $request->user();
        $isBranchAdmin = $authUser->isBranchAdmin();

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'action' => ['nullable', 'string', 'max:120'],
            'action_type' => ['nullable', 'string', 'max:30'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? self::PER_PAGE_OPTIONS[0]);
        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $query = AuditLog::with([
                'actor:id,name,role',
                'branch:id,branch_code,branch_name',
            ])
            ->latest();

        if ($isBranchAdmin) {
            // Branch admins may only see their own actions and those of staff in their branch.
            // Build the allowed actor set: themselves + staff assigned to their branch.
            $allowedActorIds = User::where('branch_id', $authUser->branch_id)
                ->where(function ($q) use ($authUser) {
                    $q->where('id', $authUser->id)
                      ->orWhere('role', 'staff');
                })
                ->pluck('id');

            $query->whereIn('actor_id', $allowedActorIds);

            // If the user filtered by a specific actor, honour it only within the allowed set
            if ($request->filled('user_id') && $allowedActorIds->contains((int) $validated['user_id'])) {
                $query->where('actor_id', (int) $validated['user_id']);
            }
        } else {
            if ($request->filled('user_id')) {
                $query->where('actor_id', (int) $validated['user_id']);
            }
            if ($request->filled('branch_id')) {
                $query->where('branch_id', (int) $validated['branch_id']);
            }
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $validated['action'] . '%');
        }
        if ($request->filled('action_type')) {
            $query->where('action_type', $validated['action_type']);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', 'like', '%' . $validated['entity_type'] . '%');
        }
        [$dateStart, $dateEnd] = $this->parseDateBounds(
            $request->filled('date_from') ? $validated['date_from'] : null,
            $request->filled('date_to') ? $validated['date_to'] : null,
        );
        if ($dateStart) {
            $query->where('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('created_at', '<=', $dateEnd);
        }

        $logs = $query->paginate($perPage)->withQueryString();

        // Branch admins only see themselves + their staff in the user filter dropdown
        $users = $isBranchAdmin
            ? User::where('branch_id', $authUser->branch_id)
                ->where(function ($q) use ($authUser) {
                    $q->where('id', $authUser->id)
                      ->orWhere('role', 'staff');
                })
                ->orderBy('name')
                ->get(['id', 'name'])
            : Cache::remember('audit:users:list', 600, fn () => User::orderBy('name')->get(['id', 'name']));

        // Branch admins only see their own branch in the filter list
        $branches = $isBranchAdmin
            ? Branch::where('id', $authUser->branch_id)->get(['id', 'branch_code', 'branch_name'])
            : Cache::remember('audit:branches:list', 600, fn () => Branch::orderBy('branch_code')->get(['id', 'branch_code', 'branch_name']));

        $validated['per_page'] = $perPage;

        return view('admin.reports.audit_logs', [
            'logs' => $logs,
            'users' => $users,
            'branches' => $branches,
            'isBranchAdminView' => $isBranchAdmin,
            'actionTypes' => ['create', 'update', 'delete', 'status_change', 'financial', 'security', 'permission'],
            'entityTypes' => AuditLog::query()->select('entity_type')->distinct()->pluck('entity_type')->filter()->values(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'filters' => $validated,
        ]);
    }

    public function show(AuditLog $audit_log)
    {
        $this->authorize('view', $audit_log);

        return response()->json([
            'remarks' => $audit_log->remarks,
            'status' => $audit_log->status,
            'metadata' => $audit_log->metadata,
            'ip_address' => $audit_log->ip_address,
            'user_agent' => $audit_log->user_agent,
            'transaction_id' => $audit_log->transaction_id,
            'created_at' => $audit_log->created_at,
            'action_label' => $audit_log->action_label,
        ]);
    }
}
