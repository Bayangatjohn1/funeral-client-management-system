<?php

namespace Tests\Unit;

use App\Support\ReportingPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReportingPeriodTest extends TestCase
{
    public static function calendarPeriods(): array
    {
        return [
            'this month across year start' => ['THIS_MONTH', '2026-01-01', '2026-01-15'],
            'last month across year start' => ['LAST_MONTH', '2025-12-01', '2025-12-31'],
            'this quarter' => ['THIS_QUARTER', '2026-01-01', '2026-01-15'],
            'last quarter across year start' => ['LAST_QUARTER', '2025-10-01', '2025-12-31'],
            'this year' => ['THIS_YEAR', '2026-01-01', '2026-01-15'],
            'last year' => ['LAST_YEAR', '2025-01-01', '2025-12-31'],
        ];
    }

    #[DataProvider('calendarPeriods')]
    public function test_calendar_period_boundaries(string $key, string $from, string $to): void
    {
        $period = ReportingPeriod::resolve($key, reportingAt: Carbon::parse('2026-01-15 10:30:00'));

        $this->assertSame($from, $period['date_from']);
        $this->assertSame($to, $period['date_to']);
        $this->assertSame(Carbon::parse($to)->addDay()->startOfDay()->toDateTimeString(), $period['end_exclusive']->toDateTimeString());
    }

    public function test_custom_range_is_inclusive_by_using_next_day_exclusive_bound(): void
    {
        $period = ReportingPeriod::resolve('CUSTOM', '2026-10-01', '2026-10-19');

        $this->assertSame('2026-10-01 00:00:00', $period['start']->toDateTimeString());
        $this->assertSame('2026-10-20 00:00:00', $period['end_exclusive']->toDateTimeString());
    }

    public function test_all_time_has_open_bounds(): void
    {
        $period = ReportingPeriod::resolve('ALL_DATES');

        $this->assertNull($period['start']);
        $this->assertNull($period['end_exclusive']);
        $this->assertNull($period['date_from']);
        $this->assertNull($period['date_to']);
    }

    public function test_deprecated_keys_remain_supported(): void
    {
        foreach (ReportingPeriod::LEGACY_KEYS as $key) {
            $this->assertSame($key, ReportingPeriod::resolve($key)['key']);
        }
    }

    public function test_granularity_policy(): void
    {
        $this->assertSame('day', ReportingPeriod::granularity(ReportingPeriod::resolve('CUSTOM', '2026-10-01', '2026-10-19')));
        $this->assertSame('week', ReportingPeriod::granularity(ReportingPeriod::resolve('CUSTOM', '2026-07-01', '2026-09-30')));
        $this->assertSame('month', ReportingPeriod::granularity(ReportingPeriod::resolve('CUSTOM', '2026-01-01', '2026-12-31')));
        $this->assertSame('year', ReportingPeriod::granularity(ReportingPeriod::resolve('ALL_DATES')));
    }
}
