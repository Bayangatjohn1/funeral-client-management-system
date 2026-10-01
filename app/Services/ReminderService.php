<?php

namespace App\Services;

use App\Models\FuneralCase;
use App\Support\WakeDuration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReminderService
{
    /**
     * Build dashboard-ready reminder sets (needs attention + today's schedule).
     */
    public function buildDashboard(int $branchId, ?Carbon $today = null): array
    {
        $referenceNow = ($today ?? now())->copy();
        $today = $referenceNow->copy()->startOfDay();
        $cases = $this->fetchMainOperationalCases($branchId);
        $conflicts = $this->mapConflicts($cases);

        $attention = $this->buildAttentionReminders($cases, $conflicts, $referenceNow)
            ->sortBy([
                ['severity_rank', 'desc'],
                ['sort_date', 'asc'],
            ])
            ->take(8)
            ->values();

        $todayScheduleCases = $this->fetchTodayScheduleCases($branchId, $today);
        $todaySchedule = $this->buildTodaySchedule($todayScheduleCases, $today, $conflicts)
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
        $referenceNow = ($today ?? now())->copy();
        $today = $referenceNow->copy()->startOfDay();
        $operationalCases = $this->fetchMainOperationalCases(
            $branchId,
            $filters['case_status'] ?? null,
            $filters['payment_status'] ?? null
        );
        $scheduleCases = $this->fetchScheduleCases($branchId);
        $conflicts = $this->mapConflicts($scheduleCases);

        $allReminders = collect()
            ->merge($this->buildAttentionReminders($operationalCases, $conflicts, $referenceNow))
            ->merge($this->buildBalanceReminders($operationalCases))
            ->merge($this->buildTodaySchedule($scheduleCases, $today, $conflicts))
            ->merge($this->buildUpcomingSchedules($scheduleCases, $today, $conflicts))
            ->values();

        if (!empty($filters['alert_type']) && $filters['alert_type'] !== 'all') {
            $alertType = $filters['alert_type'];
            $allReminders = $allReminders->where('type', $alertType);
        }

        [$dateFrom, $dateTo] = $this->resolveDueWindow($filters, $today);

        if ($dateFrom && $dateTo) {
            $allReminders = $allReminders->filter(function ($item) use ($dateFrom, $dateTo) {
                if (empty($item['date'])) {
                    return true;
                }

                $itemDate = $item['date']->copy()->startOfDay();
                return $itemDate->betweenIncluded($dateFrom, $dateTo);
            });
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

    /** Cases whose wake window is active at the supplied moment. */
    public function currentlyInWake(int $branchId, ?Carbon $now = null): Collection
    {
        $now = ($now ?? now())->copy();

        return $this->fetchScheduleCases($branchId)
            ->filter(function (FuneralCase $case) use ($now) {
                $start = $this->scheduleDateTime($case, 'wake_start', $case->wake_start_date);
                $end = $this->scheduleDateTime($case, 'wake_end', $case->wake_end_date);

                return $start && $end && $start->lessThanOrEqualTo($now) && $now->lessThan($end);
            })
            ->map(function (FuneralCase $case) use ($now) {
                $totalDays = WakeDuration::days(
                    $case->wake_start_date?->toDateString(),
                    $case->wake_end_date?->toDateString()
                ) ?? 0;
                $currentDay = (int) min(max($case->wake_start_date?->diffInDays($now->copy()->startOfDay()) + 1, 1), max($totalDays, 1));

                return [
                    'case' => $case,
                    'case_id' => $case->id,
                    'case_code' => $case->case_code,
                    'deceased_name' => $case->deceased?->full_name ?? 'N/A',
                    'label' => 'Currently in Wake',
                    'current_day' => $currentDay,
                    'total_days' => $totalDays,
                    'ends_at' => $this->scheduleDateTime($case, 'wake_end', $case->wake_end_date),
                    'location' => $case->wake_location,
                ];
            })
            ->sortBy('ends_at')
            ->values();
    }

    private function resolveDueWindow(array $filters, Carbon $today): array
    {
        $window = $filters['due_window'] ?? 'any';

        try {
            return match ($window) {
                'today' => [$today->copy(), $today->copy()],
                'tomorrow' => [$today->copy()->addDay(), $today->copy()->addDay()],
                'this_week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
                'next_7' => [$today->copy(), $today->copy()->addDays(7)],
                'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
                'custom' => !empty($filters['date'])
                    ? [Carbon::parse($filters['date'])->startOfDay(), Carbon::parse($filters['date'])->startOfDay()]
                    : [null, null],
                default => [null, null],
            };
        } catch (\Throwable $e) {
            return [null, null];
        }
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
     * Fetch every case with a service or interment scheduled today. Completed
     * cases remain visible so an interment does not disappear after its time passes.
     */
    private function fetchTodayScheduleCases(int $branchId, Carbon $today): Collection
    {
        return FuneralCase::with(['deceased', 'client'])
            ->where('branch_id', $branchId)
            ->where(function ($query) {
                $query->where('entry_source', 'MAIN')->orWhereNull('entry_source');
            })
            ->where(function ($query) use ($today) {
                $query->whereDate('funeral_service_at', $today->toDateString())
                    ->orWhereDate('interment_at', $today->toDateString());
            })
            ->get();
    }

    /** Fetch schedules independently from case completion and payment state. */
    private function fetchScheduleCases(int $branchId): Collection
    {
        return FuneralCase::with(['deceased', 'client'])
            ->where('branch_id', $branchId)
            ->where(function ($query) {
                $query->where('entry_source', 'MAIN')->orWhereNull('entry_source');
            })
            ->where(function ($query) {
                $query->whereNotNull('wake_start_date')
                    ->orWhereNotNull('wake_end_date')
                    ->orWhereNotNull('funeral_service_at')
                    ->orWhereNotNull('interment_at');
            })
            ->get();
    }

    /**
     * Identify conflict days per schedule type and record which cases share each date.
     * Returns a map of date → [case summaries] for dates that have more than one case.
     */
    private function mapConflicts(Collection $cases): array
    {
        $funeralMap   = [];
        $intermentMap = [];

        foreach ($cases as $case) {
            $funeralDate   = $case->funeral_service_at?->toDateString();
            $intermentDate = $case->interment_at?->toDateString();

            if ($funeralDate) {
                $funeralMap[$funeralDate][] = [
                    'case_id'     => $case->id,
                    'case_code'   => $case->case_code,
                    'client_name' => $case->client?->full_name ?? 'N/A',
                    'time'        => $case->funeral_service_at?->format('h:i A'),
                ];
            }
            if ($intermentDate) {
                $intermentMap[$intermentDate][] = [
                    'case_id'     => $case->id,
                    'case_code'   => $case->case_code,
                    'client_name' => $case->client?->full_name ?? 'N/A',
                    'time'        => $case->interment_at?->format('h:i A'),
                ];
            }
        }

        // Keep only dates that have more than one case (actual conflicts).
        $funeralConflicts   = array_filter($funeralMap,   fn ($list) => count($list) > 1);
        $intermentConflicts = array_filter($intermentMap, fn ($list) => count($list) > 1);

        return [
            'funeral'   => $funeralConflicts,   // ['2026-05-12' => [...case summaries...]]
            'interment' => $intermentConflicts,
        ];
    }

    /** Build staff-awareness reminders without treating the interment as a payment deadline. */
    private function buildAttentionReminders(Collection $cases, array $conflicts, Carbon $now): Collection
    {
        $today = $now->copy()->startOfDay();
        $approachingUntil = $now->copy()->addHours(24);

        return $cases->flatMap(function (FuneralCase $case) use ($conflicts, $now, $today, $approachingUntil) {
            $items = collect();
            $hasBalance = (float) $case->balance_amount > 0
                && in_array($case->payment_status, ['UNPAID', 'PARTIAL'], true);
            $intermentAt = $this->scheduleDateTime($case, 'interment', $case->interment_at);

            if ($hasBalance && $intermentAt?->lessThan($now)) {
                $completedMessage = $case->payment_status === 'UNPAID'
                    ? 'Interment has been completed, but no payment has been recorded. Please review the case.'
                    : 'Interment has been completed, and a remaining balance is still recorded. Please review the payment record.';

                $items->push($this->formatReminder(
                    $case,
                    'interment_completed',
                    'Balance After Interment',
                    'danger',
                    $intermentAt,
                    false,
                    null,
                    $completedMessage
                ));
            } elseif ($hasBalance && $intermentAt?->betweenIncluded($now, $approachingUntil)) {
                $scheduleDay = $intermentAt->isSameDay($today) ? 'today' : 'tomorrow';
                $approachingMessage = $case->payment_status === 'UNPAID'
                    ? "Interment is scheduled {$scheduleDay}, and no payment has been recorded. Please review the case."
                    : "Interment is scheduled {$scheduleDay}, and a remaining balance is recorded. Please review the payment record.";

                $items->push($this->formatReminder(
                    $case,
                    'interment_approaching',
                    'Upcoming Interment With Balance',
                    'warning',
                    $intermentAt,
                    false,
                    null,
                    $approachingMessage
                ));
            }

            if ($case->case_status !== 'COMPLETED') {
                $funeralDateStr   = $case->funeral_service_at?->toDateString();
                $intermentDateStr = $case->interment_at?->toDateString();

                if ($funeralDateStr && $funeralDateStr >= $today->toDateString() && isset($conflicts['funeral'][$funeralDateStr])) {
                    $conflictingCases = array_values(array_filter(
                        $conflicts['funeral'][$funeralDateStr],
                        fn ($c) => $c['case_id'] !== $case->id
                    ));
                    $items->push($this->formatReminder(
                        $case, 'same_day_schedule', 'Same-Day Service Schedule', 'info',
                        $case->funeral_service_at, false,
                        ['type' => 'service', 'date' => $funeralDateStr, 'cases' => $conflictingCases],
                        'Another service is scheduled on the same day. Please review the schedules for coordination.'
                    ));
                }

                if ($intermentDateStr && $intermentDateStr >= $today->toDateString() && isset($conflicts['interment'][$intermentDateStr])) {
                    $conflictingCases = array_values(array_filter(
                        $conflicts['interment'][$intermentDateStr],
                        fn ($c) => $c['case_id'] !== $case->id
                    ));
                    $items->push($this->formatReminder(
                        $case, 'same_day_schedule', 'Same-Day Interment Schedule', 'info',
                        $case->interment_at, false,
                        ['type' => 'interment', 'date' => $intermentDateStr, 'cases' => $conflictingCases],
                        'Another interment is scheduled on the same day. Please review the schedules for coordination.'
                    ));
                }
            }

            return $items;
        })->unique(function ($item) {
            return $item['case']->id.'-'.$item['type'].'-'.$item['sort_date']->toDateString();
        })->values();
    }

    /** Keep the complete balance list available in the dedicated reminders page. */
    private function buildBalanceReminders(Collection $cases): Collection
    {
        return $cases
            ->filter(fn (FuneralCase $case) => (float) $case->balance_amount > 0
                && in_array($case->case_status, ['ACTIVE', 'COMPLETED'], true)
                && in_array($case->payment_status, ['UNPAID', 'PARTIAL'], true))
            ->map(fn (FuneralCase $case) => $this->formatReminder(
                $case,
                'balance',
                'Remaining Balance',
                'info',
                null,
                false,
                null,
                'May natitirang balance sa record.'
            ))
            ->values();
    }

    /**
     * Build reminders only for today (schedule view on dashboard).
     */
    private function buildTodaySchedule(Collection $cases, Carbon $today, array $conflicts): Collection
    {
        return $cases->flatMap(function (FuneralCase $case) use ($today, $conflicts) {
            $items = collect();
            if ($case->wake_start_date && $case->wake_start_date->isSameDay($today)) {
                $items->push($this->formatReminder($case, 'wake_start_today', 'Wake Begins', 'primary', $case->wake_start_date, true));
            }
            if ($case->wake_end_date && $case->wake_end_date->isSameDay($today)) {
                $items->push($this->formatReminder($case, 'wake_end_today', 'Wake End', 'primary', $case->wake_end_date, true));
            }
            if ($case->funeral_service_at && $case->funeral_service_at->isSameDay($today)) {
                $items->push($this->formatReminder($case, 'service_today', 'Funeral Ceremony', 'primary', $case->funeral_service_at, true));
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
            if ($case->wake_start_date && $case->wake_start_date->copy()->startOfDay()->greaterThan($today)) {
                $items->push($this->formatReminder($case, 'upcoming_wake_start', 'Wake Begins', 'info', $case->wake_start_date));
            }
            if ($case->wake_end_date && $case->wake_end_date->copy()->startOfDay()->greaterThan($today)) {
                $items->push($this->formatReminder($case, 'upcoming_wake_end', 'Wake End', 'info', $case->wake_end_date));
            }
            if ($case->funeral_service_at && $case->funeral_service_at->copy()->startOfDay()->greaterThan($today)) {
                $items->push($this->formatReminder($case, 'upcoming_service', 'Funeral Ceremony', 'info', $case->funeral_service_at));
            }
            if ($case->interment_at && $case->interment_at->copy()->startOfDay()->greaterThan($today)) {
                $items->push($this->formatReminder($case, 'upcoming_interment', 'Interment', 'info', $case->interment_at));
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
        bool $isScheduleCard = false,
        ?array $conflict = null,   // ['type' => 'service|interment', 'date' => 'Y-m-d', 'cases' => [...]]
        ?string $message = null
    ): array {
        $scheduleType = str_contains($type, 'interment')
            ? 'interment'
            : (str_contains($type, 'wake_start')
                ? 'wake_start'
                : (str_contains($type, 'wake_end')
                    ? 'wake_end'
                    : (str_contains($type, 'service') ? 'service' : ($conflict['type'] ?? null))));
        $date = $this->scheduleDateTime($case, $scheduleType, $date);

        $severityRank = match ($severity) {
            'danger' => 4,
            'warning' => 3,
            'primary' => 2,
            default => 1,
        };

        return [
            'case'             => $case,
            'case_id'          => $case->id,
            'case_code'        => $case->case_code,
            'deceased_name'    => $case->deceased?->full_name ?? 'N/A',
            'type'             => $type,
            'label'            => $label,
            'severity'         => $severity,
            'severity_rank'    => $severityRank,
            'date'             => $date,
            'sort_date'        => $date?->copy() ?? now(),
            'is_schedule_card' => $isScheduleCard,
            'conflict'         => $conflict,  // null for non-warning types
            'message'          => $message,
            'location'         => $scheduleType === 'interment'
                ? ($case->deceased?->place_of_cemetery ?? null)
                : $case->wake_location,
        ];
    }

    private function scheduleDateTime(FuneralCase $case, ?string $scheduleType, ?Carbon $date): ?Carbon
    {
        if (! $date || ! $scheduleType) {
            return $date?->copy();
        }

        $scheduleTime = match ($scheduleType) {
            'interment' => $case->interment_time,
            'wake_start' => $case->wake_start_time,
            'wake_end' => $case->wake_end_time,
            default => $case->funeral_service_time,
        };

        return $scheduleTime
            ? $date->copy()->setTimeFromTimeString((string) $scheduleTime)
            : $date->copy();
    }
}
