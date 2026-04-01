<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReminderService;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request, ReminderService $reminderService)
    {
        $user = $request->user();
        $scopeBranchIds = $user->branchScopeIds();
        $mainBranchId = (int) Branch::whereIn('id', $scopeBranchIds)
            ->where('branch_code', 'BR001')
            ->value('id');
        $branchId = $mainBranchId > 0 ? $mainBranchId : (int) $user->branch_id;
        $branchChoices = Branch::whereIn('id', $scopeBranchIds)
            ->where('branch_code', 'BR001')
            ->get(['id', 'branch_code', 'branch_name']);
        $requestedBranchId = (int) $request->input('branch_id');
        if ($requestedBranchId > 0 && $branchChoices->pluck('id')->contains($requestedBranchId)) {
            $branchId = $requestedBranchId;
        }

        $validated = $request->validate([
            'alert_type' => 'nullable|in:balance,service_today,interment_today,upcoming_service,upcoming_interment,schedule_warning,all',
            'date' => 'nullable|date',
            'case_status' => 'nullable|in:DRAFT,ACTIVE,COMPLETED',
            'payment_status' => 'nullable|in:UNPAID,PARTIAL,PAID',
            'branch_id' => 'nullable|integer',
        ]);

        $filters = [
            'alert_type' => $validated['alert_type'] ?? 'all',
            'date' => $validated['date'] ?? null,
            'case_status' => $validated['case_status'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
            'branch_id' => $branchId,
        ];

        $reminders = $reminderService->buildFullList($branchId, $filters);
        $activeTab = $request->query('tab', 'today');
        $counts = [
            'today' => $reminders->whereIn('type', ['service_today', 'interment_today'])->count(),
            'upcoming' => $reminders->whereIn('type', ['upcoming_service', 'upcoming_interment'])->count(),
            'unpaid' => $reminders->where('type', 'balance')->count(),
            'warnings' => $reminders->where('type', 'schedule_warning')->count(),
            'all' => $reminders->count(),
        ];

        return view('staff.reminders.index', [
            'reminders' => $reminders,
            'filters' => $filters,
            'branchChoices' => $branchChoices,
            'selectedBranchId' => $branchId,
            'activeTab' => $activeTab,
            'counts' => $counts,
        ]);
    }
}
