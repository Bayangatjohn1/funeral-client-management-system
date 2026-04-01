<?php

namespace App\Services;

use App\Models\FuneralCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReminderService
{
    /**
     * Build dashboard-ready reminder sets (needs attention + today's schedule).
     */
    public function buildDashboard(int $branchId, ?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $cases = $this->fetchMainOperationalCases($branchId);
        $conflicts = $this->mapConflicts($cases);

        $attention = $this->buildAttentionReminders($cases, $conflicts, $today)
            ->sortBy([
                ['severity_rank', 'desc'],
                ['sort_date', 'asc'],
            ])
            ->take(8)
            ->values();

        $todaySchedule = $this->buildTodaySchedule($cases, $today, $conflicts)
            ->sortBy('sort_date')
            ->take(5)
            ->values();

        return [
            'attention' => $attention,
            'today' => $todaySchedule,
        ];
    }

    /**
     * Build the full reminder list with optional filters for the dedicated page.
     */
    public function buildFullList(int $branchId, array $filters = [], ?Carbon $today = null): Collection
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $cases = $this->fetchMainOperationalCases(
            $branchId,
            $filters['case_status'] ?? null,
            $filters['payment_status'] ?? null
        );
        $conflicts = $this->mapConflicts($cases);

        $allReminders = collect()
            ->merge($this->buildAttentionReminders($cases, $conflicts, $today))
            ->merge($this->buildTodaySchedule($cases, $today, $conflicts))
            ->merge($this->buildUpcomingSchedules($cases, $today, $conflicts))
            ->values();

        if (!empty($filters['alert_type']) && $filters['alert_type'] !== 'all') {
            $alertType = $filters['alert_type'];
            $allReminders = $allReminders->where('type', $alertType);
        }

        if (!empty($filters['date'])) {
            try {
                $filterDate = Carbon::parse($filters['date'])->toDateString();
                $allReminders = $allReminders->filter(function ($item) use ($filterDate) {
                    return $item['date']?->toDateString() === $filterDate;
                });
            } catch (\Throwable $e) {
                // ignore invalid date filter silently
            }
        }

        if (!empty($filters['payment_status'])) {
            $allReminders = $allReminders->filter(function ($item) use ($filters) {
                return $item['case']->payment_status === $filters['payment_status'];
            });
        }

        if (!empty($filters['case_status'])) {
            $allReminders = $allReminders->filter(function ($item) use ($filters) {
                return $item['case']->case_status === $filters['case_status'];
            });
        }

        return $allReminders
            ->sortBy([
                ['severity_rank', 'desc'],
                ['sort_date', 'asc'],
            ])
            ->values();
    }

    /**
     * Fetch main-branch operational cases. Include completed only if unpaid/partial.
     */
    private function fetchMainOperationalCases(int $branchId, ?string $caseStatus = null, ?string $paymentStatus = null): Collection
    {
        $query = FuneralCase::with(['deceased', 'client'])
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('entry_source', 'MAIN')->orWhereNull('entry_source');
            })
            ->where(function ($status) use ($caseStatus) {
                if ($caseStatus === null) {
                    $status->whereIn('case_status', ['DRAFT', 'ACTIVE'])
                        ->orWhere(function ($c) {
                            $c->where('case_status', 'COMPLETED')
                                ->where(function ($b) {
                                    $b->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
                                        ->orWhere('balance_amount', '>', 0);
                                });
                        });
                    return;
                }

                if ($caseStatus === 'COMPLETED') {
                    $status->where('case_status', 'COMPLETED')
                        ->where(function ($b) {
                            $b->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
                                ->orWhere('balance_amount', '>', 0);
                        });
                } else {
                    $status->where('case_status', $caseStatus);
                }
            });

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        return $query->get();
    }

    /**
     * Identify conflict days per schedule type (funeral/interment).
     */
    private function mapConflicts(Collection $cases): array
    {
        $funeralCounts = [];
        $intermentCounts = [];

        foreach ($cases as $case) {
            $funeralDate = $case->funeral_service_at?->toDateString();
            $intermentDate = $case->interment_at?->toDateString();

            if ($funeralDate) {
                $funeralCounts[$funeralDate] = ($funeralCounts[$funeralDate] ?? 0) + 1;
            }
            if ($intermentDate) {
                $intermentCounts[$intermentDate] = ($intermentCounts[$intermentDate] ?? 0) + 1;
            }
        }

        $funeralConflictDays = collect($funeralCounts)->filter(fn ($count) => $count > 1)->keys()->all();
        $intermentConflictDays = collect($intermentCounts)->filter(fn ($count) => $count > 1)->keys()->all();

        return [
            'funeral' => $funeralConflictDays,
            'interment' => $intermentConflictDays,
        ];
    }

    /**
     * Build reminders that need attention (balance, upcoming/today schedules, conflicts).
     */
    private function buildAttentionReminders(Collection $cases, array $conflicts, Carbon $today): Collection
    {
        return $cases->flatMap(function (FuneralCase $case) use ($conflicts, $today) {
            $items = collect();

            if ($case->balance_amount > 0 && in_array($case->payment_status, ['UNPAID', 'PARTIAL'], true)) {
                $items->push($this->formatReminder($case, 'balance', 'Balance Pending', 'danger'));
            }

            $isActive = $case->case_status !== 'COMPLETED';

            if ($isActive && $case->funeral_service_at) {
                $funeralDate = $case->funeral_service_at->copy()->startOfDay();
                if ($funeralDate->greaterThanOrEqualTo($today)) {
                    $label = $funeralDate->isSameDay($today) ? 'Service Today' : 'Upcoming Service';
                    $severity = $funeralDate->isSameDay($today) ? 'primary' : 'info';
                    $items->push($this->formatReminder($case, $funeralDate->isSameDay($today) ? 'service_today' : 'upcoming_service', $label, $severity, $case->funeral_service_at));
                }
            }

            if ($isActive && $case->interment_at) {
                $intermentDate = $case->interment_at->copy()->startOfDay();
                if ($intermentDate->greaterThanOrEqualTo($today)) {
                    $label = $intermentDate->isSameDay($today) ? 'Interment Today' : 'Upcoming Interment';
                    $severity = $intermentDate->isSameDay($today) ? 'primary' : 'info';
                    $items->push($this->formatReminder($case, $intermentDate->isSameDay($today) ? 'interment_today' : 'upcoming_interment', $label, $severity, $case->interment_at));
                }
            }

            if ($isActive) {
                $funeralDateStr = $case->funeral_service_at?->toDateString();
                $intermentDateStr = $case->interment_at?->toDateString();
                if ($funeralDateStr && in_array($funeralDateStr, $conflicts['funeral'], true)) {
                    $items->push($this->formatReminder($case, 'schedule_warning', 'Similar Schedule Warning', 'warning', $case->funeral_service_at));
                }
                if ($intermentDateStr && in_array($intermentDateStr, $conflicts['interment'], true)) {
                    $items->push($this->formatReminder($case, 'schedule_warning', 'Similar Schedule Warning', 'warning', $case->interment_at));
                }
            }

            return $items;
        })->unique(function ($item) {
            return $item['case']->id.'-'.$item['type'].'-'.$item['sort_date']->toDateString();
        })->values();
    }

    /**
     * Build reminders only for today (schedule view on dashboard).
     */
    private function buildTodaySchedule(Collection $cases, Carbon $today, array $conflicts): Collection
    {
        return $cases->flatMap(function (FuneralCase $case) use ($today, $conflicts) {
            $items = collect();
            if ($case->case_status === 'COMPLETED') {
                return $items;
            }
            if ($case->funeral_service_at && $case->funeral_service_at->isSameDay($today)) {
                $items->push($this->formatReminder($case, 'service_today', 'Funeral Service', 'primary', $case->funeral_service_at, true));
            }
            if ($case->interment_at && $case->interment_at->isSameDay($today)) {
                $items->push($this->formatReminder($case, 'interment_today', 'Interment', 'primary', $case->interment_at, true));
            }
            return $items;
        })->values();
    }

    /**
     * Build upcoming schedule list (beyond today) for the full list page.
     */
    private function buildUpcomingSchedules(Collection $cases, Carbon $today, array $conflicts): Collection
    {
        return $cases->flatMap(function (FuneralCase $case) use ($today, $conflicts) {
            $items = collect();
            if ($case->case_status === 'COMPLETED') {
                return $items;
            }
            if ($case->funeral_service_at && $case->funeral_service_at->greaterThan($today)) {
                $items->push($this->formatReminder($case, 'upcoming_service', 'Upcoming Service', 'info', $case->funeral_service_at));
            }
            if ($case->interment_at && $case->interment_at->greaterThan($today)) {
                $items->push($this->formatReminder($case, 'upcoming_interment', 'Upcoming Interment', 'info', $case->interment_at));
            }
            return $items;
        })->values();
    }

    private function formatReminder(
        FuneralCase $case,
        string $type,
        string $label,
        string $severity,
        ?Carbon $date = null,
        bool $isScheduleCard = false
    ): array {
        $severityRank = match ($severity) {
            'danger' => 4,
            'warning' => 3,
            'primary' => 2,
            default => 1,
        };

        return [
            'case' => $case,
            'case_id' => $case->id,
            'case_code' => $case->case_code,
            'deceased_name' => $case->deceased?->full_name ?? 'N/A',
            'type' => $type,
            'label' => $label,
            'severity' => $severity,
            'severity_rank' => $severityRank,
            'date' => $date,
            'sort_date' => $date?->copy() ?? now(),
            'is_schedule_card' => $isScheduleCard,
        ];
    }
}
