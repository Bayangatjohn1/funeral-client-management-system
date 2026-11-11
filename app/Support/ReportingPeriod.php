<?php

namespace App\Support;

use Carbon\Carbon;

final class ReportingPeriod
{
    public const CURRENT_KEYS = [
        'THIS_MONTH', 'LAST_MONTH', 'THIS_QUARTER', 'LAST_QUARTER',
        'THIS_YEAR', 'LAST_YEAR', 'ALL_DATES', 'CUSTOM',
    ];

    public const LEGACY_KEYS = ['TODAY', 'THIS_WEEK', 'FIRST_HALF', 'SECOND_HALF'];

    public static function resolve(
        ?string $key,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?Carbon $reportingAt = null,
    ): array {
        $now = ($reportingAt ?: now())->copy();
        $key = strtoupper($key ?: 'THIS_MONTH');

        [$start, $endExclusive] = match ($key) {
            'TODAY' => [$now->copy()->startOfDay(), $now->copy()->addDay()->startOfDay()],
            'THIS_WEEK' => [$now->copy()->startOfWeek()->startOfDay(), $now->copy()->addDay()->startOfDay()],
            'LAST_MONTH' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->startOfMonth()],
            'THIS_QUARTER' => [$now->copy()->firstOfQuarter()->startOfDay(), $now->copy()->addDay()->startOfDay()],
            'LAST_QUARTER' => [$now->copy()->subQuarter()->firstOfQuarter()->startOfDay(), $now->copy()->firstOfQuarter()->startOfDay()],
            'FIRST_HALF' => [$now->copy()->startOfYear(), $now->copy()->startOfYear()->addMonths(6)],
            'SECOND_HALF' => [$now->copy()->startOfYear()->addMonths(6), $now->copy()->addYear()->startOfYear()],
            'THIS_YEAR' => [$now->copy()->startOfYear(), $now->copy()->addDay()->startOfDay()],
            'LAST_YEAR' => [$now->copy()->subYear()->startOfYear(), $now->copy()->startOfYear()],
            'ALL_DATES' => [null, null],
            'CUSTOM' => [
                $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : $now->copy()->startOfMonth(),
                $dateTo ? Carbon::parse($dateTo)->addDay()->startOfDay() : $now->copy()->addDay()->startOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->addDay()->startOfDay()],
        };

        if (! in_array($key, array_merge(self::CURRENT_KEYS, self::LEGACY_KEYS), true)) {
            $key = 'THIS_MONTH';
        }

        return [
            'key' => $key,
            'start' => $start,
            'end_exclusive' => $endExclusive,
            'date_from' => $start?->toDateString(),
            'date_to' => $endExclusive?->copy()->subDay()->toDateString(),
        ];
    }

    public static function apply($query, string $expression, array $period): void
    {
        if ($period['start']) {
            $query->whereRaw("{$expression} >= ?", [$period['start']]);
        }
        if ($period['end_exclusive']) {
            $query->whereRaw("{$expression} < ?", [$period['end_exclusive']]);
        }
    }

    public static function granularity(array $period): string
    {
        if (! $period['start'] || ! $period['end_exclusive']) {
            return 'year';
        }

        $days = $period['start']->diffInDays($period['end_exclusive']);
        return match (true) {
            $days <= 31 => 'day',
            $days <= 120 => 'week',
            $days <= 731 => 'month',
            default => 'year',
        };
    }
}
