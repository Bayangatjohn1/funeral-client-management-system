<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'action' => ['nullable', 'string', 'max:120'],
            'action_type' => ['nullable', 'string', 'max:30'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = AuditLog::with(['actor', 'branch'])
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
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.reports.audit_logs', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::orderBy('branch_code')->get(['id', 'branch_code', 'branch_name']),
            'actionTypes' => ['create', 'update', 'delete', 'status_change', 'financial', 'security'],
            'entityTypes' => AuditLog::query()->select('entity_type')->distinct()->pluck('entity_type')->filter()->values(),
            'filters' => $validated,
        ]);
    }
}
