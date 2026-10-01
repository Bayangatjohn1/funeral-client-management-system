<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReminderController extends Controller
{
    public function index(Request $request, ReminderService $reminderService)
    {
        $user = $request->user();
        $scopeBranchIds = $user->branchScopeIds();
        $assignedBranchId = (int) ($user->operationalBranchId() ?? 0);
        $branchChoices = $user->isMainBranchAdmin()
            ? Branch::query()
                ->when($scopeBranchIds !== null, fn ($query) => $query->whereIn('id', $scopeBranchIds))
                ->orderBy('branch_code')
                ->get(['id', 'branch_code', 'branch_name'])
            : Branch::whereKey($assignedBranchId)->get(['id', 'branch_code', 'branch_name']);
        $branchId = $user->isMainBranchAdmin() ? null : $assignedBranchId;
        $requestedBranchId = (int) $request->input('branch_id');
        if ($requestedBranchId > 0 && $branchChoices->pluck('id')->contains($requestedBranchId)) {
            $branchId = $requestedBranchId;
        }

        $validated = $request->validate([
            'alert_type' => 'nullable|in:balance,service_today,interment_today,upcoming_service,upcoming_interment,interment_approaching,interment_completed,same_day_schedule,all',
            'date' => 'nullable|date',
            'due_window' => 'nullable|in:any,today,tomorrow,this_week,next_7,this_month,custom',
            'case_status' => 'nullable|in:DRAFT,ACTIVE,COMPLETED',
            'payment_status' => 'nullable|in:UNPAID,PARTIAL,PAID',
            'branch_id' => 'nullable|integer',
            'focus_case' => 'nullable|integer|min:1',
            'tab' => 'nullable|in:all,current_wake,today,upcoming,warnings,unpaid',
        ]);

        $dueWindow = $validated['due_window'] ?? ($request->filled('date') ? 'custom' : 'any');

        $filters = [
            'alert_type' => $validated['alert_type'] ?? 'all',
            'date' => $validated['date'] ?? null,
            'due_window' => $dueWindow,
            'case_status' => $validated['case_status'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
            'branch_id' => $branchId,
        ];

        $reminderBranches = $branchId
            ? $branchChoices->where('id', $branchId)
            : $branchChoices;
        $reminders = $reminderBranches->reduce(function (Collection $items, Branch $branch) use ($reminderService, $filters) {
            $branchFilters = array_merge($filters, ['branch_id' => (int) $branch->id]);
            $branchItems = $reminderService
                ->buildFullList((int) $branch->id, $branchFilters)
                ->map(fn ($item) => array_merge($item, [
                    'branch_id' => (int) $branch->id,
                    'branch_label' => $branch->branch_code.' - '.$branch->branch_name,
                ]));

            return $items->merge($branchItems);
        }, collect())->sortBy([
            ['severity_rank', 'desc'],
            ['sort_date', 'asc'],
        ])->values();
        $currentWakeItems = $reminderBranches->reduce(function (Collection $items, Branch $branch) use ($reminderService) {
            $branchItems = $reminderService
                ->currentlyInWake((int) $branch->id, now())
                ->map(fn ($item) => array_merge($item, [
                    'type' => 'current_wake',
                    'severity' => 'info',
                    'severity_rank' => 1,
                    'date' => $item['ends_at'] ?? null,
                    'sort_date' => $item['ends_at'] ?? now(),
                    'is_schedule_card' => false,
                    'conflict' => null,
                    'message' => 'The wake period is currently active.',
                    'branch_id' => (int) $branch->id,
                    'branch_label' => $branch->branch_code.' - '.$branch->branch_name,
                ]));

            return $items->merge($branchItems);
        }, collect())->sortBy('sort_date')->values();
        $activeTab = $validated['tab'] ?? 'all';

        // Count unique cases per tab (not raw reminder entries) to avoid inflated numbers.
        $counts = [
            'all'      => $reminders->pluck('case_id')->unique()->count(),
            'current_wake' => $currentWakeItems->pluck('case_id')->unique()->count(),
            'today'    => $reminders->whereIn('type', ['wake_start_today', 'wake_end_today', 'service_today', 'interment_today'])->count(),
            'upcoming' => $reminders->whereIn('type', ['upcoming_wake_start', 'upcoming_wake_end', 'upcoming_service', 'upcoming_interment'])->count(),
            'warnings' => $reminders->whereIn('type', ['interment_approaching', 'interment_completed', 'same_day_schedule'])->pluck('case_id')->unique()->count(),
            'unpaid'   => $reminders->where('type', 'balance')->pluck('case_id')->unique()->count(),
        ];

        return view('staff.reminders.index', [
            'reminders' => $reminders,
            'currentWakeItems' => $currentWakeItems,
            'filters' => $filters,
            'branchChoices' => $branchChoices,
            'selectedBranchId' => $branchId,
            'allowAllBranches' => $user->isMainBranchAdmin(),
            'activeTab' => $activeTab,
            'counts' => $counts,
            'focusedCaseId' => isset($validated['focus_case']) ? (int) $validated['focus_case'] : null,
        ]);
    }
}
