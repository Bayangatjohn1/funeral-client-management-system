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

        if ($request->filled('user_id')) {
            $query->where('actor_id', (int) $validated['user_id']);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $validated['branch_id']);
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

        $users = Cache::remember('audit:users:list', 600, fn () => User::orderBy('name')->get(['id', 'name']));
        $branches = Cache::remember('audit:branches:list', 600, fn () => Branch::orderBy('branch_code')->get(['id', 'branch_code', 'branch_name']));

        $validated['per_page'] = $perPage;

        return view('admin.reports.audit_logs', [
            'logs' => $logs,
            'users' => $users,
            'branches' => $branches,
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
