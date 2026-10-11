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
            'tab' => 'nullable|in:schedules,all,current_wake,today,upcoming,warnings,unpaid',
            'calendar_date' => 'nullable|date_format:Y-m-d',
            'calendar_view' => 'nullable|in:day,week',
            'schedule_section' => 'nullable|in:ongoing,today,upcoming',
            'schedule_q' => 'nullable|string|max:120',
            'upcoming_view' => 'nullable|in:auto,calendar,cases',
        ]);

        if (($validated['tab'] ?? null) === 'unpaid') {
            return redirect()->route($request->routeIs('admin.*') ? 'admin.payment-monitoring' : 'payments.history', array_filter([
                'tab' => 'summary', 'payment_status' => 'WITH_BALANCE', 'branch_id' => $branchId,
            ], fn ($value) => $value !== null));
        }

        $dueWindow = $validated['due_window'] ?? ($request->filled('date') ? 'custom' : 'any');

        $filters = [
            'alert_type' => $validated['alert_type'] ?? 'all',
            'date' => null,
            'due_window' => 'any',
            'case_status' => null,
            'payment_status' => null,
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
        $requestedTab = $validated['tab'] ?? 'schedules';
        $activeTab = $requestedTab === 'warnings' ? 'warnings' : 'schedules';
        $scheduleSection = $validated['schedule_section'] ?? match ($requestedTab) { 'current_wake' => 'ongoing', 'upcoming' => 'upcoming', default => 'today' };
        $initialDate = $requestedTab === 'upcoming' ? now()->addDay()->toDateString() : now()->toDateString();
        if (!empty($validated['focus_case']) && !in_array($requestedTab, ['today', 'current_wake'], true)) {
            $focused = $reminders->firstWhere('case_id', (int) $validated['focus_case']);
            $initialDate = ($focused['date'] ?? null)?->toDateString() ?? $initialDate;
        }
        $calendarDate = \Carbon\Carbon::parse($validated['calendar_date'] ?? $validated['date'] ?? $initialDate)->startOfDay();
        if ($scheduleSection !== 'upcoming') $calendarDate = now()->startOfDay();
        elseif ($calendarDate->lessThanOrEqualTo(now()->startOfDay())) $calendarDate = now()->addDay()->startOfDay();
        $calendarView = $scheduleSection === 'upcoming' ? ($validated['calendar_view'] ?? 'week') : 'day';
        $weekStart = $calendarDate->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        $calendarEvents = collect();
        $calendarWakes = collect();
        foreach ($reminderBranches as $branch) {
            $calendar = $reminderService->calendarData((int) $branch->id, $weekStart, $weekEnd, $filters);
            $label = $branch->branch_code.' - '.$branch->branch_name;
            $calendarEvents = $calendarEvents->merge($calendar['events']->map(fn ($event) => array_merge($event, ['branch_label' => $label])));
            $calendarWakes = $calendarWakes->merge($calendar['wakes']->map(fn ($wake) => array_merge($wake, ['branch_label' => $label])));
        }
        $sectionCounts = [
            'ongoing' => $calendarWakes->where('active', true)->count(),
            'today' => $reminders->whereIn('type', ['wake_start_today', 'wake_end_today', 'service_today', 'interment_today'])->count(),
            'upcoming' => $reminders->whereIn('type', ['upcoming_wake_start', 'upcoming_wake_end', 'upcoming_service', 'upcoming_interment'])->count(),
        ];
        $calendarEvents = $calendarEvents->filter(fn ($event) => $scheduleSection === 'today' ? $event['date']->isToday() : ($scheduleSection === 'upcoming' && $event['date']->toDateString() > now()->toDateString()))->sortBy('sort_date')->values();
        $calendarWakes = $scheduleSection === 'ongoing' ? $calendarWakes->where('active', true)->values() : collect();

        $scheduleSearch = $scheduleSection === 'upcoming' ? trim($validated['schedule_q'] ?? '') : '';
        $upcomingView = $validated['upcoming_view'] ?? 'auto';
        $upcomingCases = $scheduleSection === 'upcoming'
            ? $reminderBranches->flatMap(fn (Branch $branch) => $reminderService->upcomingCases((int) $branch->id, $filters, $scheduleSearch)
                ->map(fn ($item) => array_merge($item, ['branch_label' => $branch->branch_code.' - '.$branch->branch_name])))
                ->sortBy('next_at')->values() : collect();
        if ($scheduleSearch !== '') {
            $calendarEvents = $calendarEvents->filter(fn ($event) => mb_stripos($event['case_code'].' '.$event['deceased_name'], $scheduleSearch) !== false)->values();
        }
        $modalCases = $calendarEvents->map(fn ($event) => ['case' => $event['case'], 'branch_label' => $event['branch_label']])
            ->merge($calendarWakes->map(fn ($wake) => ['case' => $wake['case'], 'branch_label' => $wake['branch_label']]))
            ->merge($upcomingCases)->unique(fn ($item) => $item['case']->id)
            ->map(fn ($item) => array_merge($item, ['events' => $reminderService->caseSchedule($item['case'])]))->values();

        // Count unique cases per tab (not raw reminder entries) to avoid inflated numbers.
        $counts = [
            'schedules' => $calendarView === 'day' ? $calendarEvents->filter(fn ($event) => $event['date']->isSameDay($calendarDate))->count() : $calendarEvents->count(),
            'all'      => $reminders->pluck('case_id')->unique()->count(),
            'current_wake' => $currentWakeItems->pluck('case_id')->unique()->count(),
            'today'    => $reminders->whereIn('type', ['wake_start_today', 'wake_end_today', 'service_today', 'interment_today'])->count(),
            'upcoming' => $reminders->whereIn('type', ['upcoming_wake_start', 'upcoming_wake_end', 'upcoming_service', 'upcoming_interment'])->count(),
            'warnings' => $reminders->whereIn('type', ['interment_approaching', 'interment_completed'])->pluck('case_id')->unique()->count(),
            'unpaid'   => $reminders->where('type', 'balance')->pluck('case_id')->unique()->count(),
        ];

        return view('staff.reminders.index', [
            'reminders' => $reminders,
            'calendarEvents' => $calendarEvents, 'calendarWakes' => $calendarWakes,
            'scheduleSection' => $scheduleSection, 'sectionCounts' => $sectionCounts,
            'upcomingCases' => $upcomingCases, 'modalCases' => $modalCases, 'scheduleSearch' => $scheduleSearch, 'upcomingView' => $upcomingView,
            'calendarDate' => $calendarDate, 'calendarView' => $calendarView, 'weekStart' => $weekStart,
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
