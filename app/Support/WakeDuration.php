<?php

namespace App\Support;

use Carbon\Carbon;

class WakeDuration
{
    public static function calculate(?string $wakeStartDate, ?string $intermentDate): ?array
    {
        if (! $wakeStartDate || ! $intermentDate) {
            return null;
        }

        try {
            $timezone = config('app.timezone', 'Asia/Manila');
            $start = Carbon::parse($wakeStartDate, $timezone)->timezone($timezone)->startOfDay();
            $end = Carbon::parse($intermentDate, $timezone)->timezone($timezone)->startOfDay();

            if ($end->lt($start)) {
                return null;
            }

            $days = min(365, (int) $start->diffInDays($end) + 1);
            $nights = self::nightsFromDays($days);

            return [
                'days' => $days,
                'nights' => $nights,
                'label' => self::labelFromDays($days),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public static function days(?string $wakeStartDate, ?string $intermentDate): ?int
    {
        return self::calculate($wakeStartDate, $intermentDate)['days'] ?? null;
    }

    public static function nightsFromDays(?int $days): int
    {
        return max(((int) $days) - 1, 0);
    }

    public static function labelFromDays(?int $days): string
    {
        if ($days === null) {
            return '—';
        }

        $days = max((int) $days, 0);

        return $days . 'D/' . self::nightsFromDays($days) . 'N';
    }
}
